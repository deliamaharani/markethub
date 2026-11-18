<?php
/** Detail produk (admin). */
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';

$p = produk_find((int) get('id'));
if (!$p) {
    flash('danger', 'Produk tidak ditemukan.');
    redirect('admin/produk/index.php');
}
$terjual = (int) db_val("SELECT COALESCE(SUM(d.jumlah), 0) FROM detail_pesanan d JOIN pesanan o ON o.id = d.pesanan_id
    WHERE d.produk_id = ? AND o.status_pesanan <> 'dibatalkan'", [(int) $p['id']]);
$riwayat = produk_has_history($p['id']);

$layout = 'dashboard';
$activeMenu = 'produk';
$topTitle = 'Detail produk';
$topCrumb = 'Dashboard / Produk / Detail';
$pageTitle = 'Detail produk';
require __DIR__ . '/../../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-5"><?= produk_img($p, 'img-big') ?></div>
  <div class="col-lg-7">
    <div class="card card-pad">
      <span class="st" style="background:var(--mh-pl);color:var(--mh-pd)"><?= e($p['nama_kategori']) ?></span>
      <h2 class="h3 mt-2"><?= e($p['nama_produk']) ?></h2>
      <div class="fs-3 fw-bold mb-3" style="color:var(--mh-pd)"><?= rupiah($p['harga']) ?></div>
      <div class="mh-sum"><span class="text-muted">ID produk</span><b><?= (int) $p['id'] ?></b></div>
      <div class="mh-sum border-top"><span class="text-muted">Penjual</span><b><?= e($p['penjual_nama']) ?></b></div>
      <div class="mh-sum border-top"><span class="text-muted">Stok</span><?= stok_badge($p['stok']) ?></div>
      <div class="mh-sum border-top"><span class="text-muted">Terjual</span><b><?= $terjual ?> item</b></div>
      <div class="mh-sum border-top"><span class="text-muted">Dibuat</span><span><?= e(tgl($p['created_at'], true)) ?></span></div>
      <div class="mh-sum border-top"><span class="text-muted">Terakhir diperbarui</span><span><?= e(tgl($p['updated_at'], true)) ?></span></div>
      <h3 class="h6 fw-bold mt-4">Deskripsi</h3>
      <p class="text-muted" style="line-height:1.7"><?= nl2br(e($p['deskripsi'])) ?></p>
      <div class="d-flex gap-2 flex-wrap mt-2">
        <a class="btn btn-primary" href="<?= e(url('admin/produk/edit.php?id=' . (int) $p['id'])) ?>"><i class="bi bi-pencil"></i>Edit</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('detail-produk.php?id=' . (int) $p['id'])) ?>"><i class="bi bi-shop"></i>Lihat di toko</a>
        <form method="post" action="<?= e(url('admin/produk/hapus.php')) ?>" data-confirm="Hapus produk ini?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button class="btn btn-outline-danger" type="submit" <?= $riwayat ? 'disabled title="Memiliki histori transaksi"' : '' ?>><i class="bi bi-trash"></i>Hapus</button>
        </form>
        <a class="btn btn-outline-secondary ms-auto" href="<?= e(url('admin/produk/index.php')) ?>">Kembali</a>
      </div>
      <?php if ($riwayat): ?><div class="form-text mt-2">Produk memiliki histori transaksi sehingga tidak dapat dihapus.</div><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
