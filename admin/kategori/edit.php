<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';

$id = (int) get('id');
$k = db_row('SELECT * FROM kategori WHERE id = ?', [$id]);
if (!$k) {
    flash('danger', 'Kategori tidak ditemukan.');
    redirect('admin/kategori/index.php');
}
$f = ['nama' => $k['nama_kategori'], 'slug' => $k['slug']];
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('admin/kategori/edit.php?id=' . $id);
    $f = ['nama' => post('nama'), 'slug' => slugify(post('slug') !== '' ? post('slug') : post('nama'))];
    if (mb_strlen($f['nama']) < 3 || mb_strlen($f['nama']) > 100) {
        $err['nama'] = 'Nama kategori harus 3–100 karakter.';
    }
    if ($f['slug'] === '' || strlen($f['slug']) > 120) {
        $err['slug'] = 'Slug tidak valid.';
    } elseif (db_val('SELECT id FROM kategori WHERE slug = ? AND id <> ?', [$f['slug'], $id])) {
        $err['slug'] = 'Slug sudah dipakai kategori lain.';
    }
    if (!$err) {
        db_exec('UPDATE kategori SET nama_kategori = ?, slug = ? WHERE id = ?', [$f['nama'], $f['slug'], $id]);
        flash('success', 'Kategori berhasil diperbarui.');
        redirect('admin/kategori/index.php');
    }
}

$layout = 'dashboard';
$activeMenu = 'kategori';
$topTitle = 'Edit kategori';
$topCrumb = 'Dashboard / Kategori / Edit';
$pageTitle = 'Edit kategori';
require __DIR__ . '/../../includes/header.php';
?>
<div class="row"><div class="col-lg-6">
  <form class="card card-pad needs-validation" method="post" novalidate>
    <?= csrf_field() ?>
    <h2 class="h5 mb-3">Edit kategori #<?= (int) $k['id'] ?></h2>
    <div class="mb-3"><label class="form-label" for="nama">Nama kategori</label>
      <input class="form-control<?= isset($err['nama']) ? ' is-invalid' : '' ?>" id="nama" name="nama" value="<?= e($f['nama']) ?>" required minlength="3" maxlength="100" data-slug-source>
      <div class="invalid-feedback"><?= e($err['nama'] ?? 'Nama kategori 3–100 karakter.') ?></div></div>
    <div class="mb-3"><label class="form-label" for="slug">Slug</label>
      <input class="form-control<?= isset($err['slug']) ? ' is-invalid' : '' ?>" id="slug" name="slug" value="<?= e($f['slug']) ?>" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" data-slug-target>
      <div class="invalid-feedback"><?= e($err['slug'] ?? 'Format slug tidak valid.') ?></div></div>
    <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Simpan perubahan</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/kategori/index.php')) ?>">Batal</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
