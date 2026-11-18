<?php
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['pembeli'];
require __DIR__ . '/../includes/auth-check.php';

$id = (int) get('id');
$o = db_row('SELECT * FROM pesanan WHERE id = ? AND user_id = ?', [$id, $authUser['id']]);
if (!$o) {
    require __DIR__ . '/../404.php';
    exit;
}

// Aksi pembeli: batalkan pesanan (pending) atau konfirmasi pesanan diterima (dikirim)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('pembeli/detail-pesanan.php?id=' . $id);
    $aksi = post('aksi');
    $target = $aksi === 'batal' ? 'dibatalkan' : ($aksi === 'terima' ? 'selesai' : '');
    if ($target === '') {
        flash('danger', 'Aksi tidak dikenal.');
    } else {
        [$ok, $msg] = update_order_status($id, $target, 'pembeli');
        flash($ok ? 'success' : 'danger', $msg);
    }
    redirect('pembeli/detail-pesanan.php?id=' . $id);
}

$items = order_items($id);
$al = parse_alamat($o['alamat_pengiriman']);
$st = $o['status_pesanan'];
$aksiTersedia = allowed_next_status($st, 'pembeli');
$alur = ['pending', 'diproses', 'dikirim', 'selesai'];
$posisi = array_search($st, $alur, true);

$layout = 'public';
$pageTitle = 'Detail pesanan';
require __DIR__ . '/../includes/header.php';
?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item"><a href="<?= e(url('pembeli/riwayat-pesanan.php')) ?>">Riwayat pesanan</a></li>
  <li class="breadcrumb-item active" aria-current="page"><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></li>
</ol></nav>
<div class="mh-ph"><h1>Detail pesanan</h1><a class="btn btn-outline-secondary btn-sm" href="<?= e(url('pembeli/riwayat-pesanan.php')) ?>">← Kembali ke riwayat</a></div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card card-pad">
      <div class="d-flex flex-wrap justify-content-between gap-4">
        <div><div class="text-muted small">Nomor pesanan</div><b class="fs-5"><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></b></div>
        <div><div class="text-muted small">Tanggal pesanan</div><b><?= e(tgl($o['tanggal_pesanan'], true)) ?></b></div>
        <div><div class="text-muted small mb-1">Status pesanan</div><?= status_badge($st) ?></div>
      </div>
      <?php if ($st === 'dibatalkan'): ?>
        <div class="alert alert-danger mt-4 mb-0 py-2">Pesanan ini telah dibatalkan. Stok produk sudah dikembalikan.</div>
      <?php else: ?>
        <ol class="mh-steps">
          <?php foreach ($alur as $i => $a): ?>
            <li class="<?= $i <= $posisi ? 'done' : '' ?>"><i></i><?= ucfirst($a) ?></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>

    <div class="card mt-4 overflow-hidden">
      <div class="px-4 pt-4 pb-2"><h2 class="h5 mb-0">Produk yang dibeli</h2></div>
      <div class="table-responsive">
        <table class="table mh-table">
          <thead><tr><th>Produk</th><th>Harga satuan</th><th>Jumlah</th><th class="text-end">Subtotal</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><div class="thumb-box"><?= produk_img($it, 'img-thumb') ?><div><a class="fw-bold text-body" href="<?= e(url('detail-produk.php?id=' . (int) $it['produk_id'])) ?>"><?= e($it['nama_produk']) ?></a><div class="text-muted" style="font-size:12px"><?= e($it['penjual_nama']) ?></div></div></div></td>
              <td><?= rupiah($it['harga_satuan']) ?></td>
              <td><?= (int) $it['jumlah'] ?></td>
              <td class="text-end"><b><?= rupiah($it['subtotal']) ?></b></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between px-4 py-3 border-top fs-5 fw-bold flex-wrap gap-2"><span>Total harga</span><span style="color:var(--mh-pd)"><?= rupiah($o['total_harga']) ?></span></div>
    </div>
  </div>

  <aside class="col-lg-4">
    <div class="card card-pad">
      <h2 class="h5 mb-3">Alamat pengiriman</h2>
      <?php if ($al['nama'] !== ''): ?><b><?= e($al['nama']) ?></b><div class="text-muted small"><?= e($al['telp']) ?></div><?php endif; ?>
      <p class="text-muted small mt-2 mb-0" style="line-height:1.6"><?= nl2br(e($al['alamat'])) ?></p>
    </div>
    <?php if ($aksiTersedia): ?>
      <div class="card card-pad mt-4" style="background:#FEF3C7;border-color:#F6DE97">
        <?php if (in_array('dibatalkan', $aksiTersedia, true)): ?>
          <b style="color:#6B3A04">Menunggu diproses penjual</b>
          <p class="small mt-1 mb-3" style="color:#6B3A04">Anda dapat membatalkan pesanan selama status masih pending.</p>
          <form method="post" data-confirm="Batalkan pesanan ini? Tindakan tidak dapat dibatalkan.">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="batal">
            <button class="btn btn-outline-danger btn-sm" type="submit">Batalkan pesanan</button>
          </form>
        <?php else: ?>
          <b style="color:#6B3A04">Pesanan sedang dikirim</b>
          <p class="small mt-1 mb-3" style="color:#6B3A04">Jika barang sudah Anda terima, konfirmasi agar pesanan selesai.</p>
          <form method="post" data-confirm="Konfirmasi bahwa pesanan sudah diterima?">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="terima">
            <button class="btn btn-primary btn-sm" type="submit">Pesanan diterima</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
