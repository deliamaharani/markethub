<?php
/** Sidebar dashboard admin dan penjual (offcanvas pada layar kecil). */
$u = current_user();
$am = $activeMenu ?? '';
if ($u['role'] === 'admin') {
    $menus = [
        ['dashboard', 'Dashboard', 'admin/index.php', 'bi-grid-1x2'],
        ['produk', 'Kelola produk', 'admin/produk/index.php', 'bi-box-seam'],
        ['kategori', 'Kelola kategori', 'admin/kategori/index.php', 'bi-tags'],
        ['pesanan', 'Kelola pesanan', 'admin/pesanan/index.php', 'bi-receipt'],
        ['users', 'Kelola pengguna', 'admin/users/index.php', 'bi-people'],
    ];
} else {
    $menus = [
        ['dashboard', 'Dashboard', 'penjual/index.php', 'bi-grid-1x2'],
        ['produk', 'Produk saya', 'penjual/produk/index.php', 'bi-box-seam'],
        ['pesanan', 'Pesanan masuk', 'penjual/pesanan/index.php', 'bi-receipt'],
    ];
}
?>
<aside class="mh-sb offcanvas-lg offcanvas-start" tabindex="-1" id="mhSidebar" aria-label="Menu dashboard">
  <div class="offcanvas-header">
    <span class="mh-logo text-white"><?= logo_svg(32, true) ?><span>Market<b>Hub</b></span></span>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#mhSidebar" aria-label="Tutup"></button>
  </div>
  <div class="offcanvas-body d-block p-0">
    <a class="mh-logo d-none d-lg-flex" href="<?= e(url(role_home($u['role']))) ?>"><?= logo_svg(34, true) ?><span>Market<b>Hub</b></span></a>
    <div class="sec"><?= $u['role'] === 'admin' ? 'Menu admin' : 'Menu penjual' ?></div>
    <?php foreach ($menus as [$key, $label, $href, $icon]): ?>
      <a class="<?= $am === $key ? 'on' : '' ?>" href="<?= e(url($href)) ?>"><i class="bi <?= $icon ?>"></i><?= e($label) ?></a>
    <?php endforeach; ?>
    <div class="sec">Lainnya</div>
    <a class="<?= $am === 'profil' ? 'on' : '' ?>" href="<?= e(url('pembeli/profil.php')) ?>"><i class="bi bi-person-circle"></i>Profil saya</a>
    <a href="<?= e(url('beranda.php')) ?>"><i class="bi bi-shop"></i>Lihat toko</a>
    <form method="post" action="<?= e(url('auth/logout.php')) ?>"><?= csrf_field() ?>
      <button type="submit"><i class="bi bi-box-arrow-right"></i>Keluar</button></form>
  </div>
</aside>