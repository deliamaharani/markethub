<?php
/** Navbar halaman pembeli / publik. */
$u = current_user();
$cartN = ($u && $u['role'] === 'pembeli') ? cart_count($u['id']) : 0;
$isLanding = ($layout ?? 'public') === 'landing';
$activeCat = $activeCat ?? '';
?>
<header class="mh-nav">
  <div class="container mh-nav-in">
    <a class="mh-logo" href="<?= e(url($isLanding ? 'index.php' : 'beranda.php')) ?>"><?= logo_svg(36) ?><span>Market<b>Hub</b></span></a>

    <?php if ($isLanding): ?>
      <nav class="mh-links d-none d-md-flex" aria-label="Menu utama">
        <a href="<?= e(url('beranda.php')) ?>">Beranda</a>
        <a href="#kategori">Kategori</a>
        <a href="#keunggulan">Keunggulan</a>
        <a href="#unggulan">Produk unggulan</a>
      </nav>
    <?php else: ?>
      <form class="mh-search" action="<?= e(url('beranda.php')) ?>" method="get" role="search">
        <input class="form-control" type="search" name="q" value="<?= e(get('q')) ?>" placeholder="Cari batik, kopi, kerajinan..." aria-label="Cari produk">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Cari</span></button>
      </form>
    <?php endif; ?>

    <div class="mh-nr">
      <?php if (!$isLanding && (!$u || $u['role'] === 'pembeli')): ?>
        <a class="mh-ib" href="<?= e(url('pembeli/keranjang.php')) ?>" aria-label="Keranjang belanja">
          <i class="bi bi-cart3"></i><?php if ($cartN > 0): ?><span class="mh-cnt"><?= $cartN ?></span><?php endif; ?>
        </a>
      <?php endif; ?>

      <?php if ($u): ?>
        <div class="dropdown">
          <a class="mh-me dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="mh-av"><?= e(initials($u['nama'])) ?></span><span class="d-none d-sm-inline"><?= e(explode(' ', $u['nama'])[0]) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted"><?= e($u['email']) ?><br><?= e(role_label($u['role'])) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <?php if ($u['role'] !== 'pembeli'): ?>
              <li><a class="dropdown-item" href="<?= e(url(role_home($u['role']))) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="<?= e(url('pembeli/riwayat-pesanan.php')) ?>"><i class="bi bi-receipt me-2"></i>Riwayat pesanan</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?= e(url('pembeli/profil.php')) ?>"><i class="bi bi-person me-2"></i>Profil saya</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><form method="post" action="<?= e(url('auth/logout.php')) ?>"><?= csrf_field() ?>
              <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button></form></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('auth/login.php')) ?>">Masuk</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('auth/register.php')) ?>">Daftar</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($showCats)): ?>
    <nav class="container mh-cats" aria-label="Kategori">
      <a class="<?= $activeCat === '' ? 'on' : '' ?>" href="<?= e(url('beranda.php')) ?>">Semua produk</a>
      <?php foreach (db_all('SELECT nama_kategori, slug FROM kategori ORDER BY id') as $c): ?>
        <a class="<?= $activeCat === $c['slug'] ? 'on' : '' ?>" href="<?= e(url('kategori.php?slug=' . $c['slug'])) ?>"><?= e($c['nama_kategori']) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
</header>