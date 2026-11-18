<?php
/**
 * Penjaga akses halaman terproteksi.
 * Pemakaian di awal halaman (setelah require config/app.php):
 *     $requiredRoles = ['admin'];
 *     require __DIR__ . '/../includes/auth-check.php';
 * Hasilnya: variabel $authUser berisi data pengguna yang sedang login.
 */
$authUser = require_role($requiredRoles ?? null);
