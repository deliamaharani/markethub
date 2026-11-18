<?php
/** Pengelolaan session login. */

/** Pengguna yang sedang login (diambil dari database; akun nonaktif otomatis dikeluarkan). */
function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $row = db_row('SELECT id, nama, email, role, status, created_at FROM users WHERE id = ?', [(int) $_SESSION['uid']]);
        if ($row && $row['status'] === 'aktif') {
            $row['id'] = (int) $row['id'];
            $user = $row;
        } else {
            unset($_SESSION['uid']);
        }
    }
    return $user;
}

function is_logged()
{
    return current_user() !== null;
}

function login_user(array $row)
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $row['id'];
    unset($_SESSION['login_fail'], $_SESSION['login_lock']);
}

function logout_user()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function role_home($role)
{
    switch ($role) {
        case 'admin':  return 'admin/index.php';
        case 'penjual': return 'penjual/index.php';
        default:       return 'beranda.php';
    }
}

/** Hanya izinkan path relatif internal (mis. "detail-produk.php?id=3") sebagai tujuan redirect. */
function safe_next($next)
{
    $next = ltrim((string) $next, '/');
    if (BASE_URL !== '' && strpos('/' . $next, BASE_URL . '/') === 0) {
        $next = substr('/' . $next, strlen(BASE_URL) + 1);
    }
    if ($next !== '' && preg_match('#^[A-Za-z0-9_\-/]+\.php(\?[A-Za-z0-9_=&%\-\.]*)?$#', $next) && strpos($next, '..') === false
        && !preg_match('#^auth/(login|register|logout)\.php#', $next)) {
        return $next;
    }
    return '';
}
