<?php
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['pembeli'];
require __DIR__ . '/../includes/auth-check.php';

$items = cart_items($authUser['id']);
if (!$items) {
    flash('warning', 'Keranjang Anda kosong. Tambahkan produk terlebih dahulu.');
    redirect('pembeli/keranjang.php');
}

// Isi awal form dari alamat pesanan terakhir (jika ada)
$last = db_val('SELECT alamat_pengiriman FROM pesanan WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$authUser['id']]);
$prev = $last ? parse_alamat($last) : ['nama' => $authUser['nama'], 'telp' => '', 'alamat' => ''];
$f = ['nama' => $prev['nama'] ?: $authUser['nama'], 'telp' => $prev['telp'], 'alamat' => $prev['alamat']];
$errors = [];

$total = 0;
$totalQty = 0;
$masalah = false;
foreach ($items as $it) {
    $total += (int) $it['subtotal'];
    $totalQty += (int) $it['jumlah'];
    $masalah = $masalah || (int) $it['stok'] < (int) $it['jumlah'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('pembeli/checkout.php');
    $f = ['nama' => post('nama'), 'telp' => post('telp'), 'alamat' => post('alamat')];
    if (mb_strlen($f['nama']) < 3 || mb_strlen($f['nama']) > 100 || strpos($f['nama'], '|') !== false) {
        $errors['nama'] = 'Nama penerima harus 3–100 karakter (tanpa tanda |).';
    }
    if (!preg_match('/^\+?[0-9][0-9\-\s]{8,16}$/', $f['telp'])) {
        $errors['telp'] = 'Nomor telepon tidak valid (9–16 digit).';
    }
    if (mb_strlen($f['alamat']) < 10 || mb_strlen($f['alamat']) > 500) {
        $errors['alamat'] = 'Alamat lengkap harus 10–500 karakter.';
    }
    if (!$errors) {
        $res = create_order($authUser['id'], $f['nama'], preg_replace('/[\s\-]/', '', $f['telp']), $f['alamat']);
        if ($res['ok']) {
            flash('success', 'Pesanan berhasil dibuat dengan status pending. Terima kasih telah berbelanja!');
            redirect('pembeli/detail-pesanan.php?id=' . (int) $res['id']);
        }
        flash($res['code'] === 'error' ? 'danger' : 'warning', $res['msg']);
        redirect($res['code'] === 'stok' || $res['code'] === 'kosong' ? 'pembeli/keranjang.php' : 'pembeli/checkout.php');
    }
}

$layout = 'public';
$bodyClass = 'mh-wide';
$pageTitle = 'Checkout';
require __DIR__ . '/../includes/header.php';
$inv = function ($k) use ($errors) {
    return isset($errors[$k]) ? ' is-invalid' : '';
};
$fb = function ($k) use ($errors) {
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
};
?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item"><a href="<?= e(url('pembeli/keranjang.php')) ?>">Keranjang</a></li>
  <li class="breadcrumb-item active" aria-current="page">Checkout</li>
</ol></nav>
<div class="mh-ph"><h1>Checkout</h1></div>

<form method="post" action="<?= e(url('pembeli/checkout.php')) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card card-pad">
        <h2 class="h5 mb-1">Alamat pengiriman</h2>
        <p class="text-muted small mb-4">Pastikan data penerima benar agar pesanan sampai dengan lancar.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="nama">Nama penerima</label>
            <input class="form-control<?= $inv('nama') ?>" id="nama" name="nama" value="<?= e($f['nama']) ?>" required minlength="3" maxlength="100">
            <div class="invalid-feedback">Nama penerima minimal 3 karakter.</div><?= $fb('nama') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="telp">Nomor telepon</label>
            <input class="form-control<?= $inv('telp') ?>" id="telp" name="telp" value="<?= e($f['telp']) ?>" inputmode="tel" placeholder="08xxxxxxxxxx" required pattern="\+?[0-9][0-9\- ]{8,16}">
            <div class="invalid-feedback">Masukkan nomor telepon yang valid.</div><?= $fb('telp') ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="alamat">Alamat lengkap</label>
            <textarea class="form-control<?= $inv('alamat') ?>" id="alamat" name="alamat" rows="4" required minlength="10" maxlength="500" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kota, kode pos"><?= e($f['alamat']) ?></textarea>
            <div class="form-text">Tuliskan nama jalan, nomor rumah, RT/RW, kelurahan, kota, dan kode pos.</div>
            <div class="invalid-feedback">Alamat lengkap minimal 10 karakter.</div><?= $fb('alamat') ?>
          </div>
        </div>
      </div>

      <div class="card card-pad mt-4">
        <h2 class="h5 mb-2">Produk yang dibeli</h2>
        <?php foreach ($items as $it): ?>
          <div class="d-flex align-items-center gap-3 py-3 border-top flex-wrap">
            <?= produk_img(['kategori_id' => $it['kategori_id'], 'gambar' => $it['gambar'], 'nama_produk' => $it['nama_produk']], 'img-sm') ?>
            <div class="flex-grow-1" style="min-width:160px"><b class="d-block"><?= e($it['nama_produk']) ?></b>
              <span class="text-muted small"><?= rupiah($it['harga']) ?> × <?= (int) $it['jumlah'] ?></span></div>
            <b><?= rupiah($it['subtotal']) ?></b>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <aside class="col-lg-4">
      <div class="card card-pad">
        <h2 class="h5 mb-3">Ringkasan pesanan</h2>
        <div class="mh-sum"><span class="text-muted">Jumlah produk</span><span><?= count($items) ?> produk (<?= $totalQty ?> item)</span></div>
        <div class="mh-sum"><span class="text-muted">Total harga</span><span><?= rupiah($total) ?></span></div>
        <div class="mh-sum"><span class="text-muted">Ongkos kirim</span><span class="fw-600" style="color:#166534">Gratis</span></div>
        <div class="mh-sum tot"><span>Total pembayaran</span><span style="color:var(--mh-pd)"><?= rupiah($total) ?></span></div>
        <div class="mh-note mt-3">Status pesanan setelah dikonfirmasi: <b>pending</b>. Stok dan harga diperiksa ulang saat pesanan dibuat.</div>
        <button class="btn btn-primary w-100 mt-3" type="submit" style="min-height:50px" <?= $masalah ? 'disabled' : '' ?>>Konfirmasi pesanan</button>
        <?php if ($masalah): ?><p class="text-danger small mt-2 mb-0">Ada produk yang melebihi stok. Perbaiki di keranjang.</p><?php endif; ?>
        <a class="btn btn-outline-secondary w-100 mt-2" href="<?= e(url('pembeli/keranjang.php')) ?>">Kembali ke keranjang</a>
      </div>
    </aside>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>