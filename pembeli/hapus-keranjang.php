<?php
/** Hapus satu produk dari keranjang (POST: id) atau kosongkan seluruh keranjang (POST: semua=1). */
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['pembeli'];
require __DIR__ . '/../includes/auth-check.php';
require_post('pembeli/keranjang.php');

if (post('semua') === '1') {
    db_exec('DELETE FROM keranjang WHERE user_id = ?', [$authUser['id']]);
    flash('success', 'Keranjang berhasil dikosongkan.');
} elseif (db_exec('DELETE FROM keranjang WHERE id = ? AND user_id = ?', [(int) post('id'), $authUser['id']]) > 0) {
    flash('success', 'Produk dihapus dari keranjang.');
} else {
    flash('danger', 'Item keranjang tidak ditemukan.');
}
redirect('pembeli/keranjang.php');
