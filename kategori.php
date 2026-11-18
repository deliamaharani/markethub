<?php
/** Halaman satu kategori (?slug=...) — dapat diakses tanpa login. */
require_once __DIR__ . '/config/app.php';

$kat = db_row('SELECT * FROM kategori WHERE slug = ?', [get('slug')]);
if (!$kat) {
    require __DIR__ . '/404.php';
    exit;
}
$urut = get('urut', 'terbaru');
$res = catalog(['kategori_id' => (int) $kat['id'], 'urut' => $urut, 'page' => (int) get('page', '1')], 12);
[$bg, $fg, $icon] = kategori_style($kat['id']);

$layout = 'public';
$bodyClass = 'mh-wide';
$showCats = true;
$activeCat = $kat['slug'];
$pageTitle = $kat['nama_kategori'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item active" aria-current="page"><?= e($kat['nama_kategori']) ?></li>
</ol></nav>

<div class="card card-pad d-flex flex-row align-items-center gap-3 flex-wrap mb-4">
  <span class="mh-img img-sm" style="background:<?= $bg ?>;color:<?= $fg ?>;width:64px;height:64px;border-radius:14px"><i class="bi <?= $icon ?>" style="font-size:30px"></i></span>
  <div class="flex-grow-1"><h1 class="h3 mb-0"><?= e($kat['nama_kategori']) ?></h1><span class="text-muted small"><?= (int) $res['total'] ?> produk tersedia</span></div>
  <form method="get" class="d-flex gap-2">
    <input type="hidden" name="slug" value="<?= e($kat['slug']) ?>">
    <select class="form-select form-select-sm" name="urut" aria-label="Urutkan" onchange="this.form.submit()" style="min-height:36px">
      <?php foreach (['terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'nama' => 'Nama A–Z'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= $urut === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($res['rows']): ?>
  <section class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">
    <?php foreach ($res['rows'] as $p) { echo render_product_card($p); } ?>
  </section>
  <div class="d-flex justify-content-center mt-4"><?= pagination_html($res['page'], $res['pages']) ?></div>
<?php else: ?>
  <div class="card card-pad text-center py-5 text-muted">Belum ada produk pada kategori ini.</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>