<?php
/**
 * Bootstrap aplikasi MarketHub.
 * Setiap halaman cukup melakukan: require_once __DIR__ . '/.../config/app.php';
 */
if (defined('APP_BOOTED')) {
    return;
}
define('APP_BOOTED', true);

define('APP_NAME', 'MarketHub');
define('ROOT_PATH', str_replace('\\', '/', realpath(__DIR__ . '/..')));
define('UPLOAD_DIR', ROOT_PATH . '/uploads/produk');
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024); // 2 MB

// BASE_URL dihitung otomatis, sehingga cocok untuk http://localhost/markethub/
$docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$base = ($docRoot !== '' && strpos(ROOT_PATH, $docRoot) === 0) ? substr(ROOT_PATH, strlen($docRoot)) : '/markethub';
define('BASE_URL', rtrim($base, '/'));

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_name('MARKETHUBSESS');
    session_start();
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/auth/session.php';
require_once ROOT_PATH . '/auth/check-role.php';
