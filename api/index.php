<?php
/** Dokumentasi ringkas & status API MarketHub (JSON). */
define('API_REQUEST', true);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/config/response.php';
require_once __DIR__ . '/middleware/auth.php';
api_methods(['GET']);

$u = api_user();
$base = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/api';
api_ok([
    'nama' => 'MarketHub API',
    'versi' => '1.0',
    'base_url' => $base,
    'format' => 'JSON (UTF-8)',
    'autentikasi' => [
        'session' => 'Login via web lalu kirim header X-CSRF-Token (nilai di field csrf_token) untuk POST/PUT/DELETE.',
        'basic' => 'HTTP Basic Auth: email:password (cocok untuk curl/Postman).',
    ],
    'pengguna' => $u ? ['id' => $u['id'], 'nama' => $u['nama'], 'role' => $u['role']] : null,
    'csrf_token' => ($u && ($GLOBALS['api_auth_mode'] ?? '') === 'session') ? csrf_token() : null,
    'endpoint' => [
        'kategori' => [
            'GET    /api/kategori.php            (publik) daftar kategori',
            'GET    /api/kategori.php?id=1       (publik) satu kategori',
            'POST   /api/kategori.php            (admin) body: nama_kategori, slug?',
            'PUT    /api/kategori.php?id=1       (admin) body: nama_kategori, slug',
            'DELETE /api/kategori.php?id=1       (admin) ditolak 409 jika masih ada produk',
        ],
        'produk' => [
            'GET    /api/produk.php?q=&kategori_id=&urut=&page=&limit=  (publik)',
            'GET    /api/produk.php?id=1         (publik)',
            'POST   /api/produk.php              (admin, penjual) body: nama_produk, kategori_id, harga, stok, deskripsi, penjual_id? (admin)',
            'PUT    /api/produk.php?id=1         (admin, penjual pemilik) body: field yang diubah',
            'DELETE /api/produk.php?id=1         (admin, penjual pemilik) ditolak 409 jika punya histori transaksi',
        ],
        'pesanan' => [
            'GET    /api/pesanan.php?status=&page=&limit=  (login) pembeli: milik sendiri; penjual: yang memuat produknya; admin: semua',
            'GET    /api/pesanan.php?id=1        (login, sesuai hak akses)',
            'POST   /api/pesanan.php             (pembeli) body: nama_penerima, telepon, alamat -> checkout isi keranjang (transaksi, status pending)',
            'PUT    /api/pesanan.php?id=1        (login) body: status -> sesuai aturan alur status dan role',
            'DELETE /api/pesanan.php?id=1         (admin) hanya pesanan berstatus dibatalkan',
        ],
    ],
    'status_pesanan' => STATUS_LIST,
], 'Selamat datang di MarketHub API.');
