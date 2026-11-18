<?php
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['pembeli'];
require __DIR__ . '/../includes/auth-check.php';

$status = get('status');
$status = in_array($status, STATUS_LIST, true) ? $status : '';
$counts = array_column(db_all('SELECT status_pesanan AS s, COUNT(*) AS n FROM pesanan WHERE user_id = ? GROUP BY status_pesanan', [$authUser['id']]), 'n', 's');
$all = array_sum($counts);

$where = 'o.user_id = ?';
$params = [$authUser['id']];
if ($status !== '') {
    $where .= ' AND o.status_pesanan = ?';
    $params[] = $status;
}
$total = (int) db_val("SELECT COUNT(*) FROM pesanan o WHERE $where", $params);
[$page, $pages, $offset] = paginate($total, 10, (int) get('page', '1'));
$orders = db_all("SELECT o.*,
        (SELECT COUNT(*) FROM detail_pesanan d WHERE d.pesanan_id = o.id) AS jml_produk,
        (SELECT COALESCE(SUM(d.jumlah), 0) FROM detail_pesanan d WHERE d.pesanan_id = o.id) AS jml_item
    FROM pesanan o WHERE $where ORDER BY o.tanggal_pesanan DESC, o.id DESC LIMIT ? OFFSET ?", array_merge($params, [10, $offset]));

$layout = 'public';
$bodyClass = 'mh-wide';
$pageTitle = 'Riwayat pesanan';
require __DIR__ . '/../includes/header.php';
?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item active" aria-current="page">Riwayat pesanan</li>
</ol></nav>
<div class="mh-ph"><h1>Riwayat pesanan</h1><a class="btn btn-outline-primary btn-sm" href="<?= e(url('beranda.php')) ?>">Belanja lagi</a></div>

<div class="card overflow-hidden">
  <div class="px-3 pt-3 pb-1 border-bottom">
    <div class="mh-cats" style="padding:0 0 12px">
      <a class="<?= $status === '' ? 'on' : '' ?>" href="<?= e(page_url(['status' => '', 'page' => ''])) ?>">Semua (<?= $all ?>)</a>
      <?php foreach (STATUS_LIST as $s): ?>
        <a class="<?= $status === $s ? 'on' : '' ?>" href="<?= e(page_url(['status' => $s, 'page' => ''])) ?>"><?= ucfirst($s) ?> (<?= (int) ($counts[$s] ?? 0) ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table mh-table">
      <thead><tr><th>Nomor pesanan</th><th>Tanggal pesanan</th><th>Total harga</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><b><?= e(nomor_pesanan($o['id'], $o['tanggal_pesanan'])) ?></b><div class="text-muted" style="font-size:12px"><?= (int) $o['jml_produk'] ?> produk · <?= (int) $o['jml_item'] ?> item</div></td>
          <td><?= e(tgl($o['tanggal_pesanan'])) ?></td>
          <td><b><?= rupiah($o['total_harga']) ?></b></td>
          <td><?= status_badge($o['status_pesanan']) ?></td>
          <td><a class="btn btn-outline-primary btn-sm" href="<?= e(url('pembeli/detail-pesanan.php?id=' . (int) $o['id'])) ?>">Detail pesanan</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?>
        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada pesanan<?= $status ? ' dengan status ' . e($status) : '' ?>.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mh-pgn"><span>Menampilkan <?= count($orders) ?> dari <?= $total ?> pesanan</span><?= pagination_html($page, $pages) ?></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>