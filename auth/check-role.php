<?php
/** Pemeriksaan login dan role di sisi server. */

/** Wajib login. Jika belum, simpan tujuan awal lalu arahkan ke halaman login. */
function require_login()
{
    $user = current_user();
    if ($user) {
        return $user;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $next = safe_next($_SERVER['REQUEST_URI'] ?? '');
        if ($next !== '') {
            $_SESSION['next'] = $next;
        }
    }
    flash('warning', 'Silakan login terlebih dahulu untuk melanjutkan.');
    redirect('auth/login.php');
}

/** Wajib login dengan salah satu role yang diizinkan. */
function require_role($roles = null)
{
    $user = require_login();
    if ($roles && !in_array($user['role'], (array) $roles, true)) {
        flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect(role_home($user['role']));
    }
    return $user;
}

function has_role(...$roles)
{
    $u = current_user();
    return $u && in_array($u['role'], $roles, true);
}
