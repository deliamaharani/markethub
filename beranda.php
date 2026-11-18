<?php
/** Beranda marketplace — katalog produk (etalase), dapat diakses tanpa login. */
require_once __DIR__ . '/config/app.php';

$q = get('q');
$slug = get('kategori');
$urut = get('urut', 'terbaru');
$kat = $slug !== '' ? db_row('SELECT * FROM kategori WHERE slug = ?', [$slug]) : null;

$res = catalog(['q' => $q, 'kategori_id' => $kat['id'] ?? null, 'urut' => $urut, 'page' => (int) get('page', '1')], 12);
$kategori = kategori_all();

$layout = 'public';
$bodyClass = 'mh-wide';
$showCats = true;
$activeCat = $kat['slug'] ?? '';
$pageTitle = 'Beranda';
require __DIR__ . '/includes/header.php';
?>
<?php
// Hero carousel: 3 slide (produk pilihan, kategori, pelacakan pesanan).
$heroSlides = [
    ['theme' => 'teal', 'art' => 'shop', 'icon' => 'bi-basket2-fill', 'badge' => 'Promo pekan ini',
     'title' => 'Temukan produk lokal pilihan hari ini.',
     'desc' => 'Batik, kopi, kerajinan, dan hasil bumi langsung dari penjualnya.',
     'ctas' => [['Belanja sekarang', '#produk']]],
    ['theme' => 'dark', 'art' => 'cats', 'icon' => 'bi-grid-fill', 'badge' => 'Kategori pilihan',
     'title' => 'Jelajahi beragam kategori favoritmu.',
     'desc' => 'Dari fashion hingga pertanian, temukan yang kamu cari lebih cepat.',
     'ctas' => [['Lihat kategori', $kategori ? url('kategori.php?slug=' . $kategori[0]['slug']) : '#produk']]],
    ['theme' => 'amber', 'art' => 'track', 'icon' => 'bi-truck', 'badge' => 'Gratis ongkos kirim',
     'title' => 'Pantau pesananmu, dari pending sampai selesai.',
     'desc' => 'Gratis ongkos kirim untuk seluruh pesanan, dengan status yang selalu diperbarui.',
     'ctas' => [['Cek pesanan saya', url('pembeli/riwayat-pesanan.php')]]],
];
?>
<section class="pt-3">
  <?php $heroSize = 'sm'; $heroLabel = 'Promo dan informasi MarketHub'; require __DIR__ . '/includes/hero-carousel.php'; ?>
</section>

<div class="mh-sh" id="produk">
  <h2>
    <?php if ($q !== ''): ?>Hasil pencarian “<?= e($q) ?>”
    <?php elseif ($kat): ?><?= e($kat['nama_kategori']) ?>
    <?php else: ?>Produk terbaru<?php endif; ?>
  </h2>
  <form class="d-flex gap-2 align-items-center" method="get">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <select class="form-select form-select-sm" name="kategori" aria-label="Filter kategori" onchange="this.form.submit()" style="min-height:36px;width:auto">
      <option value="">Semua kategori</option>
      <?php foreach ($kategori as $k): ?>
        <option value="<?= e($k['slug']) ?>" <?= $slug === $k['slug'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-select form-select-sm" name="urut" aria-label="Urutkan" onchange="this.form.submit()" style="min-height:36px;width:auto">
      <?php foreach (['terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'nama' => 'Nama A–Z'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= $urut === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-primary btn-sm">Terapkan</button></noscript>
  </form>
</div>
<p class="text-muted small mb-3">Menampilkan <?= count($res['rows']) ?> dari <?= (int) $res['total'] ?> produk</p>

<?php if ($res['rows']): ?>
  <section class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4" aria-label="Daftar produk">
    <?php foreach ($res['rows'] as $p) { echo render_product_card($p); } ?>
  </section>
  <div class="d-flex justify-content-center mt-4"><?= pagination_html($res['page'], $res['pages']) ?></div>
<?php else: ?>
  <div class="card card-pad text-center py-5">
    <i class="bi bi-search fs-1 text-muted"></i>
    <h3 class="h5 mt-3">Produk tidak ditemukan</h3>
    <p class="text-muted">Coba kata kunci lain atau tampilkan semua kategori.</p>
    <div><a class="btn btn-primary" href="<?= e(url('beranda.php')) ?>">Lihat semua produk</a></div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>