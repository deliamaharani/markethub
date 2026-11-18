<?php
define('API_REQUEST', true);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/middleware/auth.php';

$method = api_methods(['GET', 'POST', 'PUT', 'DELETE']);
$id = (int) ($_GET['id'] ?? 0);

function produk_out(array $p)
{
    return [
        'id' => (int) $p['id'],
        'nama_produk' => $p['nama_produk'],
        'deskripsi' => $p['deskripsi'],
        'harga' => (int) $p['harga'],
        'stok' => (int) $p['stok'],
        'gambar_url' => $p['gambar'] ? (((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . url('uploads/produk/' . $p['gambar'])) : null,
        'kategori' => ['id' => (int) $p['kategori_id'], 'nama_kategori' => $p['nama_kategori'], 'slug' => $p['kategori_slug']],
        'penjual' => ['id' => (int) $p['penjual_id'], 'nama' => $p['penjual_nama']],
        'created_at' => $p['created_at'],
        'updated_at' => $p['updated_at'],
    ];
}

if ($method === 'GET') {
    if ($id) {
        $p = produk_find($id);
        ($p && $p['penjual_status'] === 'aktif') ? api_ok(produk_out($p)) : api_fail('Produk tidak ditemukan.', 404);
    }
    [$page, $limit] = api_page_params(12, 50);
    $res = catalog([
        'q' => trim((string) ($_GET['q'] ?? '')),
        'kategori_id' => (int) ($_GET['kategori_id'] ?? 0),
        'urut' => (string) ($_GET['urut'] ?? 'terbaru'),
        'page' => $page,
    ], $limit);
    api_ok(array_map('produk_out', $res['rows']), 'OK', 200, ['total' => $res['total'], 'page' => $res['page'], 'pages' => $res['pages'], 'limit' => $limit]);
}

$user = api_require(['admin', 'penjual']);

if ($method === 'POST') {
    $in = api_input();
    [$data, $err] = produk_validate($in);
    $penjualId = $user['id'];
    if ($user['role'] === 'admin' && !empty($in['penjual_id'])) {
        $penjualId = (int) $in['penjual_id'];
        if (!db_val("SELECT id FROM users WHERE id = ? AND role IN ('penjual','admin') AND status = 'aktif'", [$penjualId])) {
            $err['penjual_id'] = 'Penjual tidak valid.';
        }
    }
    if ($err) {
        api_fail('Validasi gagal.', 422, $err);
    }
    db_exec('INSERT INTO produk (kategori_id, penjual_id, nama_produk, deskripsi, harga, stok) VALUES (?, ?, ?, ?, ?, ?)',
        [$data['kategori_id'], $penjualId, $data['nama_produk'], $data['deskripsi'], $data['harga'], $data['stok']]);
    api_ok(produk_out(produk_find(db_id())), 'Produk berhasil dibuat.', 201);
}

$p = $id ? produk_find($id) : null;
if (!$p) {
    api_fail('Produk tidak ditemukan. Sertakan parameter ?id=.', 404);
}
if ($user['role'] === 'penjual' && (int) $p['penjual_id'] !== $user['id']) {
    api_fail('Produk ini bukan milik Anda.', 403);
}

if ($method === 'PUT') {
    $in = array_merge(['nama_produk' => $p['nama_produk'], 'kategori_id' => $p['kategori_id'], 'harga' => $p['harga'],
        'stok' => $p['stok'], 'deskripsi' => $p['deskripsi']], api_input());
    [$data, $err] = produk_validate($in);
    if ($err) {
        api_fail('Validasi gagal.', 422, $err);
    }
    db_exec('UPDATE produk SET kategori_id = ?, nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, updated_at = NOW() WHERE id = ?',
        [$data['kategori_id'], $data['nama_produk'], $data['deskripsi'], $data['harga'], $data['stok'], $id]);
    api_ok(produk_out(produk_find($id)), 'Produk berhasil diperbarui.');
}

// DELETE
[$ok, $msg, $code] = produk_hapus($id, $user['role'] === 'penjual' ? $user['id'] : null);
$ok ? api_ok(null, $msg) : api_fail($msg, $code);
