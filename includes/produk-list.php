<?php
/**
 * Daftar produk untuk dashboard. Diset oleh halaman pemanggil: $scope = 'admin' | 'penjual'.
 * Penjual hanya melihat produk miliknya sendiri (dipaksa di query SQL).
 */
$q = get('q');
$kat = (int) get('kategori');
$where = ['1 = 1'];
$params = [];
if ($scope === 'penjual') {
    $where[] = 'p.penjual_id = ?';
    $params[] = $authUser['id'];
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(p.nama_produk LIKE ? OR u.nama LIKE ?)';
    array_push($params, $like, $like);
}
if ($kat > 0) {
    $where[] = 'p.kategori_id = ?';
    $params[] = $kat;
}
$from = ' FROM produk p JOIN kategori k ON k.id = p.kategori_id JOIN users u ON u.id = p.penjual_id WHERE ' . implode(' AND ', $where);
$total = (int) db_val('SELECT COUNT(*)' . $from, $params);
[$page, $pages, $offset] = paginate($total, 10, (int) get('page', '1'));
$rows = db_all('SELECT p.*, k.nama_kategori, u.nama AS penjual_nama,
        (SELECT COUNT(*) FROM detail_pesanan d WHERE d.produk_id = p.id) AS riwayat' . $from . ' ORDER BY p.id DESC LIMIT ? OFFSET ?',
    array_merge($params, [10, $offset]));
$kategori = db_all('SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori');

$layout = 'dashboard';
$activeMenu = 'produk';
$topTitle = $scope === 'admin' ? 'Kelola produk' : 'Produk saya';
$topCrumb = 'Dashboard / Produk';
$pageTitle = $topTitle;
require __DIR__ . '/header.php';
?>
<div class="card overflow-hidden">
  <form class="mh-tool" method="get">
    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= $scope === 'admin' ? 'Cari nama produk atau penjual...' : 'Cari nama produk...' ?>" aria-label="Cari produk">
    <select class="form-select" name="kategori" aria-label="Filter kategori" onchange="this.form.submit()">
      <option value="">Semua kategori</option>
      <?php foreach ($kategori as $k): ?><option value="<?= (int) $k['id'] ?>" <?= $kat === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i>Cari</button>
    <a class="btn btn-primary ms-auto" href="<?= e(url($scope . '/produk/tambah.php')) ?>"><i class="bi bi-plus-lg"></i>Tambah produk</a>
  </form>
  <div class="table-responsive">
    <table class="table mh-table">
      <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><?php if ($scope === 'admin'): ?><th>Penjual</th><?php endif; ?><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><div class="thumb-box"><?= produk_img($r, 'img-thumb') ?><div><b><?= e($r['nama_produk']) ?></b><div class="text-muted" style="font-size:12px">ID <?= (int) $r['id'] ?></div></div></div></td>
          <td><?= e($r['nama_kategori']) ?></td>
          <td><b><?= rupiah($r['harga']) ?></b></td>
          <td><?= stok_badge($r['stok']) ?></td>
          <?php if ($scope === 'admin'): ?><td><?= e($r['penjual_nama']) ?></td><?php endif; ?>
          <td><div class="acts">
            <a class="btn btn-outline-secondary ibs" href="<?= e(url($scope === 'admin' ? 'admin/produk/detail.php?id=' . (int) $r['id'] : 'detail-produk.php?id=' . (int) $r['id'])) ?>" aria-label="Detail produk" title="Detail"><i class="bi bi-eye"></i></a>
            <a class="btn btn-outline-secondary ibs" href="<?= e(url($scope . '/produk/edit.php?id=' . (int) $r['id'])) ?>" aria-label="Edit produk" title="Edit"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= e(url($scope . '/produk/hapus.php')) ?>" data-confirm="Hapus produk &quot;<?= e($r['nama_produk']) ?>&quot;? Tindakan ini tidak dapat dibatalkan.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn btn-outline-danger ibs" type="submit" aria-label="Hapus produk" <?= $r['riwayat'] > 0 ? 'disabled title="Memiliki histori transaksi, tidak dapat dihapus"' : 'title="Hapus"' ?>><i class="bi bi-trash"></i></button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="<?= $scope === 'admin' ? 6 : 5 ?>" class="text-center text-muted py-5">Belum ada produk<?= $q !== '' ? ' yang cocok dengan pencarian' : '' ?>.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mh-pgn"><span>Menampilkan <?= count($rows) ?> dari <?= $total ?> produk</span><?= pagination_html($page, $pages) ?></div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
