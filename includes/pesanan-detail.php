<?php
/**
 * Detail pesanan dashboard. Diset oleh halaman pemanggil: $scope = 'admin' | 'penjual'.
 * Penjual hanya dapat membuka pesanan yang memuat produk miliknya dan hanya melihat item miliknya.
 */
$id = (int) get('id');
$self = $scope . '/pesanan/detail.php?id=' . $id;
$listUrl = $scope . '/pesanan/index.php';

$o = db_row('SELECT o.*, b.nama AS pembeli_nama, b.email AS pembeli_email FROM pesanan o JOIN users b ON b.id = o.user_id WHERE o.id = ?', [$id]);
if (!$o || ($scope === 'penjual' && !seller_has_order($authUser['id'], $id))) {
    flash('danger', 'Pesanan tidak ditemukan atau bukan terkait produk Anda.');
    redirect($listUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post($self);
    [$ok, $msg] = update_order_status($id, post('status'), $authUser['role']);
    flash($ok ? 'success' : 'danger', $msg);
    redirect($self);
}

$items = order_items($id);
if ($scope === 'penjual') {
    $items = array_values(array_filter($items, function ($i) use ($authUser) {
        return (int) $i['penjual_id'] === (int) $authUser['id'];
    }));
}
$totalTampil = array_sum(array_map(function ($i) { return (int) $i['subtotal']; }, $items));
$al = parse_alamat($o['alamat_pengiriman']);
$st = $o['status_pesanan'];
$next = allowed_next_status($st, $authUser['role']);
$alur = ['pending', 'diproses', 'dikirim', 'selesai'];
$posisi = array_search($st, $alur, true);

$layout = 'dashboard';
$activeMenu = 'pesanan';
$topTitle = 'Detail pesanan';
$topCrumb = 'Dashboard / Pesanan / ' . nomor_pesanan($o['id'], $o['tanggal_pesanan']);
$pageTitle = 'Detail pesanan';
require __DIR__ . '/header.php';
?>
<div class="mh-ph"><h1 class="h4"><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?= e(url($listUrl)) ?>">← Kembali ke daftar</a></div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card card-pad">
      <div class="d-flex flex-wrap justify-content-between gap-4">
        <div><div class="text-muted small">Tanggal pesanan</div><b><?= e(tgl($o['tanggal_pesanan'], true)) ?></b></div>
        <div><div class="text-muted small">Pembeli</div><b><?= e($o['pembeli_nama']) ?></b><div class="text-muted small"><?= e($o['pembeli_email']) ?></div></div>
        <div><div class="text-muted small mb-1">Status pesanan</div><?= status_badge($st) ?></div>
      </div>
      <?php if ($st === 'dibatalkan'): ?>
        <div class="alert alert-danger mt-4 mb-0 py-2">Pesanan dibatalkan. Stok produk sudah dikembalikan.</div>
      <?php else: ?>
        <ol class="mh-steps"><?php foreach ($alur as $i => $a): ?><li class="<?= $i <= $posisi ? 'done' : '' ?>"><i></i><?= ucfirst($a) ?></li><?php endforeach; ?></ol>
      <?php endif; ?>
    </div>
    <div class="card mt-4 overflow-hidden">
      <div class="px-4 pt-4 pb-2"><h2 class="h5 mb-0"><?= $scope === 'penjual' ? 'Produk Anda dalam pesanan ini' : 'Produk dalam pesanan' ?></h2></div>
      <div class="table-responsive">
        <table class="table mh-table">
          <thead><tr><th>Produk</th><th>Penjual</th><th>Harga satuan</th><th>Jumlah</th><th class="text-end">Subtotal</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><div class="thumb-box"><?= produk_img($it, 'img-thumb') ?><b><?= e($it['nama_produk']) ?></b></div></td>
              <td><?= e($it['penjual_nama']) ?></td>
              <td><?= rupiah($it['harga_satuan']) ?></td>
              <td><?= (int) $it['jumlah'] ?></td>
              <td class="text-end"><b><?= rupiah($it['subtotal']) ?></b></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between px-4 py-3 border-top fs-5 fw-bold flex-wrap gap-2">
        <span><?= $scope === 'penjual' ? 'Total produk Anda' : 'Total harga' ?></span><span style="color:var(--mh-pd)"><?= rupiah($totalTampil) ?></span></div>
      <?php if ($scope === 'penjual' && $totalTampil != (int) $o['total_harga']): ?>
        <div class="px-4 pb-3 text-muted small">Pesanan ini juga memuat produk dari penjual lain (total pesanan <?= rupiah($o['total_harga']) ?>).</div>
      <?php endif; ?>
    </div>
  </div>
  <aside class="col-lg-4">
    <div class="card card-pad">
      <h2 class="h5 mb-3">Alamat pengiriman</h2>
      <?php if ($al['nama'] !== ''): ?><b><?= e($al['nama']) ?></b><div class="text-muted small"><?= e($al['telp']) ?></div><?php endif; ?>
      <p class="text-muted small mt-2 mb-0" style="line-height:1.6"><?= nl2br(e($al['alamat'])) ?></p>
    </div>
    <form class="card card-pad mt-4" method="post" data-confirm="Perbarui status pesanan ini?">
      <?= csrf_field() ?>
      <h2 class="h5 mb-3" id="status">Perbarui status</h2>
      <?php if ($next): ?>
        <label class="form-label" for="status-baru">Status baru</label>
        <select class="form-select" id="status-baru" name="status"><?php foreach ($next as $n): ?><option value="<?= $n ?>"><?= ucfirst($n) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary w-100 mt-3" type="submit">Simpan status</button>
      <?php else: ?>
        <p class="text-muted small mb-0">Status <b><?= e($st) ?></b> sudah final atau tidak dapat diubah oleh <?= e(role_label($authUser['role'])) ?>.</p>
      <?php endif; ?>
    </form>
  </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>
