<?php
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['pembeli'];
require __DIR__ . '/../includes/auth-check.php';

$items = cart_items($authUser['id']);
$total = 0;
$totalQty = 0;
$masalah = false;
foreach ($items as $it) {
    $total += (int) $it['subtotal'];
    $totalQty += (int) $it['jumlah'];
    if ((int) $it['stok'] < (int) $it['jumlah']) {
        $masalah = true;
    }
}

$layout = 'public';
$bodyClass = 'mh-wide';
$pageTitle = 'Keranjang belanja';
require __DIR__ . '/../includes/header.php';
?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item active" aria-current="page">Keranjang belanja</li>
</ol></nav>
<div class="mh-ph"><h1>Keranjang belanja</h1><span class="text-muted small"><?= count($items) ?> produk · <?= $totalQty ?> item</span></div>

<?php if (!$items): ?>
  <div class="card card-pad text-center py-5">
    <i class="bi bi-cart-x fs-1 text-muted"></i>
    <h2 class="h5 mt-3">Keranjang Anda masih kosong</h2>
    <p class="text-muted">Temukan produk lokal pilihan dan tambahkan ke keranjang.</p>
    <div><a class="btn btn-primary" href="<?= e(url('beranda.php')) ?>">Mulai belanja</a></div>
  </div>
<?php else: ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card overflow-hidden">
      <?php foreach ($items as $it): $kurang = (int) $it['stok'] < (int) $it['jumlah']; ?>
        <div class="d-flex flex-wrap align-items-center gap-3 p-3 p-md-4 border-bottom">
          <a href="<?= e(url('detail-produk.php?id=' . (int) $it['produk_id'])) ?>"><?= produk_img(['kategori_id' => $it['kategori_id'], 'gambar' => $it['gambar'], 'nama_produk' => $it['nama_produk']], 'img-mini') ?></a>
          <div class="flex-grow-1" style="min-width:180px">
            <a class="fw-bold text-body" href="<?= e(url('detail-produk.php?id=' . (int) $it['produk_id'])) ?>"><?= e($it['nama_produk']) ?></a>
            <div class="text-muted small"><?= e($it['penjual_nama']) ?></div>
            <div class="small mt-1">Harga satuan <b><?= rupiah($it['harga']) ?></b></div>
            <?php if ($kurang): ?><div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle"></i> Stok tersisa <?= (int) $it['stok'] ?> pcs. Kurangi jumlah.</div><?php endif; ?>
          </div>
          <form method="post" action="<?= e(url('pembeli/update-keranjang.php')) ?>" class="mh-qty">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $it['kid'] ?>">
            <button type="submit" name="aksi" value="kurang" aria-label="Kurangi jumlah" <?= (int) $it['jumlah'] <= 1 ? 'disabled' : '' ?>><i class="bi bi-dash-lg"></i></button>
            <span aria-label="Jumlah"><?= (int) $it['jumlah'] ?></span>
            <button type="submit" name="aksi" value="tambah" aria-label="Tambah jumlah" <?= (int) $it['jumlah'] >= (int) $it['stok'] ? 'disabled' : '' ?>><i class="bi bi-plus-lg"></i></button>
          </form>
          <div class="text-end" style="flex:0 0 120px"><div class="text-muted" style="font-size:12px">Subtotal</div><b class="fs-6" style="color:var(--mh-pd)"><?= rupiah($it['subtotal']) ?></b></div>
          <form method="post" action="<?= e(url('pembeli/hapus-keranjang.php')) ?>" data-confirm="Hapus produk ini dari keranjang?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $it['kid'] ?>">
            <button class="btn btn-outline-danger ibs" type="submit" aria-label="Hapus produk"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 p-md-4">
        <a class="fw-600 small" href="<?= e(url('beranda.php')) ?>">← Lanjut belanja</a>
        <form method="post" action="<?= e(url('pembeli/hapus-keranjang.php')) ?>" data-confirm="Kosongkan seluruh keranjang?">
          <?= csrf_field() ?><input type="hidden" name="semua" value="1">
          <button class="btn btn-outline-danger btn-sm" type="submit">Kosongkan keranjang</button>
        </form>
      </div>
    </div>
  </div>
  <aside class="col-lg-4">
    <div class="card card-pad">
      <h2 class="h5 mb-3">Ringkasan belanja</h2>
      <div class="mh-sum"><span class="text-muted">Total harga (<?= $totalQty ?> item)</span><span><?= rupiah($total) ?></span></div>
      <div class="mh-sum"><span class="text-muted">Ongkos kirim</span><span class="fw-600" style="color:#166534">Gratis</span></div>
      <div class="mh-sum tot"><span>Total belanja</span><span style="color:var(--mh-pd)"><?= rupiah($total) ?></span></div>
      <?php if ($masalah): ?>
        <button class="btn btn-primary w-100 mt-3" style="min-height:50px" disabled>Lanjut checkout</button>
        <p class="text-danger small text-center mt-2 mb-0">Perbaiki jumlah produk yang melebihi stok.</p>
      <?php else: ?>
        <a class="btn btn-primary w-100 mt-3" style="min-height:50px" href="<?= e(url('pembeli/checkout.php')) ?>">Lanjut checkout</a>
        <p class="text-muted small text-center mt-3 mb-0">Stok dan harga diperiksa ulang saat checkout.</p>
      <?php endif; ?>
    </div>
  </aside>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>