<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';

$q = get('q');
$role = get('role');
$role = in_array($role, ['admin', 'penjual', 'pembeli'], true) ? $role : '';
$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(nama LIKE ? OR email LIKE ?)';
    array_push($params, $like, $like);
}
if ($role !== '') {
    $where[] = 'role = ?';
    $params[] = $role;
}
$total = (int) db_val('SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $where), $params);
[$page, $pages, $offset] = paginate($total, 10, (int) get('page', '1'));
$rows = db_all('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT ? OFFSET ?', array_merge($params, [10, $offset]));

$layout = 'dashboard';
$activeMenu = 'users';
$topTitle = 'Kelola pengguna';
$topCrumb = 'Dashboard / Pengguna';
$pageTitle = 'Kelola pengguna';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card overflow-hidden">
  <form class="mh-tool" method="get">
    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau email..." aria-label="Cari pengguna">
    <select class="form-select" name="role" aria-label="Filter role" onchange="this.form.submit()">
      <option value="">Semua role</option>
      <?php foreach (['admin', 'penjual', 'pembeli'] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i>Cari</button>
  </form>
  <div class="table-responsive">
    <table class="table mh-table">
      <thead><tr><th>Pengguna</th><th>Role</th><th>Status</th><th>Bergabung</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $self = (int) $r['id'] === $authUser['id']; ?>
        <tr>
          <td><div class="thumb-box"><span class="mh-av"><?= e(initials($r['nama'])) ?></span><div><b><?= e($r['nama']) ?></b><?= $self ? ' <span class="text-muted small">(Anda)</span>' : '' ?><div class="text-muted" style="font-size:12px"><?= e($r['email']) ?></div></div></div></td>
          <td><span class="st st-<?= e($r['role']) ?>"><?= e($r['role']) ?></span></td>
          <td><span class="st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td><?= e(tgl($r['created_at'])) ?></td>
          <td><div class="acts">
            <a class="btn btn-outline-secondary ibs" href="<?= e(url('admin/users/detail.php?id=' . (int) $r['id'])) ?>" aria-label="Detail" title="Detail"><i class="bi bi-eye"></i></a>
            <a class="btn btn-outline-secondary ibs" href="<?= e(url('admin/users/edit.php?id=' . (int) $r['id'])) ?>" aria-label="Edit" title="Edit"><i class="bi bi-pencil"></i></a>
            <?php if (!$self): ?>
            <form method="post" action="<?= e(url('admin/users/status.php')) ?>" data-confirm="<?= $r['status'] === 'aktif' ? 'Nonaktifkan akun ini? Pengguna tidak dapat login.' : 'Aktifkan kembali akun ini?' ?>">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn <?= $r['status'] === 'aktif' ? 'btn-outline-danger' : 'btn-outline-primary' ?> ibs" type="submit" aria-label="Ubah status akun" title="<?= $r['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="bi <?= $r['status'] === 'aktif' ? 'bi-person-slash' : 'bi-person-check' ?>"></i></button>
            </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">Pengguna tidak ditemukan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mh-pgn"><span>Menampilkan <?= count($rows) ?> dari <?= $total ?> pengguna</span><?= pagination_html($page, $pages) ?></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
