<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';

$q = get('q');
$rows = $q !== ''
    ? db_all('SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.kategori_id = k.id) AS jml FROM kategori k WHERE k.nama_kategori LIKE ? OR k.slug LIKE ? ORDER BY k.id',
        ['%' . addcslashes($q, '%_\\') . '%', '%' . addcslashes($q, '%_\\') . '%'])
    : kategori_all();
$old = $_SESSION['old_kategori'] ?? ['nama' => '', 'slug' => ''];
$err = $_SESSION['err_kategori'] ?? [];
unset($_SESSION['old_kategori'], $_SESSION['err_kategori']);

$layout = 'dashboard';
$activeMenu = 'kategori';
$topTitle = 'Kelola kategori';
$topCrumb = 'Dashboard / Kategori';
$pageTitle = 'Kelola kategori';
require __DIR__ . '/../../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card overflow-hidden">
      <form class="mh-tool" method="get">
        <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kategori..." aria-label="Cari kategori">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i>Cari</button>
        <a class="btn btn-primary ms-auto" href="#form-kategori"><i class="bi bi-plus-lg"></i>Tambah kategori</a>
      </form>
      <div class="table-responsive">
        <table class="table mh-table">
          <thead><tr><th>ID</th><th>Nama kategori</th><th>Slug</th><th>Jumlah produk</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $k): [$bg, $fg, $ic] = kategori_style($k['id']); ?>
            <tr>
              <td class="text-muted"><?= (int) $k['id'] ?></td>
              <td><div class="thumb-box"><span class="mh-img img-thumb" style="width:40px;height:40px;background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $ic ?>" style="font-size:18px"></i></span><b><?= e($k['nama_kategori']) ?></b></div></td>
              <td><code><?= e($k['slug']) ?></code></td>
              <td><?= (int) $k['jml'] ?></td>
              <td><div class="acts">
                <a class="btn btn-outline-secondary ibs" href="<?= e(url('admin/kategori/edit.php?id=' . (int) $k['id'])) ?>" aria-label="Edit kategori"><i class="bi bi-pencil"></i></a>
                <form method="post" action="<?= e(url('admin/kategori/hapus.php')) ?>" data-confirm="Hapus kategori &quot;<?= e($k['nama_kategori']) ?>&quot;?">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                  <button class="btn btn-outline-danger ibs" type="submit" aria-label="Hapus kategori" <?= $k['jml'] > 0 ? 'disabled title="Masih memiliki produk"' : '' ?>><i class="bi bi-trash"></i></button>
                </form>
              </div></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">Kategori tidak ditemukan.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mh-pgn"><span>Menampilkan <?= count($rows) ?> kategori</span></div>
    </div>
  </div>
  <div class="col-lg-4" id="form-kategori">
    <form class="card card-pad needs-validation" method="post" action="<?= e(url('admin/kategori/tambah.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <h2 class="h5 mb-1">Tambah kategori</h2>
      <p class="text-muted small mb-3">Slug dipakai pada URL kategori.</p>
      <div class="mb-3"><label class="form-label" for="nama">Nama kategori</label>
        <input class="form-control<?= isset($err['nama']) ? ' is-invalid' : '' ?>" id="nama" name="nama" value="<?= e($old['nama']) ?>" placeholder="Contoh: Peralatan dapur" required minlength="3" maxlength="100" data-slug-source>
        <div class="invalid-feedback"><?= e($err['nama'] ?? 'Nama kategori 3–100 karakter.') ?></div></div>
      <div class="mb-3"><label class="form-label" for="slug">Slug</label>
        <input class="form-control<?= isset($err['slug']) ? ' is-invalid' : '' ?>" id="slug" name="slug" value="<?= e($old['slug']) ?>" placeholder="peralatan-dapur" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" data-slug-target>
        <div class="form-text">Huruf kecil, tanpa spasi, gunakan tanda hubung. Kosongkan untuk dibuat otomatis.</div>
        <div class="invalid-feedback"><?= e($err['slug'] ?? 'Format slug tidak valid.') ?></div></div>
      <div class="d-flex gap-2"><button class="btn btn-primary flex-fill" type="submit">Simpan</button><button class="btn btn-outline-secondary" type="reset">Batal</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
