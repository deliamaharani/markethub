<?php
/**
 * Middleware autentikasi & otorisasi API.
 * Dua cara autentikasi:
 *   1. Session login web (cookie). Untuk method POST/PUT/DELETE wajib header X-CSRF-Token
 *      (token dibaca dari GET api/index.php setelah login).
 *   2. HTTP Basic Auth (email:password) untuk klien non-browser seperti curl/Postman.
 */

function api_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    $GLOBALS['api_auth_mode'] = null;

    $email = $_SERVER['PHP_AUTH_USER'] ?? null;
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;
    if ($email === null && !empty($_SERVER['HTTP_AUTHORIZATION']) && stripos($_SERVER['HTTP_AUTHORIZATION'], 'basic ') === 0) {
        $dec = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6), true);
        if ($dec !== false && strpos($dec, ':') !== false) {
            [$email, $pass] = explode(':', $dec, 2);
        }
    }
    if ($email !== null) {
        $row = db_row('SELECT id, nama, email, password, role, status, created_at FROM users WHERE email = ?', [strtolower($email)]);
        $hash = $row['password'] ?? '$2y$10$usesomesillystringforsaltuQ9Z5s2h0nQe0m4v3u0D6Qk8k2hGq1e';
        if ($row && password_verify((string) $pass, $hash) && $row['status'] === 'aktif') {
            unset($row['password']);
            $row['id'] = (int) $row['id'];
            $GLOBALS['api_auth_mode'] = 'basic';
            return $user = $row;
        }
        api_fail('Kredensial tidak valid atau akun nonaktif.', 401);
    }
    if ($cu = current_user()) {
        $GLOBALS['api_auth_mode'] = 'session';
        $user = $cu;
    }
    return $user;
}

/** Wajib login (dan role tertentu bila diberikan). Mengembalikan data pengguna. */
function api_require($roles = null)
{
    $u = api_user();
    if (!$u) {
        header('WWW-Authenticate: Basic realm="MarketHub API"');
        api_fail('Autentikasi diperlukan. Login atau gunakan HTTP Basic Auth.', 401);
    }
    if ($roles && !in_array($u['role'], (array) $roles, true)) {
        api_fail('Akses ditolak. Role "' . $u['role'] . '" tidak diizinkan untuk aksi ini.', 403);
    }
    // Perlindungan CSRF untuk request berbasis cookie session
    $unsafe = !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'OPTIONS'], true);
    if ($unsafe && ($GLOBALS['api_auth_mode'] ?? '') === 'session') {
        $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($t) || $t === '' || !hash_equals(csrf_token(), $t)) {
            api_fail('Header X-CSRF-Token tidak valid. Ambil token dari GET api/index.php.', 403);
        }
    }
    return $u;
}
