<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['penjual'];
require __DIR__ . '/../../includes/auth-check.php';
$scope = 'penjual';
require __DIR__ . '/../../includes/produk-list.php';
