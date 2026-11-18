<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';
$scope = 'admin';
$mode = 'edit';
require __DIR__ . '/../../includes/produk-form.php';
