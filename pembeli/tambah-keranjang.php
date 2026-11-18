<?php
/** Tambah produk ke keranjang (POST). Wajib login; jika belum, tindakan disimpan lalu dilanjutkan setelah login. */
require_once __DIR__ . '/../config/app.php';
require_post('beranda.php');

$produkId = (int) post('produk_id');
$qty = max(1, (int) post('jumlah', '1'));
$u = current_user();

if (!$u) {
    $_SESSION['pending_cart'] = ['produk_id' => $produkId, 'jumlah' => $qty];
    $_SESSION['next'] = 'detail-produk.php?id=' . $produkId;
    flash('warning', 'Silakan login terlebih dahulu. Produk akan otomatis masuk ke keranjang setelah Anda login.');
    redirect('auth/login.php');
}
if ($u['role'] !== 'pembeli') {
    flash('danger', 'Hanya akun pembeli yang dapat menggunakan keranjang belanja.');
    redirect(role_home($u['role']));
}

[$ok, $msg] = cart_add($u['id'], $produkId, $qty);
flash($ok ? 'success' : 'danger', $msg);
redirect($ok ? 'pembeli/keranjang.php' : 'detail-produk.php?id=' . $produkId);
