<?php
/**
 * Fungsi bersama MarketHub: database, keamanan, tampilan, dan logika bisnis.
 */

const STATUS_LIST = ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

/* ============================ DATABASE (prepared statements) ============================ */

function db_stmt($sql, array $params = [])
{
    global $conn;
    $st = $conn->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    return $st;
}

function db_all($sql, array $params = [])
{
    $st = db_stmt($sql, $params);
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}

function db_row($sql, array $params = [])
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

function db_val($sql, array $params = [])
{
    $row = db_row($sql, $params);
    return $row ? reset($row) : null;
}

/** Eksekusi INSERT/UPDATE/DELETE, mengembalikan jumlah baris terpengaruh. */
function db_exec($sql, array $params = [])
{
    $st = db_stmt($sql, $params);
    $n = $st->affected_rows;
    $st->close();
    return $n;
}

function db_id()
{
    global $conn;
    return (int) $conn->insert_id;
}

/* ============================ UMUM / KEAMANAN ============================ */

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset($path)
{
    $file = ROOT_PATH . '/assets/' . ltrim($path, '/');
    return url('assets/' . ltrim($path, '/')) . '?v=' . (is_file($file) ? filemtime($file) : 1);
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function flash($type, $msg)
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pull()
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid()
{
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && $t !== '' && hash_equals(csrf_token(), $t);
}

/** Pastikan request POST dengan CSRF token valid; jika tidak, kembali ke halaman $back. */
function require_post($back = 'beranda.php')
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirect($back);
    }
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid atau sudah kedaluwarsa. Silakan ulangi.');
        redirect($back);
    }
}

function post($key, $default = '')
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get($key, $default = '')
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/* ============================ FORMAT ============================ */

function rupiah($n)
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

function rupiah_short($n)
{
    $n = (float) $n;
    return $n >= 1000000 ? 'Rp ' . number_format($n / 1000000, 2, ',', '.') . ' jt' : rupiah($n);
}

function tgl($d, $withTime = false)
{
    if (!$d) {
        return '-';
    }
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $bln[(int) date('n', $t)] . ' ' . date('Y', $t) . ($withTime ? ', ' . date('H:i', $t) : '');
}

