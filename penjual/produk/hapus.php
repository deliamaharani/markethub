<?php
/** Hapus produk milik sendiri (POST). Kepemilikan diperiksa di server. */
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['penjual'];
require __DIR__ . '/../../includes/auth-check.php';
require_post('penjual/produk/index.php');
[$ok, $msg] = produk_hapus((int) post('id'), $authUser['id']);
flash($ok ? 'success' : 'danger', $msg);
redirect('penjual/produk/index.php');
