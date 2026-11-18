<?php
/** Aktifkan / nonaktifkan akun pengguna (POST). Admin tidak dapat menonaktifkan akunnya sendiri. */
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';
require_post('admin/users/index.php');

$id = (int) post('id');
$u = db_row('SELECT id, nama, status FROM users WHERE id = ?', [$id]);
if (!$u) {
    flash('danger', 'Pengguna tidak ditemukan.');
} elseif ($id === $authUser['id']) {
    flash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
} else {
    $baru = $u['status'] === 'aktif' ? 'nonaktif' : 'aktif';
    db_exec('UPDATE users SET status = ? WHERE id = ?', [$baru, $id]);
    flash('success', 'Akun "' . $u['nama'] . '" sekarang ' . $baru . '.');
}
redirect(post('kembali') === 'detail' && $u ? 'admin/users/detail.php?id=' . $id : 'admin/users/index.php');
