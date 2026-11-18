<?php
/** Penutup halaman, sesuai $layout yang dibuka di header.php. */
$layout = $layout ?? 'public';
if ($layout === 'dashboard') {
    echo '</div></div></div>';
} elseif ($layout === 'public' || $layout === 'landing') {
    if ($layout === 'public') {
        echo '</main>';
    } ?>
<footer class="mh-ft">
  <div class="container">
    <div class="row g-4 justify-content-between">
      <div class="col-lg-4">
        <div class="mh-logo text-white"><?= logo_svg(34, true) ?><span>Market<b>Hub</b></span></div>
        <p class="mt-3 small">Marketplace tradisional modern untuk produk lokal Indonesia. Belanja langsung dari penjualnya.</p>
      </div>
      <div class="col-6 col-md-3 col-lg-2"><h4>Belanja</h4>
        <a href="<?= e(url('beranda.php')) ?>">Semua produk</a><a href="<?= e(url('index.php#kategori')) ?>">Kategori</a><a href="<?= e(url('index.php#keunggulan')) ?>">Keunggulan</a></div>
      <div class="col-6 col-md-3 col-lg-2"><h4>Akun</h4>
        <?php if (is_logged()): ?>
          <a href="<?= e(url('pembeli/profil.php')) ?>">Profil saya</a><a href="<?= e(url('pembeli/riwayat-pesanan.php')) ?>">Riwayat pesanan</a>
        <?php else: ?>
          <a href="<?= e(url('auth/login.php')) ?>">Masuk</a><a href="<?= e(url('auth/register.php')) ?>">Daftar</a>
        <?php endif; ?>
      </div>
      <div class="col-6 col-md-3 col-lg-2"><h4>Bantuan</h4>
        <a href="<?= e(url('api/index.php')) ?>">Dokumentasi API</a><a href="<?= e(url('index.php#keunggulan')) ?>">Cara kerja</a></div>
    </div>
    <div class="mh-ft-b">© <?= date('Y') ?> MarketHub. Proyek UTS Pemrograman Web.</div>
  </div>
</footer>
<?php
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/script.js')) ?>"></script>
</body>
</html>