function tanggal_panjang($t = null)
{
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bln = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = $t ?? time();
    return $hari[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . $bln[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function slugify($s)
{
    $s = strtolower(trim((string) $s));
    return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
}

function nomor_pesanan($id, $tanggal)
{
    return 'MH-' . date('ymd', strtotime($tanggal)) . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function status_badge($s)
{
    $s = in_array($s, STATUS_LIST, true) ? $s : 'pending';
    return '<span class="st st-' . $s . '">' . e($s) . '</span>';
}

function role_label($r)
{
    return ['admin' => 'Administrator', 'penjual' => 'Penjual', 'pembeli' => 'Pembeli'][$r] ?? $r;
}

function initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $i = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    return $i !== '' ? $i : 'U';
}

/** Alamat disimpan sebagai "Nama|Telepon\nAlamat lengkap" pada kolom alamat_pengiriman. */
function parse_alamat($s)
{
    if (preg_match('/^([^|\n]*)\|([^\n]*)\n(.*)$/s', (string) $s, $m)) {
        return ['nama' => $m[1], 'telp' => $m[2], 'alamat' => $m[3]];
    }
    return ['nama' => '', 'telp' => '', 'alamat' => (string) $s];
}

/* ============================ PAGINASI ============================ */

function paginate($total, $per, $page)
{
    $pages = max(1, (int) ceil($total / $per));
    $page = min(max(1, (int) $page), $pages);
    return [$page, $pages, ($page - 1) * $per];
}

function page_url(array $override = [])
{
    $q = array_merge($_GET, $override);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return '?' . http_build_query($q);
}

function pagination_html($page, $pages)
{
    if ($pages <= 1) {
        return '';
    }
    $item = function ($p, $label, $active = false, $disabled = false) {
        $cls = 'page-item' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '');
        $href = $disabled ? '#' : e(page_url(['page' => $p]));
        return '<li class="' . $cls . '"><a class="page-link" href="' . $href . '">' . $label . '</a></li>';
    };
    $h = '<nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0">';
    $h .= $item($page - 1, '&lsaquo;', false, $page <= 1);
    $last = 0;
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === 1 || $i === $pages || abs($i - $page) <= 1) {
            if ($last && $i - $last > 1) {
                $h .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
            $h .= $item($i, (string) $i, $i === $page);
            $last = $i;
        }
    }
    $h .= $item($page + 1, '&rsaquo;', false, $page >= $pages);
    return $h . '</ul></nav>';
}

/* ============================ TAMPILAN ============================ */

function logo_svg($size = 36, $inverse = false)
{
    $bg = $inverse ? '#FFFFFF' : '#0F766E';
    $fg = $inverse ? '#0F766E' : '#FFFFFF';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 36 36" aria-hidden="true"><rect width="36" height="36" rx="10" fill="' . $bg . '"/>'
        . '<path d="M8 14l2.2-5.5h15.6L28 14v1.5a3 3 0 01-6 0 3 3 0 01-4 0 3 3 0 01-4 0 3 3 0 01-6 0z" fill="' . $fg . '"/>'
        . '<path d="M10.5 20v7h15v-7" stroke="' . $fg . '" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<rect x="15" y="22" width="6" height="5" rx="1" fill="#F59E0B"/></svg>';
}

/** Warna dan ikon placeholder gambar produk berdasarkan kategori. */
function kategori_style($kategoriId)
{
    $set = [
        ['#E8E1F5', '#4C3A8A', 'bi-handbag'],
        ['#FBE9C8', '#7A4A06', 'bi-cup-hot'],
        ['#D9F2EE', '#115E59', 'bi-basket2'],
        ['#FADBD2', '#8A2F17', 'bi-house-heart'],
        ['#DDEBD0', '#2F5A14', 'bi-flower1'],
        ['#E2E8F0', '#334155', 'bi-gem'],
    ];
    return $set[(((int) $kategoriId) - 1 + count($set) * 10) % count($set)];
}

/** Gambar produk (atau placeholder rapi bila belum ada foto). $cls: img-card | img-big | img-thumb | img-mini */
function produk_img(array $p, $cls = 'img-card')
{
    [$bg, $fg, $icon] = kategori_style($p['kategori_id'] ?? 0);
    $file = $p['gambar'] ?? '';
    if ($file && is_file(UPLOAD_DIR . '/' . basename($file))) {
        $inner = '<img src="' . e(url('uploads/produk/' . basename($file))) . '" alt="' . e($p['nama_produk'] ?? 'Produk') . '" loading="lazy">';
    } else {
        $inner = '<i class="bi ' . $icon . '" aria-hidden="true"></i>';
    }
    return '<span class="mh-img ' . $cls . '" style="background:' . $bg . ';color:' . $fg . '">' . $inner . '</span>';
}

function render_product_card(array $p)
{
    $stok = (int) $p['stok'];
    $tag = $stok <= 0 ? '<span class="tag tag-out">Habis</span>' : ($stok <= 5 ? '<span class="tag">Stok terbatas</span>' : '');
    $link = url('detail-produk.php?id=' . (int) $p['id']);
    return '<div class="col"><article class="pc h-100">'
        . '<a class="pi-wrap" href="' . e($link) . '" aria-label="' . e($p['nama_produk']) . '">' . produk_img($p, 'img-card') . $tag . '</a>'
        . '<div class="pb"><span class="pk">' . e($p['nama_kategori']) . '</span>'
        . '<h3 class="pn">' . e($p['nama_produk']) . '</h3>'
        . '<span class="pr">' . rupiah($p['harga']) . '</span>'
        . '<span class="ps">' . e($p['penjual_nama']) . ' · Stok ' . $stok . '</span>'
        . '<a class="btn btn-outline-primary btn-sm w-100 mt-auto" href="' . e($link) . '">Lihat detail</a></div></article></div>';
}

/* ============================ KATALOG / PRODUK ============================ */

function kategori_all()
{
    return db_all('SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.kategori_id = k.id) AS jml FROM kategori k ORDER BY k.id');
}

/** Pencarian katalog publik: filter q, kategori_id, urut, page. Produk milik akun nonaktif disembunyikan. */
function catalog(array $f, $per = 12)
{
    $where = ["u.status = 'aktif'"];
    $params = [];
    if (!empty($f['q'])) {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(p.nama_produk LIKE ? OR p.deskripsi LIKE ? OR u.nama LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if (!empty($f['kategori_id'])) {
        $where[] = 'p.kategori_id = ?';
        $params[] = (int) $f['kategori_id'];
    }
    $orders = [
        'terbaru'  => 'p.created_at DESC, p.id DESC',
        'termurah' => 'p.harga ASC, p.id DESC',
        'termahal' => 'p.harga DESC, p.id DESC',
        'nama'     => 'p.nama_produk ASC',
    ];
    $order = $orders[$f['urut'] ?? 'terbaru'] ?? $orders['terbaru'];
    $from = ' FROM produk p JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.penjual_id WHERE ' . implode(' AND ', $where);
    $total = (int) db_val('SELECT COUNT(*)' . $from, $params);
    [$page, $pages, $offset] = paginate($total, $per, (int) ($f['page'] ?? 1));
    $rows = db_all('SELECT p.*, k.nama_kategori, k.slug AS kategori_slug, u.nama AS penjual_nama' . $from
        . ' ORDER BY ' . $order . ' LIMIT ? OFFSET ?', array_merge($params, [$per, $offset]));
    return compact('rows', 'total', 'page', 'pages');
}

function produk_find($id)
{
    return db_row('SELECT p.*, k.nama_kategori, k.slug AS kategori_slug, u.nama AS penjual_nama, u.status AS penjual_status
        FROM produk p JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.penjual_id WHERE p.id = ?', [(int) $id]);
}

/** Validasi input produk (dipakai form web dan API). Mengembalikan [data, errors]. */
function produk_validate(array $in)
{
    $err = [];
    $nama = trim((string) ($in['nama_produk'] ?? ''));
    $desk = trim((string) ($in['deskripsi'] ?? ''));
    $kat  = filter_var($in['kategori_id'] ?? null, FILTER_VALIDATE_INT);
    $harga = filter_var(str_replace(['.', ','], '', (string) ($in['harga'] ?? '')), FILTER_VALIDATE_INT);
    $stok = filter_var($in['stok'] ?? null, FILTER_VALIDATE_INT);

    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 150) {
        $err['nama_produk'] = 'Nama produk harus 3–150 karakter.';
    }
    if ($kat === false || !db_val('SELECT id FROM kategori WHERE id = ?', [(int) $kat])) {
        $err['kategori_id'] = 'Pilih kategori yang valid.';
    }
    if ($harga === false || $harga < 100 || $harga > 999999999) {
        $err['harga'] = 'Harga harus berupa angka minimal Rp 100.';
    }
    if ($stok === false || $stok < 0 || $stok > 1000000) {
        $err['stok'] = 'Stok harus berupa angka 0 atau lebih.';
    }
    if (mb_strlen($desk) < 10 || mb_strlen($desk) > 3000) {
        $err['deskripsi'] = 'Deskripsi harus 10–3000 karakter.';
    }
    return [[
        'nama_produk' => $nama, 'deskripsi' => $desk, 'kategori_id' => (int) $kat,
        'harga' => (int) $harga, 'stok' => (int) $stok,
    ], $err];
}

/** Upload gambar produk. Mengembalikan [namaFile|null, error|null]. Tanpa file => [null, null]. */
function upload_produk_image($file)
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return [null, 'Ukuran gambar maksimal ' . (int) (UPLOAD_MAX_BYTES / 1048576) . ' MB.'];
        }
        return [null, 'Upload gambar gagal (kode ' . (int) $file['error'] . '). Silakan coba lagi.'];
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return [null, 'Ukuran gambar maksimal 2 MB.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || @getimagesize($file['tmp_name']) === false) {
        return [null, 'Format gambar harus JPG, PNG, atau WEBP.'];
    }
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        return [null, 'Gambar tidak dapat disimpan di folder uploads/produk.'];
    }
    return [$name, null];
}

