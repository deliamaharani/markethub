<?php
/** Ubah jumlah produk di keranjang (POST: id, aksi = tambah | kurang | set, jumlah). */
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['pembeli'];
require __DIR__ . '/../includes/auth-check.php';
require_post('pembeli/keranjang.php');

$row = db_row('SELECT k.id, k.jumlah, p.stok, p.nama_produk FROM keranjang k JOIN produk p ON p.id = k.produk_id
               WHERE k.id = ? AND k.user_id = ?', [(int) post('id'), $authUser['id']]);
if (!$row) {
    flash('danger', 'Item keranjang tidak ditemukan.');
    redirect('pembeli/keranjang.php');
}

$aksi = post('aksi');
$jumlah = (int) $row['jumlah'];
if ($aksi === 'tambah') {
    $jumlah++;
} elseif ($aksi === 'kurang') {
    $jumlah--;
} elseif ($aksi === 'set') {
    $jumlah = (int) post('jumlah');
}

if ($jumlah < 1) {
    $jumlah = 1;
}
if ($jumlah > (int) $row['stok']) {
    flash('warning', 'Stok "' . $row['nama_produk'] . '" hanya tersedia ' . (int) $row['stok'] . ' pcs.');
    $jumlah = max(1, (int) $row['stok']);
}
db_exec('UPDATE keranjang SET jumlah = ? WHERE id = ? AND user_id = ?', [$jumlah, (int) $row['id'], $authUser['id']]);
redirect('pembeli/keranjang.php');
