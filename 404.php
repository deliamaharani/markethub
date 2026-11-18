<?php
/** Halaman 404 — juga di-include oleh halaman yang data-nya tidak ditemukan. */
require_once __DIR__ . '/config/app.php';
http_response_code(404);
$layout = 'public';
$pageTitle = 'Halaman tidak ditemukan';
require __DIR__ . '/includes/header.php';
?>
<div class="text-center py-5 my-4">
  <div class="mh-img mx-auto mb-4" style="width:120px;height:120px;border-radius:28px;background:var(--mh-pl);color:var(--mh-pd)"><i class="bi bi-compass" style="font-size:56px"></i></div>
  <h1 class="display-5">404</h1>
  <p class="text-muted mb-4">Halaman atau produk yang Anda cari tidak ditemukan, atau sudah tidak tersedia.</p>
  <a class="btn btn-primary" href="<?= e(url('beranda.php')) ?>"><i class="bi bi-shop"></i>Kembali ke beranda</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