function delete_produk_image($name)
{
    if ($name) {
        $f = UPLOAD_DIR . '/' . basename($name);
        if (is_file($f)) {
            @unlink($f);
        }
    }
}

/** Produk yang sudah memiliki histori transaksi tidak boleh dihapus. */
function produk_has_history($id)
{
    return (int) db_val('SELECT COUNT(*) FROM detail_pesanan WHERE produk_id = ?', [(int) $id]) > 0;
}

/* ============================ KERANJANG ============================ */

function cart_count($uid)
{
    return (int) db_val('SELECT COUNT(*) FROM keranjang WHERE user_id = ?', [(int) $uid]);
}

function cart_items($uid)
{
    return db_all('SELECT k.id AS kid, k.jumlah, p.id AS produk_id, p.nama_produk, p.harga, p.stok, p.gambar, p.kategori_id,
            u.nama AS penjual_nama, (k.jumlah * p.harga) AS subtotal
        FROM keranjang k JOIN produk p ON p.id = k.produk_id JOIN users u ON u.id = p.penjual_id
        WHERE k.user_id = ? ORDER BY k.id DESC', [(int) $uid]);
}

/** Tambahkan produk ke keranjang. Mengembalikan [ok, pesan]. */
function cart_add($uid, $produkId, $qty)
{
    $p = db_row("SELECT p.id, p.nama_produk, p.stok FROM produk p JOIN users u ON u.id = p.penjual_id
                 WHERE p.id = ? AND u.status = 'aktif'", [(int) $produkId]);
    if (!$p) {
        return [false, 'Produk tidak ditemukan.'];
    }
    if ($qty < 1) {
        return [false, 'Jumlah pembelian minimal 1.'];
    }
    if ((int) $p['stok'] <= 0) {
        return [false, 'Stok "' . $p['nama_produk'] . '" sedang habis.'];
    }
    $ada = (int) db_val('SELECT jumlah FROM keranjang WHERE user_id = ? AND produk_id = ?', [(int) $uid, (int) $produkId]);
    if ($ada + $qty > (int) $p['stok']) {
        return [false, 'Jumlah melebihi stok. Stok tersedia ' . (int) $p['stok'] . ($ada ? ', di keranjang sudah ada ' . $ada : '') . '.'];
    }
    db_exec('INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE jumlah = jumlah + VALUES(jumlah)', [(int) $uid, (int) $produkId, $qty]);
    return [true, '"' . $p['nama_produk'] . '" ditambahkan ke keranjang.'];
}

/* ============================ PESANAN ============================ */

/** Status berikutnya yang boleh dipilih oleh $role dari status $current. */
function allowed_next_status($current, $role)
{
    $map = [
        'pending'  => ['diproses', 'dibatalkan'],
        'diproses' => ['dikirim', 'dibatalkan'],
        'dikirim'  => ['selesai'],
    ];
    $next = $map[$current] ?? [];
    if ($role === 'pembeli') {
        return array_values(array_intersect($next, $current === 'pending' ? ['dibatalkan'] : ($current === 'dikirim' ? ['selesai'] : [])));
    }
    if ($role === 'penjual') {
        return array_values(array_diff($next, ['selesai']));
    }
    return $role === 'admin' ? $next : [];
}

function order_items($orderId)
{
    return db_all('SELECT d.*, p.nama_produk, p.gambar, p.kategori_id, p.penjual_id, u.nama AS penjual_nama
        FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id JOIN users u ON u.id = p.penjual_id
        WHERE d.pesanan_id = ? ORDER BY d.id', [(int) $orderId]);
}

function seller_has_order($sellerId, $orderId)
{
    return (bool) db_val('SELECT 1 FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id
        WHERE d.pesanan_id = ? AND p.penjual_id = ? LIMIT 1', [(int) $orderId, (int) $sellerId]);
}

/**
 * Checkout dengan transaksi database (sesuai flowchart):
 * BEGIN -> validasi ulang stok & harga -> simpan pesanan -> simpan detail -> kurangi stok
 * -> kosongkan keranjang -> COMMIT; jika gagal -> ROLLBACK.
 */
function create_order($uid, $nama, $telp, $alamat)
{
    global $conn;
    try {
        $conn->begin_transaction();
        $items = db_all('SELECT k.jumlah, p.id AS pid, p.nama_produk, p.harga, p.stok
            FROM keranjang k JOIN produk p ON p.id = k.produk_id
            WHERE k.user_id = ? ORDER BY k.id FOR UPDATE', [(int) $uid]);
        if (!$items) {
            $conn->rollback();
            return ['ok' => false, 'code' => 'kosong', 'msg' => 'Keranjang Anda kosong.'];
        }
        $total = 0;
        foreach ($items as $it) {
            if ((int) $it['stok'] < (int) $it['jumlah']) {
                $conn->rollback();
                return ['ok' => false, 'code' => 'stok',
                    'msg' => 'Stok "' . $it['nama_produk'] . '" tidak mencukupi (tersisa ' . (int) $it['stok'] . '). Ubah jumlah di keranjang.'];
            }
            $total += (int) $it['harga'] * (int) $it['jumlah'];
        }
        db_exec("INSERT INTO pesanan (user_id, total_harga, status_pesanan, alamat_pengiriman) VALUES (?, ?, 'pending', ?)",
            [(int) $uid, $total, $nama . '|' . $telp . "\n" . $alamat]);
        $oid = db_id();
        foreach ($items as $it) {
            $harga = (int) $it['harga'];
            $qty = (int) $it['jumlah'];
            db_exec('INSERT INTO detail_pesanan (pesanan_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)',
                [$oid, (int) $it['pid'], $qty, $harga, $harga * $qty]);
            // Pengurangan stok mengacu pada id dan stok produk (bukan harga).
            if (db_exec('UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?', [$qty, (int) $it['pid'], $qty]) !== 1) {
                throw new RuntimeException('Stok berubah saat transaksi berlangsung.');
            }
        }
        db_exec('DELETE FROM keranjang WHERE user_id = ?', [(int) $uid]);
        $conn->commit();
        return ['ok' => true, 'id' => $oid, 'msg' => 'Pesanan berhasil dibuat dengan status pending.'];
    } catch (Throwable $e) {
        $conn->rollback(); // ROLLBACK otomatis membatalkan seluruh perubahan, termasuk pengurangan stok.
        error_log('create_order gagal: ' . $e->getMessage());
        return ['ok' => false, 'code' => 'error', 'msg' => 'Pesanan gagal diproses dan seluruh perubahan dibatalkan. Silakan coba lagi.'];
    }
}

/** Ubah status pesanan sesuai hak role. Pembatalan mengembalikan stok produk (setelah pesanan sempat COMMIT). */
function update_order_status($orderId, $new, $role)
{
    global $conn;
    try {
        $conn->begin_transaction();
        $o = db_row('SELECT id, status_pesanan FROM pesanan WHERE id = ? FOR UPDATE', [(int) $orderId]);
        if (!$o) {
            $conn->rollback();
            return [false, 'Pesanan tidak ditemukan.'];
        }
        if (!in_array($new, allowed_next_status($o['status_pesanan'], $role), true)) {
            $conn->rollback();
            return [false, 'Perubahan status dari "' . $o['status_pesanan'] . '" ke "' . $new . '" tidak diizinkan.'];
        }
        if ($new === 'dibatalkan') {
            foreach (db_all('SELECT produk_id, jumlah FROM detail_pesanan WHERE pesanan_id = ?', [(int) $orderId]) as $d) {
                db_exec('UPDATE produk SET stok = stok + ? WHERE id = ?', [(int) $d['jumlah'], (int) $d['produk_id']]);
            }
        }
        db_exec('UPDATE pesanan SET status_pesanan = ? WHERE id = ?', [$new, (int) $orderId]);
        $conn->commit();
        return [true, 'Status pesanan diperbarui menjadi "' . $new . '".'];
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('update_order_status gagal: ' . $e->getMessage());
        return [false, 'Status gagal diperbarui karena kesalahan database.'];
    }
}

/** Hapus produk (dipakai admin, penjual, dan API). $penjualId != null => hanya boleh menghapus milik penjual itu. */
function produk_hapus($id, $penjualId = null)
{
    $p = db_row('SELECT id, penjual_id, gambar, nama_produk FROM produk WHERE id = ?', [(int) $id]);
    if (!$p || ($penjualId !== null && (int) $p['penjual_id'] !== (int) $penjualId)) {
        return [false, 'Produk tidak ditemukan atau bukan milik Anda.', 404];
    }
    if (produk_has_history($id)) {
        return [false, 'Produk "' . $p['nama_produk'] . '" tidak dapat dihapus karena sudah memiliki histori transaksi. Ubah stok menjadi 0 jika tidak ingin dijual lagi.', 409];
    }
    try {
        db_exec('DELETE FROM produk WHERE id = ?', [(int) $id]);
    } catch (mysqli_sql_exception $e) {
        return [false, 'Produk tidak dapat dihapus karena masih dipakai data lain.', 409];
    }
    delete_produk_image($p['gambar']);
    return [true, 'Produk "' . $p['nama_produk'] . '" berhasil dihapus.', 200];
}

/** Badge stok: habis / terbatas / tersedia. */
function stok_badge($stok)
{
    $stok = (int) $stok;
    $cls = $stok <= 0 ? 'st-dibatalkan' : ($stok <= 5 ? 'st-pending' : 'st-selesai');
    return '<span class="st ' . $cls . '">' . ($stok <= 0 ? 'Habis' : $stok) . '</span>';
}
