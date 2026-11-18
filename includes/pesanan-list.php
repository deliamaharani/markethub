<?php
/**
 * Daftar pesanan dashboard. Diset oleh halaman pemanggil: $scope = 'admin' | 'penjual'.
 * Penjual hanya melihat pesanan yang memuat produk miliknya (dipaksa di query SQL).
 */
$self = $scope . '/pesanan/index.php';

// Pembaruan status cepat dari baris tabel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post($self);
    $oid = (int) post('id');
    if ($scope === 'penjual' && !seller_has_order($authUser['id'], $oid)) {
        flash('danger', 'Pesanan tidak ditemukan atau bukan terkait produk Anda.');
    } else {
        [$ok, $msg] = update_order_status($oid, post('status'), $authUser['role']);
        flash($ok ? 'success' : 'danger', $msg);
    }
    redirect($self . page_url());
}

$status = get('status');
$status = in_array($status, STATUS_LIST, true) ? $status : '';
$q = get('q');
$sid = (int) $authUser['id'];

$where = ['1 = 1'];
$params = [];
$extra = '';
if ($scope === 'penjual') {
    $where[] = 'EXISTS (SELECT 1 FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id WHERE d.pesanan_id = o.id AND p.penjual_id = ?)';
    $params[] = $sid;
    $extra = ', (SELECT COALESCE(SUM(d.subtotal), 0) FROM detail_pesanan d JOIN produk p ON p.id = d.produk_id WHERE d.pesanan_id = o.id AND p.penjual_id = ' . $sid . ') AS total_saya';
}
if ($status !== '') {
    $where[] = 'o.status_pesanan = ?';
    $params[] = $status;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $idGuess = preg_match('/(\d+)$/', $q, $m) ? (int) $m[1] : 0;
    $where[] = '(b.nama LIKE ? OR o.id = ?)';
    array_push($params, $like, $idGuess);
}
$from = ' FROM pesanan o JOIN users b ON b.id = o.user_id WHERE ' . implode(' AND ', $where);
$total = (int) db_val('SELECT COUNT(*)' . $from, $params);
[$page, $pages, $offset] = paginate($total, 10, (int) get('page', '1'));
$rows = db_all('SELECT o.*, b.nama AS pembeli_nama' . $extra . $from . ' ORDER BY o.tanggal_pesanan DESC, o.id DESC LIMIT ? OFFSET ?', array_merge($params, [10, $offset]));

$layout = 'dashboard';
$activeMenu = 'pesanan';
$topTitle = $scope === 'admin' ? 'Kelola pesanan' : 'Pesanan masuk';
$topCrumb = 'Dashboard / Pesanan';
$pageTitle = $topTitle;
require __DIR__ . '/header.php';
?>
<div class="card overflow-hidden">
  <form class="mh-tool" method="get">
    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor pesanan atau nama pembeli..." aria-label="Cari pesanan">
    <select class="form-select" name="status" aria-label="Filter status" onchange="this.form.submit()">
      <option value="">Semua status</option>
      <?php foreach (STATUS_LIST as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i>Cari</button>
  </form>
  <div class="table-responsive">
    <table class="table mh-table">
      <thead><tr><th>Nomor pesanan</th><th>Pembeli</th><th>Tanggal</th><th><?= $scope === 'penjual' ? 'Total produk Anda' : 'Total harga' ?></th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $o): $next = allowed_next_status($o['status_pesanan'], $authUser['role']); ?>
        <tr>
          <td><b><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></b></td>
          <td><?= e($o['pembeli_nama']) ?></td>
          <td><?= e(tgl($o['tanggal_pesanan'])) ?></td>
          <td><b><?= rupiah($scope === 'penjual' ? $o['total_saya'] : $o['total_harga']) ?></b></td>
          <td><?= status_badge($o['status_pesanan']) ?></td>
          <td><div class="acts flex-wrap">
            <a class="btn btn-outline-primary btn-sm" href="<?= e(url($scope . '/pesanan/detail.php?id=' . (int) $o['id'])) ?>">Lihat detail</a>
            <?php if ($next): ?>
              <form method="post" class="d-flex gap-1" data-confirm="Perbarui status pesanan ini?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <select class="form-select form-select-sm" name="status" aria-label="Status baru" style="min-height:36px;width:auto">
                  <?php foreach ($next as $n): ?><option value="<?= $n ?>"><?= ucfirst($n) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">Ubah</button>
              </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">Belum ada pesanan yang sesuai.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mh-pgn"><span>Menampilkan <?= count($rows) ?> dari <?= $total ?> pesanan</span><?= pagination_html($page, $pages) ?></div>
</div>
<div class="d-flex gap-2 flex-wrap align-items-center mt-3 small text-muted">Alur status:
  <span class="st st-pending">pending</span>→<span class="st st-diproses">diproses</span>→<span class="st st-dikirim">dikirim</span>→<span class="st st-selesai">selesai</span> atau <span class="st st-dibatalkan">dibatalkan</span></div>
<?php require __DIR__ . '/footer.php'; ?>
