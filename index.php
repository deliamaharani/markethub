<?php
/** Landing page MarketHub — halaman pengenalan (tanpa login). */
require_once __DIR__ . '/config/app.php';

$kategori = kategori_all();
// Produk unggulan: paling banyak dipesan (non-batal), lalu terbaru.
$unggulan = db_all("SELECT p.*, k.nama_kategori, u.nama AS penjual_nama,
        COALESCE((SELECT SUM(d.jumlah) FROM detail_pesanan d JOIN pesanan o ON o.id = d.pesanan_id
                  WHERE d.produk_id = p.id AND o.status_pesanan <> 'dibatalkan'), 0) AS terjual
    FROM produk p JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.penjual_id
    WHERE u.status = 'aktif' AND p.stok > 0
    ORDER BY terjual DESC, p.created_at DESC LIMIT 4");
$totalProduk = (int) db_val("SELECT COUNT(*) FROM produk p JOIN users u ON u.id = p.penjual_id WHERE u.status = 'aktif'");
$totalPenjual = (int) db_val("SELECT COUNT(*) FROM users WHERE role = 'penjual' AND status = 'aktif'");

$layout = 'landing';
$bodyClass = 'mh-wide';
$pageTitle = 'Pasar tradisional dalam genggaman';
require __DIR__ . '/includes/header.php';
$fitur = [
    ['bi-shop', 'Langsung dari penjual', 'Produk dijual langsung oleh pengrajin dan pelaku usaha lokal, tanpa perantara berlapis.', '#D9F2EE', '#115E59'],
    ['bi-shield-check', 'Stok selalu dicek', 'Stok dan harga divalidasi ulang saat checkout sehingga pesanan Anda akurat.', '#E8E1F5', '#4C3A8A'],
    ['bi-truck', 'Pantau status pesanan', 'Ikuti perjalanan pesanan dari pending, diproses, dikirim, hingga selesai.', '#FBE9C8', '#7A4A06'],
    ['bi-tag', 'Harga transparan', 'Harga satuan tercatat pada setiap transaksi sehingga riwayat belanja Anda jelas.', '#DDEBD0', '#2F5A14'],
];
?>
<div class="container pt-3"><?php require __DIR__ . '/includes/alert.php'; ?></div>

<?php
// Hero carousel: 3 slide dengan tema berbeda (belanja, ongkir/pesanan, jualan).
$u = current_user();
if (!$u) {
    $ctaJual = [['Daftar jadi penjual', url('auth/register.php')], ['Sudah punya akun? Masuk', url('auth/login.php')]];
} elseif ($u['role'] === 'pembeli') {
    $ctaJual = [['Lihat produk lokal', url('beranda.php')]];
} else {
    $ctaJual = [['Buka dashboard', url(role_home($u['role']))]];
}
$heroSlides = [
    ['theme' => 'teal', 'art' => 'shop', 'icon' => 'bi-basket2-fill', 'badge' => 'Promo pekan ini',
     'title' => 'Belanja produk lokal, langsung dari penjualnya.',
     'desc' => 'Batik, kopi, kerajinan, dan hasil bumi pilihan dari pengrajin dan pelaku usaha lokal.',
     'ctas' => array_merge([['Mulai Belanja', url('beranda.php')]], is_logged() ? [] : [['Daftar gratis', url('auth/register.php')]]),
     'meta' => [[(string) $totalProduk, 'Produk tersedia'], [(string) $totalPenjual, 'Penjual aktif'], [(string) count($kategori), 'Kategori']]],
    ['theme' => 'dark', 'art' => 'ship', 'icon' => 'bi-truck', 'badge' => 'Gratis ongkos kirim',
     'title' => 'Gratis ongkir untuk setiap pesanan.',
     'desc' => 'Pesan hari ini lalu pantau statusnya dari pending hingga selesai, tanpa biaya pengiriman tambahan.',
     'ctas' => [['Belanja sekarang', url('beranda.php')]],
     'meta' => [['bi-cash-coin', 'Ongkir Rp0'], ['bi-geo-alt', 'Status terpantau'], ['bi-shield-check', 'Stok dicek ulang']]],
    ['theme' => 'amber', 'art' => 'seller', 'icon' => 'bi-shop', 'badge' => 'Untuk penjual lokal',
     'title' => 'Punya produk lokal? Jual di MarketHub.',
     'desc' => 'Kelola produk, stok, dan pesanan dari satu dashboard penjual yang sederhana.',
     'ctas' => $ctaJual,
     'meta' => [['bi-speedometer2', 'Dashboard khusus'], ['bi-box-seam', 'Kelola stok'], ['bi-receipt', 'Pantau pesanan']]],
];
?>
<section class="container pt-3">
  <?php $heroSize = 'lg'; $heroLabel = 'Promo dan informasi MarketHub'; require __DIR__ . '/includes/hero-carousel.php'; ?>
</section>

<section class="container mt-4">
  <div class="mh-banner">
    <div><h3>Gratis ongkos kirim untuk semua pesanan</h3><span>Belanja sekarang dan nikmati produk lokal pilihan tanpa biaya pengiriman.</span></div>
    <a class="btn btn-primary" href="<?= e(url('beranda.php')) ?>">Belanja sekarang</a>
  </div>
</section>

<section class="container" id="keunggulan">
  <div class="mh-sh"><h2>Mengapa belanja di MarketHub?</h2></div>
  <div class="row g-3">
    <?php foreach ($fitur as [$ic, $jd, $ds, $bg, $fg]): ?>
      <div class="col-sm-6 col-lg-3"><div class="card mh-feat">
        <span class="ic" style="background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $ic ?>"></i></span>
        <h3 class="h6 fw-bold"><?= e($jd) ?></h3><p class="text-muted small mb-0"><?= e($ds) ?></p>
      </div></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container" id="kategori">
  <div class="mh-sh"><h2>Kategori produk</h2><a class="fw-600 small" href="<?= e(url('beranda.php')) ?>">Lihat semua produk</a></div>
  <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-6">
    <?php foreach ($kategori as $k): [$bg, $fg, $icon] = kategori_style($k['id']); ?>
      <div class="col"><a class="card mh-cat" href="<?= e(url('kategori.php?slug=' . $k['slug'])) ?>">
        <span class="ic" style="background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $icon ?>"></i></span>
        <span><b class="d-block"><?= e($k['nama_kategori']) ?></b><span class="text-muted small"><?= (int) $k['jml'] ?> produk</span></span>
      </a></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container" id="unggulan">
  <div class="mh-sh"><h2>Produk unggulan</h2><a class="fw-600 small" href="<?= e(url('beranda.php')) ?>">Lihat semua</a></div>
  <?php if ($unggulan): ?>
    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-4">
      <?php foreach ($unggulan as $p) { echo render_product_card($p); } ?>
    </div>
  <?php else: ?>
    <div class="card card-pad text-center text-muted">Belum ada produk. Silakan kembali lagi nanti.</div>
  <?php endif; ?>
</section>

<?php if (!is_logged() || has_role('pembeli')): ?>
<section class="container mt-5">
  <div class="mh-cta">
    <div><h2>Punya produk lokal untuk dijual?</h2><p class="mb-0">Daftar sebagai penjual dan kelola produk, stok, serta pesanan Anda dari satu dashboard.</p></div>
    <a class="btn btn-accent btn-lg" href="<?= e(url(is_logged() ? 'beranda.php' : 'auth/register.php')) ?>"><?= is_logged() ? 'Mulai Belanja' : 'Daftar sekarang' ?></a>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>