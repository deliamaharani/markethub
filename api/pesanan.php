<?php
define('API_REQUEST', true);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/middleware/auth.php';

$method = api_methods(['GET', 'POST', 'PUT', 'DELETE']);
$user = api_require();
$id = (int) ($_GET['id'] ?? 0);

/** Format pesanan; $items bila disertakan = rincian produk. */
function pesanan_out(array $o, $items = null)
{
    $al = parse_alamat($o['alamat_pengiriman']);
    $out = [
        'id' => (int) $o['id'],
        'nomor_pesanan' => nomor_pesanan($o['id'], $o['tanggal_pesanan']),
        'pembeli' => ['id' => (int) $o['user_id'], 'nama' => $o['pembeli_nama'] ?? null],
        'total_harga' => (int) $o['total_harga'],
        'status_pesanan' => $o['status_pesanan'],
        'penerima' => ['nama' => $al['nama'], 'telepon' => $al['telp'], 'alamat' => $al['alamat']],
        'tanggal_pesanan' => $o['tanggal_pesanan'],
    ];
    if ($items !== null) {
        $out['items'] = array_map(function ($i) {
            return ['produk_id' => (int) $i['produk_id'], 'nama_produk' => $i['nama_produk'], 'penjual' => $i['penjual_nama'],
                'jumlah' => (int) $i['jumlah'], 'harga_satuan' => (int) $i['harga_satuan'], 'subtotal' => (int) $i['subtotal']];
        }, $items);
    }
    return $out;
}

/** Ambil pesanan jika pengguna berhak; selain itu hentikan dengan 404/403. */
function pesanan_akses($id, array $user)
{
    $o = db_row('SELECT o.*, b.nama AS pembeli_nama FROM pesanan o JOIN users b ON b.id = o.user_id WHERE o.id = ?', [(int) $id]);
    if (!$o) {
        api_fail('Pesanan tidak ditemukan.', 404);
    }
    if ($user['role'] === 'pembeli' && (int) $o['user_id'] !== $user['id']) {
        api_fail('Pesanan ini bukan milik Anda.', 403);
    }
    if ($user['role'] === 'penjual' && !seller_has_order($user['id'], $id)) {
        api_fail('Pesanan ini tidak memuat produk Anda.', 403);
    }
    return $o;
}

if ($method === 'GET') {
    if ($id) {
        $o = pesanan_akses($id, $user);
        $items = order_items($id);
        if ($user['role'] === 'penjual') {
            $items = array_values(array_filter($items, function ($i) use ($user) { return (int) $i['penjual_id'] === $user['id']; }));
        }
        api_ok(pesanan_out($o, $items));
    }
    [$page, $limit] = api_page_params(10, 50);
    $where = ['1 = 1'];
    $params = [];
    if ($user['role'] === 'pembeli') {
        $where[] = 'o.user_id = ?';
        $params[] = $user['id'];
    } elseif ($user['role'] === 'penjual') {
        $where[] = 'EXISTS (SELECT 1 FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id WHERE d.pesanan_id = o.id AND p.penjual_id = ?)';
        $params[] = $user['id'];
    }
    $status = (string) ($_GET['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, STATUS_LIST, true)) {
            api_fail('Status tidak valid. Pilihan: ' . implode(', ', STATUS_LIST) . '.', 422);
        }
        $where[] = 'o.status_pesanan = ?';
        $params[] = $status;
    }
    $from = ' FROM pesanan o JOIN users b ON b.id = o.user_id WHERE ' . implode(' AND ', $where);
    $total = (int) db_val('SELECT COUNT(*)' . $from, $params);
    [$page, $pages, $offset] = paginate($total, $limit, $page);
    $rows = db_all('SELECT o.*, b.nama AS pembeli_nama' . $from . ' ORDER BY o.tanggal_pesanan DESC, o.id DESC LIMIT ? OFFSET ?', array_merge($params, [$limit, $offset]));
    api_ok(array_map('pesanan_out', $rows), 'OK', 200, ['total' => $total, 'page' => $page, 'pages' => $pages, 'limit' => $limit]);
}

if ($method === 'POST') {
    if ($user['role'] !== 'pembeli') {
        api_fail('Hanya pembeli yang dapat membuat pesanan.', 403);
    }
    $in = api_input();
    $nama = trim((string) ($in['nama_penerima'] ?? ''));
    $telp = trim((string) ($in['telepon'] ?? ''));
    $alamat = trim((string) ($in['alamat'] ?? ''));
    $err = [];
    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100 || strpos($nama, '|') !== false) {
        $err['nama_penerima'] = 'Nama penerima harus 3–100 karakter.';
    }
    if (!preg_match('/^\+?[0-9][0-9\-\s]{8,16}$/', $telp)) {
        $err['telepon'] = 'Nomor telepon tidak valid.';
    }
    if (mb_strlen($alamat) < 10 || mb_strlen($alamat) > 500) {
        $err['alamat'] = 'Alamat harus 10–500 karakter.';
    }
    if ($err) {
        api_fail('Validasi gagal.', 422, $err);
    }
    $res = create_order($user['id'], $nama, preg_replace('/[\s\-]/', '', $telp), $alamat);
    if (!$res['ok']) {
        api_fail($res['msg'], $res['code'] === 'stok' ? 409 : ($res['code'] === 'kosong' ? 422 : 500));
    }
    $o = pesanan_akses($res['id'], $user);
    api_ok(pesanan_out($o, order_items($res['id'])), $res['msg'], 201);
}

if (!$id) {
    api_fail('Sertakan parameter ?id=.', 400);
}
$o = pesanan_akses($id, $user);

if ($method === 'PUT') {
    $status = (string) (api_input()['status'] ?? '');
    if (!in_array($status, STATUS_LIST, true)) {
        api_fail('Status tidak valid. Pilihan: ' . implode(', ', STATUS_LIST) . '.', 422);
    }
    [$ok, $msg] = update_order_status($id, $status, $user['role']);
    if (!$ok) {
        api_fail($msg, 422);
    }
    api_ok(pesanan_out(pesanan_akses($id, $user), order_items($id)), $msg);
}

// DELETE — hanya admin, hanya pesanan yang sudah dibatalkan (stok sudah dikembalikan saat pembatalan)
if ($user['role'] !== 'admin') {
    api_fail('Hanya admin yang dapat menghapus pesanan.', 403);
}
if ($o['status_pesanan'] !== 'dibatalkan') {
    api_fail('Hanya pesanan berstatus "dibatalkan" yang dapat dihapus.', 409);
}
db_exec('DELETE FROM pesanan WHERE id = ?', [$id]);
api_ok(null, 'Pesanan berhasil dihapus.');
