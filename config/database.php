<?php
/**
 * Konfigurasi koneksi database MarketHub (MySQLi).
 * Pengaturan default XAMPP: user "root" tanpa password.
 * Ubah nilai di bawah jika konfigurasi MySQL Anda berbeda.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'markethub');
define('DB_PORT', 3306);

// Lempar exception pada error MySQLi agar transaksi dapat di-rollback dengan benar.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('MarketHub DB error: ' . $e->getMessage());
    http_response_code(500);
    if (defined('API_REQUEST')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Koneksi database gagal</title>'
           . '<body style="font-family:sans-serif;max-width:560px;margin:80px auto;line-height:1.6">'
           . '<h2>Koneksi database gagal</h2>'
           . '<p>Pastikan MySQL aktif di XAMPP, database <b>markethub</b> sudah diimpor lewat phpMyAdmin '
           . '(file <code>database/markethub.sql</code>), dan pengaturan di <code>config/database.php</code> sudah benar.</p></body>';
    }
    exit;
}
