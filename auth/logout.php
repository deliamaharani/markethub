<?php
require_once __DIR__ . '/../config/app.php';

// Logout hanya melalui POST dengan CSRF token yang valid.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect(is_logged() ? role_home(current_user()['role']) : 'index.php');
}

logout_user();
session_start();
session_regenerate_id(true);
flash('success', 'Anda telah keluar dari akun.');
redirect('index.php');
