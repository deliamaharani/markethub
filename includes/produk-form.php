<?php
/**
 * Form tambah / edit produk untuk dashboard.
 * Diset oleh halaman pemanggil: $scope = 'admin' | 'penjual', $mode = 'tambah' | 'edit'.
 * Penjual hanya dapat mengedit produk miliknya; kepemilikan diperiksa di server.
 */
$id = (int) get('id');
$listUrl = $scope . '/produk/index.php';
$self = $scope . '/produk/' . ($mode === 'edit' ? 'edit.php?id=' . $id : 'tambah.php');
$p = null;
if ($mode === 'edit') {
    $p = produk_find($id);
    if (!$p || ($scope === 'penjual' && (int) $p['penjual_id'] !== $authUser['id'])) {
        flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
        redirect($listUrl);
    }
}
$kategori = db_all('SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori');
$sellers = ($scope === 'admin' && $mode === 'tambah')
    ? db_all("SELECT id, nama, role FROM users WHERE role IN ('penjual','admin') AND status = 'aktif' ORDER BY role, nama") : [];

$f = $p ? ['nama_produk' => $p['nama_produk'], 'kategori_id' => $p['kategori_id'], 'harga' => (int) $p['harga'], 'stok' => $p['stok'], 'deskripsi' => $p['deskripsi'], 'penjual_id' => $p['penjual_id']]
        : ['nama_produk' => '', 'kategori_id' => '', 'harga' => '', 'stok' => '', 'deskripsi' => '', 'penjual_id' => $authUser['id']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post($self);
    [$data, $errors] = produk_validate($_POST);
    $f = array_merge($f, $data, ['harga' => post('harga'), 'stok' => post('stok')]);

    $penjualId = $mode === 'edit' ? (int) $p['penjual_id'] : $authUser['id'];
    if ($scope === 'admin' && $mode === 'tambah') {
        $penjualId = (int) post('penjual_id');
        $f['penjual_id'] = $penjualId;
        if (!in_array($penjualId, array_map('intval', array_column($sellers, 'id')), true)) {
            $errors['penjual_id'] = 'Pilih penjual yang valid.';
        }
    }
    $newImage = null;
    if (!$errors) {
        [$newImage, $uploadErr] = upload_produk_image($_FILES['gambar'] ?? null);
        if ($uploadErr) {
            $errors['gambar'] = $uploadErr;
        }
    }
    if (!$errors) {
        if ($mode === 'tambah') {
            db_exec('INSERT INTO produk (kategori_id, penjual_id, nama_produk, deskripsi, harga, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$data['kategori_id'], $penjualId, $data['nama_produk'], $data['deskripsi'], $data['harga'], $data['stok'], $newImage]);
            flash('success', 'Produk "' . $data['nama_produk'] . '" berhasil ditambahkan.');
        } else {
            // updated_at diperbarui eksplisit pada setiap pembaruan produk
            db_exec('UPDATE produk SET kategori_id = ?, nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, gambar = ?, updated_at = NOW() WHERE id = ?',
                [$data['kategori_id'], $data['nama_produk'], $data['deskripsi'], $data['harga'], $data['stok'], $newImage ?: $p['gambar'], $id]);
            if ($newImage) {
                delete_produk_image($p['gambar']);
            }
            flash('success', 'Produk "' . $data['nama_produk'] . '" berhasil diperbarui.');
        }
        redirect($listUrl);
    }
    if ($newImage) {
        delete_produk_image($newImage);
    }
}

$layout = 'dashboard';
$activeMenu = 'produk';
$topTitle = $mode === 'edit' ? 'Edit produk' : 'Tambah produk';
$topCrumb = 'Dashboard / Produk / ' . ($mode === 'edit' ? 'Edit' : 'Tambah');
$pageTitle = $topTitle;
require __DIR__ . '/header.php';
$inv = function ($k) use ($errors) {
    return isset($errors[$k]) ? ' is-invalid' : '';
};
$fb = function ($k) use ($errors) {
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
};
?>
<form method="post" action="<?= e(url($self)) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card card-pad">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
          <h2 class="h5 mb-0">Informasi produk</h2>
          <?php if ($p): ?><span class="st" style="background:#F1F5F3;color:var(--mh-mut)">ID produk <?= (int) $p['id'] ?></span><?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="nama_produk">Nama produk</label>
          <input class="form-control<?= $inv('nama_produk') ?>" id="nama_produk" name="nama_produk" value="<?= e($f['nama_produk']) ?>" placeholder="Contoh: Kopi Gayo Arabika 250g" required minlength="3" maxlength="150">
          <div class="invalid-feedback">Nama produk 3–150 karakter.</div><?= $fb('nama_produk') ?>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label" for="kategori_id">Kategori</label>
            <select class="form-select<?= $inv('kategori_id') ?>" id="kategori_id" name="kategori_id" required>
              <option value="">Pilih kategori</option>
              <?php foreach ($kategori as $k): ?><option value="<?= (int) $k['id'] ?>" <?= (int) $f['kategori_id'] === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Pilih kategori produk.</div><?= $fb('kategori_id') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="penjual">Penjual</label>
            <?php if ($sellers): ?>
              <select class="form-select<?= $inv('penjual_id') ?>" id="penjual" name="penjual_id" required>
                <?php foreach ($sellers as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (int) $f['penjual_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nama']) ?> (<?= e($s['role']) ?>)</option><?php endforeach; ?>
              </select><?= $fb('penjual_id') ?>
            <?php else: ?>
              <input class="form-control" id="penjual" value="<?= e($p ? $p['penjual_nama'] : $authUser['nama']) ?>" readonly>
              <div class="form-text"><?= $p ? 'Penjual tidak dapat diubah.' : 'Terisi otomatis dari akun yang login.' ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label" for="harga">Harga</label>
            <div class="input-group has-validation">
              <span class="input-group-text">Rp</span>
              <input class="form-control<?= $inv('harga') ?>" id="harga" name="harga" value="<?= e($f['harga']) ?>" inputmode="numeric" placeholder="0" required pattern="[0-9.]+">
              <div class="invalid-feedback">Masukkan harga berupa angka.</div>
            </div><?= $fb('harga') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="stok">Stok</label>
            <input class="form-control<?= $inv('stok') ?>" type="number" id="stok" name="stok" value="<?= e($f['stok']) ?>" min="0" max="1000000" placeholder="0" required>
            <div class="form-text">Jumlah barang yang tersedia.</div>
            <div class="invalid-feedback">Stok harus angka 0 atau lebih.</div><?= $fb('stok') ?>
          </div>
        </div>
        <div>
          <label class="form-label" for="deskripsi">Deskripsi produk</label>
          <textarea class="form-control<?= $inv('deskripsi') ?>" id="deskripsi" name="deskripsi" rows="6" required minlength="10" maxlength="3000" placeholder="Jelaskan bahan, ukuran, keunggulan, dan cara penggunaan produk."><?= e($f['deskripsi']) ?></textarea>
          <div class="invalid-feedback">Deskripsi minimal 10 karakter.</div><?= $fb('deskripsi') ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card card-pad">
        <h2 class="h5 mb-3">Gambar produk</h2>
        <div class="mh-preview" id="preview"><?= produk_img($p ?: ['kategori_id' => (int) $f['kategori_id'] ?: 1, 'nama_produk' => 'Produk'], 'img-card') ?></div>
        <?php if ($p && $p['gambar']): ?><div class="text-muted small mb-2"><?= e($p['gambar']) ?></div><?php endif; ?>
        <div class="mh-upl">
          <i class="bi bi-cloud-arrow-up"></i>
          <div class="fw-bold mt-1" style="color:var(--mh-ink)"><?= $p ? 'Ganti gambar produk' : 'Unggah gambar produk' ?></div>
          <div class="small mt-1">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
          <input class="form-control mt-3" type="file" name="gambar" id="gambar" accept="image/jpeg,image/png,image/webp" data-preview="#preview" aria-label="Pilih file gambar">
        </div>
        <div class="text-danger small mt-2" data-file-error></div><?= $fb('gambar') ?>
        <?php if ($p): ?><div class="form-text mt-2">Kosongkan jika tidak ingin mengganti gambar.</div><?php endif; ?>
      </div>
      <?php if ($p): ?>
      <div class="card card-pad mt-4">
        <div class="mh-sum"><span class="text-muted">Dibuat</span><span><?= e(tgl($p['created_at'])) ?></span></div>
        <div class="mh-sum"><span class="text-muted">Terakhir diperbarui</span><span><?= e(tgl($p['updated_at'], true)) ?></span></div>
      </div>
      <?php endif; ?>
      <div class="card card-pad mt-4 d-grid gap-2">
        <button class="btn btn-primary" type="submit" style="min-height:48px"><?= $mode === 'edit' ? 'Perbarui produk' : 'Simpan produk' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url($listUrl)) ?>">Batal</a>
      </div>
    </div>
  </div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
