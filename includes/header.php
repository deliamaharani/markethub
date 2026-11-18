<?php
/**
 * Pembuka halaman. Variabel yang dapat diatur sebelum include:
 *   $pageTitle  judul tab         $layout  public | landing | dashboard | auth
 *   $activeMenu menu sidebar aktif $showCats tampilkan chip kategori (layout public)
 */
$layout    = $layout ?? 'public';
$pageTitle = $pageTitle ?? 'MarketHub';
$bodyClass = $bodyClass ?? '';   // kelas tambahan opsional, mis. 'mh-wide' (layout lebar)
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · MarketHub</title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('logo/logo.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="layout-<?= e($layout) ?><?= $bodyClass !== '' ? ' ' . e($bodyClass) : '' ?>">
<?php
if ($layout === 'dashboard') {
    echo '<div class="mh-adm">';
    require __DIR__ . '/sidebar.php';
    echo '<div class="mh-mn">';
    require __DIR__ . '/topbar.php';
    echo '<div class="mh-ct">';
    require __DIR__ . '/alert.php';
} elseif ($layout === 'public' || $layout === 'landing') {
    require __DIR__ . '/navbar.php';
    if ($layout === 'public') {
        echo '<main class="container pb-4">';
        require __DIR__ . '/alert.php';
    }
}
