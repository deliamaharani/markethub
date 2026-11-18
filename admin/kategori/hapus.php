<?php
/** Hapus kategori (POST). Kategori yang masih memiliki produk tidak boleh dihapus. */
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';
require_post('admin/kategori/index.php');

$id = (int) post('id');
$k = db_row('SELECT nama_kategori FROM kategori WHERE id = ?', [$id]);
if (!$k) {
    flash('danger', 'Kategori tidak ditemukan.');
} elseif ((int) db_val('SELECT COUNT(*) FROM produk WHERE kategori_id = ?', [$id]) > 0) {
    flash('danger', 'Kategori "' . $k['nama_kategori'] . '" masih memiliki produk sehingga tidak dapat dihapus.');
} else {
    db_exec('DELETE FROM kategori WHERE id = ?', [$id]);
    flash('success', 'Kategori "' . $k['nama_kategori'] . '" berhasil dihapus.');
}
redirect('admin/kategori/index.php');
