<?php
/** Hapus produk (POST). Produk dengan histori transaksi tidak boleh dihapus. */
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';
require_post('admin/produk/index.php');
[$ok, $msg] = produk_hapus((int) post('id'));
flash($ok ? 'success' : 'danger', $msg);
redirect('admin/produk/index.php');
