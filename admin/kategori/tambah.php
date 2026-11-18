<?php
/** Simpan kategori baru (POST dari form di halaman Kelola kategori). */
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';
require_post('admin/kategori/index.php');

$nama = post('nama');
$slug = slugify(post('slug') !== '' ? post('slug') : $nama);
$err = [];
if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
    $err['nama'] = 'Nama kategori harus 3–100 karakter.';
}
if ($slug === '' || strlen($slug) > 120) {
    $err['slug'] = 'Slug tidak valid.';
} elseif (db_val('SELECT id FROM kategori WHERE slug = ?', [$slug])) {
    $err['slug'] = 'Slug sudah dipakai kategori lain.';
}
if ($err) {
    $_SESSION['old_kategori'] = ['nama' => $nama, 'slug' => post('slug')];
    $_SESSION['err_kategori'] = $err;
    flash('danger', implode(' ', $err));
    redirect('admin/kategori/index.php#form-kategori');
}
db_exec('INSERT INTO kategori (nama_kategori, slug) VALUES (?, ?)', [$nama, $slug]);
flash('success', 'Kategori "' . $nama . '" berhasil ditambahkan.');
redirect('admin/kategori/index.php');
