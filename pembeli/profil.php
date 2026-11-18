<?php
/** Profil akun — dapat diakses semua role yang sudah login. */
require_once __DIR__ . '/../config/app.php';
$requiredRoles = ['admin', 'penjual', 'pembeli'];
require __DIR__ . '/../includes/auth-check.php';

$errors = [];
$f = ['nama' => $authUser['nama'], 'email' => $authUser['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('pembeli/profil.php');
    if (post('aksi') === 'profil') {
        $f = ['nama' => post('nama'), 'email' => strtolower(post('email'))];
        if (mb_strlen($f['nama']) < 3 || mb_strlen($f['nama']) > 100) {
            $errors['nama'] = 'Nama harus 3–100 karakter.';
        }
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 150) {
            $errors['email'] = 'Email tidak valid.';
        } elseif (db_val('SELECT id FROM users WHERE email = ? AND id <> ?', [$f['email'], $authUser['id']])) {
            $errors['email'] = 'Email sudah dipakai akun lain.';
        }
        if (!$errors) {
            db_exec('UPDATE users SET nama = ?, email = ? WHERE id = ?', [$f['nama'], $f['email'], $authUser['id']]);
            flash('success', 'Profil berhasil diperbarui.');
            redirect('pembeli/profil.php');
        }
    } elseif (post('aksi') === 'password') {
        $lama = (string) ($_POST['password_lama'] ?? '');
        $baru = (string) ($_POST['password_baru'] ?? '');
        $konf = (string) ($_POST['password_konf'] ?? '');
        $hash = db_val('SELECT password FROM users WHERE id = ?', [$authUser['id']]);
        if (!password_verify($lama, (string) $hash)) {
            $errors['password_lama'] = 'Password saat ini salah.';
        }
        if (strlen($baru) < 8 || !preg_match('/[A-Za-z]/', $baru) || !preg_match('/\d/', $baru)) {
            $errors['password_baru'] = 'Password baru minimal 8 karakter dengan huruf dan angka.';
        }
        if ($baru !== $konf) {
            $errors['password_konf'] = 'Konfirmasi password tidak cocok.';
        }
        if (!$errors) {
            db_exec('UPDATE users SET password = ? WHERE id = ?', [password_hash($baru, PASSWORD_DEFAULT), $authUser['id']]);
            flash('success', 'Password berhasil diubah.');
            redirect('pembeli/profil.php');
        }
    }
}

$stat = [];
if ($authUser['role'] === 'pembeli') {
    $stat[] = ['Total pesanan', (int) db_val('SELECT COUNT(*) FROM pesanan WHERE user_id = ?', [$authUser['id']])];
    $stat[] = ['Item di keranjang', cart_count($authUser['id'])];
} elseif ($authUser['role'] === 'penjual') {
    $stat[] = ['Produk saya', (int) db_val('SELECT COUNT(*) FROM produk WHERE penjual_id = ?', [$authUser['id']])];
}

$dash = $authUser['role'] !== 'pembeli';
$layout = $dash ? 'dashboard' : 'public';
if (!$dash) {
    $bodyClass = 'mh-wide';   // layout lebar hanya untuk tampilan pembeli
}
$activeMenu = 'profil';
$topTitle = 'Profil saya';
$topCrumb = 'Informasi akun dan keamanan';
$pageTitle = 'Profil saya';
require __DIR__ . '/../includes/header.php';
$inv = function ($k) use ($errors) {
    return isset($errors[$k]) ? ' is-invalid' : '';
};
$fb = function ($k) use ($errors) {
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
};
?>
<?php if (!$dash): ?>
<nav aria-label="Breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
  <li class="breadcrumb-item active" aria-current="page">Profil saya</li>
</ol></nav>
<div class="mh-ph"><h1>Profil saya</h1></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card card-pad text-center">
      <span class="mh-av mx-auto" style="width:80px;height:80px;font-size:28px"><?= e(initials($authUser['nama'])) ?></span>
      <h2 class="h5 mt-3 mb-1"><?= e($authUser['nama']) ?></h2>
      <div class="text-muted small"><?= e($authUser['email']) ?></div>
      <div class="mt-2"><span class="st st-<?= e($authUser['role']) ?>"><?= e(role_label($authUser['role'])) ?></span></div>
      <hr>
      <div class="mh-sum"><span class="text-muted">Bergabung</span><span><?= e(tgl($authUser['created_at'])) ?></span></div>
      <div class="mh-sum"><span class="text-muted">Status akun</span><span class="st st-aktif">aktif</span></div>
      <?php foreach ($stat as [$l, $v]): ?><div class="mh-sum"><span class="text-muted"><?= e($l) ?></span><b><?= $v ?></b></div><?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-8">
    <form class="card card-pad needs-validation" method="post" novalidate>
      <?= csrf_field() ?><input type="hidden" name="aksi" value="profil">
      <h2 class="h5 mb-3">Data profil</h2>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="nama">Nama lengkap</label>
          <input class="form-control<?= $inv('nama') ?>" id="nama" name="nama" value="<?= e($f['nama']) ?>" required minlength="3" maxlength="100">
          <div class="invalid-feedback">Nama minimal 3 karakter.</div><?= $fb('nama') ?></div>
        <div class="col-md-6"><label class="form-label" for="email">Email</label>
          <input class="form-control<?= $inv('email') ?>" type="email" id="email" name="email" value="<?= e($f['email']) ?>" required maxlength="150">
          <div class="invalid-feedback">Email tidak valid.</div><?= $fb('email') ?></div>
      </div>
      <div class="mt-4"><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
    </form>

    <form class="card card-pad mt-4 needs-validation" method="post" novalidate>
      <?= csrf_field() ?><input type="hidden" name="aksi" value="password">
      <h2 class="h5 mb-3">Ubah password</h2>
      <div class="row g-3">
        <div class="col-12"><label class="form-label" for="pl">Password saat ini</label>
          <input class="form-control<?= $inv('password_lama') ?>" type="password" id="pl" name="password_lama" required autocomplete="current-password">
          <div class="invalid-feedback">Password saat ini wajib diisi.</div><?= $fb('password_lama') ?></div>
        <div class="col-md-6"><label class="form-label" for="pb">Password baru</label>
          <input class="form-control<?= $inv('password_baru') ?>" type="password" id="pb" name="password_baru" data-pw required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password">
          <div class="invalid-feedback">Minimal 8 karakter, huruf dan angka.</div><?= $fb('password_baru') ?></div>
        <div class="col-md-6"><label class="form-label" for="pk">Konfirmasi password baru</label>
          <input class="form-control<?= $inv('password_konf') ?>" type="password" id="pk" name="password_konf" data-pw-confirm required autocomplete="new-password">
          <div class="invalid-feedback">Konfirmasi harus sama dengan password baru.</div><?= $fb('password_konf') ?></div>
      </div>
      <div class="mt-4"><button class="btn btn-outline-primary" type="submit">Ubah password</button></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>