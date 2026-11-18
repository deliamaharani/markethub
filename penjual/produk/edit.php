<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['penjual'];
require __DIR__ . '/../../includes/auth-check.php';
$scope = 'penjual';
$mode = 'edit';
require __DIR__ . '/../../includes/produk-form.php';
