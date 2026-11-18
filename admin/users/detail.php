<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';

$id = (int) get('id');
$u = db_row('SELECT * FROM users WHERE id = ?', [$id]);
if (!$u) {
    flash('danger', 'Pengguna tidak ditemukan.');
    redirect('admin/users/index.php');
}
$jmlProduk = (int) db_val('SELECT COUNT(*) FROM produk WHERE penjual_id = ?', [$id]);
$jmlPesanan = (int) db_val('SELECT COUNT(*) FROM pesanan WHERE user_id = ?', [$id]);
$belanja = (int) db_val("SELECT COALESCE(SUM(total_harga), 0) FROM pesanan WHERE user_id = ? AND status_pesanan <> 'dibatalkan'", [$id]);
$pesanan = db_all('SELECT * FROM pesanan WHERE user_id = ? ORDER BY tanggal_pesanan DESC, id DESC LIMIT 5', [$id]);
$produk = db_all('SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON k.id = p.kategori_id WHERE p.penjual_id = ? ORDER BY p.id DESC LIMIT 5', [$id]);
$self = $id === $authUser['id'];

$layout = 'dashboard';
$activeMenu = 'users';
$topTitle = 'Detail pengguna';
$topCrumb = 'Dashboard / Pengguna / Detail';
$pageTitle = 'Detail pengguna';
require __DIR__ . '/../../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card card-pad text-center">
      <span class="mh-av mx-auto" style="width:80px;height:80px;font-size:28px"><?= e(initials($u['nama'])) ?></span>
      <h2 class="h5 mt-3 mb-1"><?= e($u['nama']) ?></h2>
      <div class="text-muted small"><?= e($u['email']) ?></div>
      <div class="mt-2 d-flex gap-2 justify-content-center"><span class="st st-<?= e($u['role']) ?>"><?= e($u['role']) ?></span><span class="st st-<?= e($u['status']) ?>"><?= e($u['status']) ?></span></div>
      <hr>
      <div class="mh-sum"><span class="text-muted">ID</span><b><?= (int) $u['id'] ?></b></div>
      <div class="mh-sum"><span class="text-muted">Bergabung</span><span><?= e(tgl($u['created_at'], true)) ?></span></div>
      <?php if ($u['role'] === 'penjual' || $u['role'] === 'admin'): ?><div class="mh-sum"><span class="text-muted">Jumlah produk</span><b><?= $jmlProduk ?></b></div><?php endif; ?>
      <div class="mh-sum"><span class="text-muted">Jumlah pesanan</span><b><?= $jmlPesanan ?></b></div>
      <div class="mh-sum"><span class="text-muted">Total belanja</span><b><?= rupiah($belanja) ?></b></div>
      <div class="d-grid gap-2 mt-3">
        <a class="btn btn-primary" href="<?= e(url('admin/users/edit.php?id=' . $id)) ?>"><i class="bi bi-pencil"></i>Edit pengguna</a>
        <?php if (!$self): ?>
        <form method="post" action="<?= e(url('admin/users/status.php')) ?>" data-confirm="Ubah status akun ini?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="kembali" value="detail">
          <button class="btn <?= $u['status'] === 'aktif' ? 'btn-outline-danger' : 'btn-outline-primary' ?> w-100" type="submit"><?= $u['status'] === 'aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun' ?></button>
        </form>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin/users/index.php')) ?>">Kembali</a>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card overflow-hidden">
      <div class="px-4 pt-4 pb-2"><h2 class="h5 mb-0">Pesanan terakhir</h2></div>
      <div class="table-responsive"><table class="table mh-table">
        <thead><tr><th>Nomor</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($pesanan as $o): ?><tr><td><a class="fw-bold" href="<?= e(url('admin/pesanan/detail.php?id=' . (int) $o['id'])) ?>"><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></a></td><td><?= e(tgl($o['tanggal_pesanan'])) ?></td><td><?= rupiah($o['total_harga']) ?></td><td><?= status_badge($o['status_pesanan']) ?></td></tr><?php endforeach; ?>
        <?php if (!$pesanan): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada pesanan.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div>
    <?php if ($produk): ?>
    <div class="card overflow-hidden mt-4">
      <div class="px-4 pt-4 pb-2"><h2 class="h5 mb-0">Produk terbaru</h2></div>
      <div class="table-responsive"><table class="table mh-table">
        <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th></tr></thead><tbody>
        <?php foreach ($produk as $p): ?><tr><td><div class="thumb-box"><?= produk_img($p, 'img-thumb') ?><a class="fw-bold text-body" href="<?= e(url('admin/produk/detail.php?id=' . (int) $p['id'])) ?>"><?= e($p['nama_produk']) ?></a></div></td><td><?= e($p['nama_kategori']) ?></td><td><?= rupiah($p['harga']) ?></td><td><?= stok_badge($p['stok']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
