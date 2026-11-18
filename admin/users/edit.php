<?php
require_once __DIR__ . '/../../config/app.php';
$requiredRoles = ['admin'];
require __DIR__ . '/../../includes/auth-check.php';

$id = (int) get('id');
$u = db_row('SELECT * FROM users WHERE id = ?', [$id]);
if (!$u) {
    flash('danger', 'Pengguna tidak ditemukan.');
    redirect('admin/users/index.php');
}
$self = $id === $authUser['id'];
$f = ['nama' => $u['nama'], 'email' => $u['email'], 'role' => $u['role']];
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('admin/users/edit.php?id=' . $id);
    $f = ['nama' => post('nama'), 'email' => strtolower(post('email')), 'role' => post('role', $u['role'])];
    $pass = (string) ($_POST['password'] ?? '');
    if (mb_strlen($f['nama']) < 3 || mb_strlen($f['nama']) > 100) {
        $err['nama'] = 'Nama harus 3–100 karakter.';
    }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 150) {
        $err['email'] = 'Email tidak valid.';
    } elseif (db_val('SELECT id FROM users WHERE email = ? AND id <> ?', [$f['email'], $id])) {
        $err['email'] = 'Email sudah dipakai akun lain.';
    }
    if (!in_array($f['role'], ['admin', 'penjual', 'pembeli'], true)) {
        $err['role'] = 'Role tidak valid.';
    } elseif ($self && $f['role'] !== $u['role']) {
        $err['role'] = 'Anda tidak dapat mengubah role akun Anda sendiri.';
    } elseif ($f['role'] !== $u['role'] && $u['role'] !== 'pembeli' && (int) db_val('SELECT COUNT(*) FROM produk WHERE penjual_id = ?', [$id]) > 0) {
        $err['role'] = 'Pengguna ini masih memiliki produk sehingga role tidak dapat diubah.';
    }
    if ($pass !== '' && (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass))) {
        $err['password'] = 'Password baru minimal 8 karakter dengan huruf dan angka.';
    }
    if (!$err) {
        db_exec('UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?', [$f['nama'], $f['email'], $f['role'], $id]);
        if ($pass !== '') {
            db_exec('UPDATE users SET password = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
        }
        flash('success', 'Data pengguna berhasil diperbarui.');
        redirect('admin/users/detail.php?id=' . $id);
    }
}

$layout = 'dashboard';
$activeMenu = 'users';
$topTitle = 'Edit pengguna';
$topCrumb = 'Dashboard / Pengguna / Edit';
$pageTitle = 'Edit pengguna';
require __DIR__ . '/../../includes/header.php';
$inv = function ($k) use ($err) { return isset($err[$k]) ? ' is-invalid' : ''; };
$fb = function ($k) use ($err) { return isset($err[$k]) ? '<div class="invalid-feedback d-block">' . e($err[$k]) . '</div>' : ''; };
?>
<div class="row"><div class="col-lg-7">
  <form class="card card-pad needs-validation" method="post" novalidate>
    <?= csrf_field() ?>
    <h2 class="h5 mb-3">Edit pengguna #<?= $id ?></h2>
    <div class="mb-3"><label class="form-label" for="nama">Nama lengkap</label>
      <input class="form-control<?= $inv('nama') ?>" id="nama" name="nama" value="<?= e($f['nama']) ?>" required minlength="3" maxlength="100"><div class="invalid-feedback">Nama minimal 3 karakter.</div><?= $fb('nama') ?></div>
    <div class="mb-3"><label class="form-label" for="email">Email</label>
      <input class="form-control<?= $inv('email') ?>" type="email" id="email" name="email" value="<?= e($f['email']) ?>" required maxlength="150"><div class="invalid-feedback">Email tidak valid.</div><?= $fb('email') ?></div>
    <div class="mb-3"><label class="form-label" for="role">Role</label>
      <select class="form-select<?= $inv('role') ?>" id="role" name="role" <?= $self ? 'disabled' : '' ?>>
        <?php foreach (['admin', 'penjual', 'pembeli'] as $r): ?><option value="<?= $r ?>" <?= $f['role'] === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?>
      </select><?php if ($self): ?><input type="hidden" name="role" value="<?= e($u['role']) ?>"><div class="form-text">Role akun Anda sendiri tidak dapat diubah.</div><?php endif; ?><?= $fb('role') ?></div>
    <div class="mb-4"><label class="form-label" for="password">Password baru (opsional)</label>
      <input class="form-control<?= $inv('password') ?>" type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti"><?= $fb('password') ?></div>
    <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Simpan perubahan</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/users/detail.php?id=' . $id)) ?>">Batal</a></div>
  </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
