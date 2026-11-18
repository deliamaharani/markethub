<?php
/** Detail produk — dapat diakses tanpa login. Menambah ke keranjang membutuhkan login (pembeli). */
require_once __DIR__ . '/config/app.php';

$p = produk_find((int) get('id'));
if (!$p || $p['penjual_status'] !== 'aktif') {
    require __DIR__ . '/404.php';
    exit;
}
$u = current_user();
$stok = (int) $p['stok'];
$related = db_all("SELECT p.*, k.nama_kategori, u.nama AS penjual_nama FROM produk p
    JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.penjual_id
    WHERE p.kategori_id = ? AND p.id <> ? AND u.status = 'aktif' ORDER BY p.created_at DESC LIMIT 4",
    [(int) $p['kategori_id'], (int) $p['id']]);
$canBuy = !$u || $u['role'] === 'pembeli';

$layout = 'public';
$bodyClass = 'mh-wide';
$pageTitle = $p['nama_produk'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item"><a href="<?= e(url('kategori.php?slug=' . $p['kategori_slug'])) ?>"><?= e($p['nama_kategori']) ?></a></li>
  <li class="breadcrumb-item active" aria-current="page"><?= e($p['nama_produk']) ?></li>
</ol></nav>

<div class="row g-4 g-lg-5">
  <div class="col-lg-6"><?= produk_img($p, 'img-big') ?></div>
  <div class="col-lg-6">
    <span class="st" style="background:var(--mh-pl);color:var(--mh-pd)"><?= e($p['nama_kategori']) ?></span>
    <h1 class="mt-2" style="font-size:32px;line-height:1.2"><?= e($p['nama_produk']) ?></h1>
    <div class="mt-3" style="font-size:34px;font-weight:800;color:var(--mh-pd);letter-spacing:-.02em"><?= rupiah($p['harga']) ?></div>

    <div class="card mt-3 px-3 py-1">
      <div class="mh-sum py-2"><span class="text-muted">Penjual</span><b class="d-flex align-items-center gap-2"><span class="mh-av" style="width:26px;height:26px;font-size:11px"><?= e(initials($p['penjual_nama'])) ?></span><?= e($p['penjual_nama']) ?></b></div>
      <div class="mh-sum py-2 border-top"><span class="text-muted">Kategori</span><b><?= e($p['nama_kategori']) ?></b></div>
      <div class="mh-sum py-2 border-top"><span class="text-muted">Ketersediaan stok</span>
        <?php if ($stok <= 0): ?><span class="st st-dibatalkan">Stok habis</span>
        <?php else: ?><span class="st st-selesai">Tersedia · <?= $stok ?> pcs</span><?php endif; ?></div>
    </div>

    <?php if ($canBuy): ?>
    <form method="post" action="<?= e(url('pembeli/tambah-keranjang.php')) ?>" class="mt-4">
      <?= csrf_field() ?>
      <input type="hidden" name="produk_id" value="<?= (int) $p['id'] ?>">
      <label class="form-label" for="jumlah">Jumlah pembelian</label>
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="mh-qty" data-qty data-max="<?= max(1, $stok) ?>">
          <button type="button" data-act="minus" aria-label="Kurangi" <?= $stok <= 0 ? 'disabled' : '' ?>><i class="bi bi-dash-lg"></i></button>
          <input type="number" id="jumlah" name="jumlah" value="1" min="1" max="<?= max(1, $stok) ?>" <?= $stok <= 0 ? 'disabled' : '' ?> aria-label="Jumlah">
          <button type="button" data-act="plus" aria-label="Tambah" <?= $stok <= 0 ? 'disabled' : '' ?>><i class="bi bi-plus-lg"></i></button>
        </div>
        <span class="text-muted small">Maksimal <?= $stok ?> pcs</span>
      </div>
      <div class="d-flex gap-3 flex-wrap mt-4">
        <button class="btn btn-primary flex-fill" type="submit" style="min-height:50px" <?= $stok <= 0 ? 'disabled' : '' ?>>
          <i class="bi bi-cart-plus"></i><?= $u ? 'Tambah ke keranjang' : 'Masuk &amp; tambah ke keranjang' ?></button>
        <a class="btn btn-outline-primary" style="min-height:50px" href="<?= e(url('beranda.php')) ?>">Lanjut belanja</a>
      </div>
    </form>
    <?php else: ?>
      <div class="mh-note mt-4">Anda login sebagai <b><?= e(role_label($u['role'])) ?></b>. Hanya akun pembeli yang dapat menambahkan produk ke keranjang.</div>
      <?php if ($u['role'] === 'admin' || (int) $u['id'] === (int) $p['penjual_id']): ?>
        <a class="btn btn-outline-primary mt-3" href="<?= e(url($u['role'] === 'admin' ? 'admin/produk/edit.php?id=' . (int) $p['id'] : 'penjual/produk/edit.php?id=' . (int) $p['id'])) ?>"><i class="bi bi-pencil"></i>Edit produk ini</a>
      <?php endif; ?>
    <?php endif; ?>

    <div class="card card-pad mt-4">
      <h2 class="h6 fw-bold mb-2">Deskripsi produk</h2>
      <p class="text-muted mb-0" style="line-height:1.7"><?= nl2br(e($p['deskripsi'])) ?></p>
    </div>
  </div>
</div>

<?php if ($related): ?>
  <div class="mh-sh"><h2>Produk lain di <?= e($p['nama_kategori']) ?></h2><a class="fw-600 small" href="<?= e(url('kategori.php?slug=' . $p['kategori_slug'])) ?>">Lihat semua</a></div>
  <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-4">
    <?php foreach ($related as $r) { echo render_product_card($r); } ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>