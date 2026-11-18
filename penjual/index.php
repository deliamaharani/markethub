<?php
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['penjual'];
require __DIR__ . '/../includes/auth-check.php';
$sid = $authUser['id'];

$produk = (int) db_val('SELECT COUNT(*) FROM produk WHERE penjual_id = ?', [$sid]);
$stok = (int) db_val('SELECT COALESCE(SUM(stok), 0) FROM produk WHERE penjual_id = ?', [$sid]);
$pesanan = (int) db_val('SELECT COUNT(DISTINCT d.pesanan_id) FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id WHERE p.penjual_id = ?', [$sid]);
$perlu = (int) db_val("SELECT COUNT(DISTINCT d.pesanan_id) FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id JOIN pesanan o ON o.id = d.pesanan_id
    WHERE p.penjual_id = ? AND o.status_pesanan IN ('pending','diproses')", [$sid]);
$penjualan = (int) db_val("SELECT COALESCE(SUM(d.subtotal), 0) FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id JOIN pesanan o ON o.id = d.pesanan_id
    WHERE p.penjual_id = ? AND o.status_pesanan <> 'dibatalkan'", [$sid]);
$menipis = db_all('SELECT * FROM produk WHERE penjual_id = ? AND stok <= 5 ORDER BY stok, id LIMIT 5', [$sid]);
$terbaru = db_all("SELECT o.*, b.nama AS pembeli_nama,
        (SELECT COALESCE(SUM(d.subtotal), 0) FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id WHERE d.pesanan_id = o.id AND p.penjual_id = ?) AS total_saya
    FROM pesanan o JOIN users b ON b.id = o.user_id
    WHERE EXISTS (SELECT 1 FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id WHERE d.pesanan_id = o.id AND p.penjual_id = ?)
    ORDER BY o.tanggal_pesanan DESC, o.id DESC LIMIT 6", [$sid, $sid]);

$layout = 'dashboard';
$activeMenu = 'dashboard';
$topTitle = 'Dashboard penjual';
$pageTitle = 'Dashboard penjual';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mh-stat">
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#D9F2EE;color:#115E59"><i class="bi bi-box-seam"></i></span><div><b><?= $produk ?></b><span class="l">Produk saya</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#E8E1F5;color:#4C3A8A"><i class="bi bi-stack"></i></span><div><b><?= $stok ?></b><span class="l">Total stok</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#FBE9C8;color:#7A4A06"><i class="bi bi-receipt"></i></span><div><b><?= $pesanan ?></b><span class="l">Pesanan (<?= $perlu ?> perlu diproses)</span></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><span class="si" style="background:#DCFCE7;color:#166534"><i class="bi bi-graph-up-arrow"></i></span><div><b><?= e(rupiah_short($penjualan)) ?></b><span class="l">Total penjualan</span></div></div></div>
</div>

<div class="row g-4 mt-1">
  <div class="col-xl-7">
    <div class="card overflow-hidden h-100">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 px-4"><h2 class="h5 mb-0">Pesanan terbaru</h2><a class="btn btn-outline-primary btn-sm" href="<?= e(url('penjual/pesanan/index.php')) ?>">Lihat semua</a></div>
      <div class="table-responsive"><table class="table mh-table">
        <thead><tr><th>Nomor</th><th>Pembeli</th><th>Produk Anda</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($terbaru as $o): ?><tr>
          <td><a class="fw-bold" href="<?= e(url('penjual/pesanan/detail.php?id=' . (int) $o['id'])) ?>"><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></a></td>
          <td><?= e($o['pembeli_nama']) ?></td><td><?= rupiah($o['total_saya']) ?></td><td><?= status_badge($o['status_pesanan']) ?></td></tr><?php endforeach; ?>
        <?php if (!$terbaru): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada pesanan masuk.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card overflow-hidden h-100">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 px-4"><h2 class="h5 mb-0">Stok menipis</h2><a class="btn btn-primary btn-sm" href="<?= e(url('penjual/produk/tambah.php')) ?>"><i class="bi bi-plus-lg"></i>Tambah produk</a></div>
      <div class="table-responsive"><table class="table mh-table">
        <thead><tr><th>Produk</th><th>Stok</th><th></th></tr></thead><tbody>
        <?php foreach ($menipis as $p): ?><tr><td><div class="thumb-box"><?= produk_img($p, 'img-thumb') ?><b><?= e($p['nama_produk']) ?></b></div></td><td><?= stok_badge($p['stok']) ?></td>
          <td><a class="btn btn-outline-secondary ibs" href="<?= e(url('penjual/produk/edit.php?id=' . (int) $p['id'])) ?>" aria-label="Edit"><i class="bi bi-pencil"></i></a></td></tr><?php endforeach; ?>
        <?php if (!$menipis): ?><tr><td colspan="3" class="text-center text-muted py-4">Semua stok aman.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
