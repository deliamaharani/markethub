<?php
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../includes/auth-check.php';

$stat = [
    'users'   => (int) db_val('SELECT COUNT(*) FROM users'),
    'produk'  => (int) db_val('SELECT COUNT(*) FROM produk'),
    'pesanan' => (int) db_val('SELECT COUNT(*) FROM pesanan'),
    'omzet'   => (int) db_val("SELECT COALESCE(SUM(total_harga), 0) FROM pesanan WHERE status_pesanan <> 'dibatalkan'"),
    'kategori' => (int) db_val('SELECT COUNT(*) FROM kategori'),
    'pending' => (int) db_val("SELECT COUNT(*) FROM pesanan WHERE status_pesanan = 'pending'"),
];
$terbaru = db_all('SELECT o.*, b.nama AS pembeli_nama FROM pesanan o JOIN users b ON b.id = o.user_id ORDER BY o.tanggal_pesanan DESC, o.id DESC LIMIT 6');

$layout = 'dashboard';
$activeMenu = 'dashboard';
$topTitle = 'Dashboard';
$pageTitle = 'Dashboard admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mh-stat">
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#E8E1F5;color:#4C3A8A"><i class="bi bi-people"></i></span><div><b><?= $stat['users'] ?></b><span class="l">Total pengguna</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#D9F2EE;color:#115E59"><i class="bi bi-box-seam"></i></span><div><b><?= $stat['produk'] ?></b><span class="l">Total produk</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#FBE9C8;color:#7A4A06"><i class="bi bi-receipt"></i></span><div><b><?= $stat['pesanan'] ?></b><span class="l">Total pesanan</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#DCFCE7;color:#166534"><i class="bi bi-graph-up-arrow"></i></span><div><b><?= e(rupiah_short($stat['omzet'])) ?></b><span class="l">Total transaksi</span></div></div></div>
</div>

<div class="mh-sh" style="margin-top:28px"><h2 class="h5">Pengelolaan data</h2></div>
<div class="row g-3">
  <?php foreach ([
      ['Produk', $stat['produk'] . ' produk', 'admin/produk/index.php'],
      ['Kategori', $stat['kategori'] . ' kategori', 'admin/kategori/index.php'],
      ['Pengguna', $stat['users'] . ' akun terdaftar', 'admin/users/index.php'],
      ['Pesanan', $stat['pending'] . ' menunggu diproses', 'admin/pesanan/index.php'],
  ] as [$n, $d, $h]): ?>
    <div class="col-sm-6 col-xl-3"><a class="card mh-link-card" href="<?= e(url($h)) ?>"><span><b class="d-block"><?= e($n) ?></b><span class="text-muted small"><?= e($d) ?></span></span><i class="bi bi-arrow-right fs-5" style="color:var(--mh-p)"></i></a></div>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden mt-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 px-4"><h2 class="h5 mb-0">Pesanan terbaru</h2><a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/pesanan/index.php')) ?>">Lihat semua pesanan</a></div>
  <div class="table-responsive">
    <table class="table mh-table">
      <thead><tr><th>Nomor pesanan</th><th>Pembeli</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($terbaru as $o): ?>
        <tr><td><a class="fw-bold" href="<?= e(url('admin/pesanan/detail.php?id=' . (int) $o['id'])) ?>"><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></a></td>
          <td><?= e($o['pembeli_nama']) ?></td><td><?= e(tgl($o['tanggal_pesanan'])) ?></td><td><?= rupiah($o['total_harga']) ?></td><td><?= status_badge($o['status_pesanan']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$terbaru): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada pesanan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
