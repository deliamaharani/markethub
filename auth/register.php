<?php
require_once __DIR__ . '/../config/app.php';

if (is_logged()) {
    redirect(role_home(current_user()['role']));
}

$errors = [];
$old = ['nama' => '', 'email' => '', 'role' => 'pembeli'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('auth/register.php');
    $old['nama'] = post('nama');
    $old['email'] = strtolower(post('email'));
    $old['role'] = post('role', 'pembeli');
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if (mb_strlen($old['nama']) < 3 || mb_strlen($old['nama']) > 100) {
        $errors['nama'] = 'Nama lengkap harus 3–100 karakter.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $errors['email'] = 'Masukkan alamat email yang valid.';
    } elseif (db_val('SELECT id FROM users WHERE email = ?', [$old['email']])) {
        $errors['email'] = 'Email sudah terdaftar. Silakan login.';
    }
    if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
        $errors['password'] = 'Password minimal 8 karakter dan mengandung huruf serta angka.';
    }
    if ($pass !== $pass2) {
        $errors['password2'] = 'Konfirmasi password tidak cocok.';
    }
    // Registrasi publik hanya untuk pembeli atau penjual; admin tidak dapat mendaftar.
    if (!in_array($old['role'], ['pembeli', 'penjual'], true)) {
        $errors['role'] = 'Jenis akun tidak valid.';
    }
    if (empty($_POST['setuju'])) {
        $errors['setuju'] = 'Anda harus menyetujui syarat dan ketentuan.';
    }

    if (!$errors) {
        db_exec("INSERT INTO users (nama, email, password, role, status) VALUES (?, ?, ?, ?, 'aktif')",
            [$old['nama'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), $old['role']]);
        flash('success', 'Registrasi berhasil. Silakan login dengan akun Anda.');
        redirect('auth/login.php');
    }
}

$layout = 'auth';
$pageTitle = 'Registrasi';
require __DIR__ . '/../includes/header.php';
$fb = function ($k) use ($errors) {
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
};
$inv = function ($k) use ($errors) {
    return isset($errors[$k]) ? ' is-invalid' : '';
};
?>
<div class="mh-auth">
  <section class="mh-auth-l d-none d-md-flex">
    <a class="mh-logo" href="<?= e(url('index.php')) ?>"><?= logo_svg(44, true) ?><span>Market<b>Hub</b></span></a>
    <div>
      <h1 data-typing>Gabung sebagai pembeli atau penjual.</h1>
      <ul class="list-unstyled mt-4 d-grid gap-3">
        <li class="d-flex gap-3 align-items-center"><i class="bi bi-check-lg fs-5" style="color:#FDE68A"></i>Belanja produk lokal dari banyak penjual</li>
        <li class="d-flex gap-3 align-items-center"><i class="bi bi-check-lg fs-5" style="color:#FDE68A"></i>Pantau status pesanan dengan mudah</li>
        <li class="d-flex gap-3 align-items-center"><i class="bi bi-check-lg fs-5" style="color:#FDE68A"></i>Penjual dapat mengelola produk sendiri</li>
      </ul>
    </div>
    <p class="small mb-0" style="color:#B5E4DE">© <?= date('Y') ?> MarketHub</p>
  </section>

  <section class="mh-auth-r">
    <div class="card" style="max-width:500px">
      <a class="mh-logo d-md-none mb-4" href="<?= e(url('index.php')) ?>"><?= logo_svg(36) ?><span>Market<b>Hub</b></span></a>
      <h2 class="h3 mb-1">Buat akun</h2>
      <p class="text-muted small mb-4">Isi data berikut untuk mulai menggunakan MarketHub.</p>
      <?php require __DIR__ . '/../includes/alert.php'; ?>
      <form method="post" action="<?= e(url('auth/register.php')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="nama">Nama lengkap</label>
          <input class="form-control<?= $inv('nama') ?>" id="nama" name="nama" value="<?= e($old['nama']) ?>" placeholder="Nama sesuai identitas" required minlength="3" maxlength="100" autocomplete="name">
          <div class="invalid-feedback">Nama lengkap minimal 3 karakter.</div><?= $fb('nama') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control<?= $inv('email') ?>" type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="nama@email.com" required maxlength="150" autocomplete="email">
          <div class="invalid-feedback">Masukkan alamat email yang valid.</div><?= $fb('email') ?>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label class="form-label" for="password">Password</label>
            <input class="form-control<?= $inv('password') ?>" type="password" id="password" name="password" data-pw placeholder="Minimal 8 karakter" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password">
            <div class="invalid-feedback">Minimal 8 karakter, kombinasi huruf dan angka.</div><?= $fb('password') ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="password2">Konfirmasi password</label>
            <input class="form-control<?= $inv('password2') ?>" type="password" id="password2" name="password2" data-pw-confirm placeholder="Ulangi password" required autocomplete="new-password">
            <div class="invalid-feedback">Konfirmasi password harus sama.</div><?= $fb('password2') ?>
          </div>
        </div>
        <div class="mb-3">
          <span class="form-label d-block">Jenis akun</span>
          <div class="row g-3">
            <div class="col-sm-6"><label class="mh-role"><input type="radio" name="role" value="pembeli" <?= $old['role'] === 'pembeli' ? 'checked' : '' ?>>
              <span class="card"><b class="d-block">Pembeli</b><span class="text-muted small">Belanja dan melacak pesanan</span></span></label></div>
            <div class="col-sm-6"><label class="mh-role"><input type="radio" name="role" value="penjual" <?= $old['role'] === 'penjual' ? 'checked' : '' ?>>
              <span class="card"><b class="d-block">Penjual</b><span class="text-muted small">Jual dan kelola produk</span></span></label></div>
          </div>
          <div class="form-text">Akun admin dibuat langsung oleh pengelola sistem.</div><?= $fb('role') ?>
        </div>
        <div class="form-check mb-4">
          <input class="form-check-input<?= $inv('setuju') ?>" type="checkbox" id="setuju" name="setuju" value="1" required>
          <label class="form-check-label small" for="setuju">Saya setuju dengan syarat dan ketentuan MarketHub.</label>
          <div class="invalid-feedback">Anda harus menyetujui syarat dan ketentuan.</div>
        </div>
        <button class="btn btn-primary w-100" type="submit" style="min-height:48px">Daftar</button>
      </form>
      <p class="text-center small text-muted mt-4 mb-0">Sudah punya akun? <a class="fw-bold" href="<?= e(url('auth/login.php')) ?>">Masuk</a></p>
    </div>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>