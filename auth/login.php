<?php
require_once __DIR__ . '/../config/app.php';

if (is_logged()) {
    redirect(role_home(current_user()['role']));
}

// Tujuan setelah login (kembali ke halaman sebelumnya)
if (isset($_GET['next'])) {
    $_SESSION['next'] = safe_next($_GET['next']);
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post('auth/login.php');
    $email = strtolower(post('email'));
    $pass = (string) ($_POST['password'] ?? '');

    if (!empty($_SESSION['login_lock']) && $_SESSION['login_lock'] > time()) {
        $errors['umum'] = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($_SESSION['login_lock'] - time()) . ' detik.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
        $errors['umum'] = 'Masukkan email yang valid dan password.';
    } else {
        $row = db_row('SELECT id, nama, email, password, role, status FROM users WHERE email = ?', [$email]);
        // password_verify tetap dijalankan walau email tidak ditemukan (menyamakan waktu respons)
        $hash = $row['password'] ?? '$2y$10$usesomesillystringforsaltuQ9Z5s2h0nQe0m4v3u0D6Qk8k2hGq1e';
        $ok = password_verify($pass, $hash);
        if (!$row || !$ok) {
            $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
            if ($_SESSION['login_fail'] >= 5) {
                $_SESSION['login_lock'] = time() + 60;
                $_SESSION['login_fail'] = 0;
            }
            $errors['umum'] = 'Email atau password salah.';
        } elseif ($row['status'] !== 'aktif') {
            $errors['umum'] = 'Akun Anda dinonaktifkan. Hubungi administrator MarketHub.';
        } else {
            $pending = $_SESSION['pending_cart'] ?? null;
            $next = $_SESSION['next'] ?? '';
            login_user($row);
            unset($_SESSION['next'], $_SESSION['pending_cart']);

            // Lanjutkan tindakan "tambah ke keranjang" yang tertunda sebelum login
            if ($pending && $row['role'] === 'pembeli') {
                [$okAdd, $msg] = cart_add((int) $row['id'], (int) $pending['produk_id'], (int) $pending['jumlah']);
                flash($okAdd ? 'success' : 'warning', $msg);
                redirect($okAdd ? 'pembeli/keranjang.php' : 'detail-produk.php?id=' . (int) $pending['produk_id']);
            }
            flash('success', 'Selamat datang, ' . $row['nama'] . '!');
            redirect($next !== '' ? $next : role_home($row['role']));
        }
    }
}

$layout = 'auth';
$pageTitle = 'Masuk';
require __DIR__ . '/../includes/header.php';
?>
<div class="mh-auth">
  <section class="mh-auth-l d-none d-md-flex">
    <a class="mh-logo" href="<?= e(url('index.php')) ?>"><?= logo_svg(44, true) ?><span>Market<b>Hub</b></span></a>
    <div>
      <h1 data-typing>Selamat datang kembali di pasar digital Anda.</h1>
      <p class="mt-3" style="max-width:440px">Masuk untuk melanjutkan belanja, memantau pesanan, atau mengelola produk dagangan Anda.</p>
      <div class="mh-tiles mt-4" aria-hidden="true">
        <span style="width:96px;height:96px;background:#FBE9C8;color:#7A4A06"><i class="bi bi-cup-hot"></i></span>
        <span style="width:96px;height:96px;background:#E8E1F5;color:#4C3A8A"><i class="bi bi-handbag"></i></span>
        <span style="width:96px;height:96px;background:#DDEBD0;color:#2F5A14"><i class="bi bi-flower1"></i></span>
      </div>
    </div>
    <p class="small mb-0" style="color:#B5E4DE">© <?= date('Y') ?> MarketHub</p>
  </section>

  <section class="mh-auth-r">
    <div class="card">
      <a class="mh-logo d-md-none mb-4" href="<?= e(url('index.php')) ?>"><?= logo_svg(36) ?><span>Market<b>Hub</b></span></a>
      <h2 class="h3 mb-1">Masuk</h2>
      <p class="text-muted small mb-4">Gunakan email dan password akun MarketHub Anda.</p>
      <?php require __DIR__ . '/../includes/alert.php'; ?>
      <?php if (!empty($errors['umum'])): ?>
        <div class="alert alert-danger py-2" role="alert"><?= e($errors['umum']) ?></div>
      <?php endif; ?>
      <form method="post" action="<?= e(url('auth/login.php')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@email.com" required autocomplete="email">
          <div class="invalid-feedback">Masukkan alamat email yang valid.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <input class="form-control" type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" data-toggle-pw="#password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
            <div class="invalid-feedback">Password wajib diisi.</div>
          </div>
        </div>
        <button class="btn btn-primary w-100 mt-2" type="submit" style="min-height:48px">Masuk</button>
      </form>
      <p class="text-center small text-muted mt-4 mb-0">Belum punya akun? <a class="fw-bold" href="<?= e(url('auth/register.php')) ?>">Daftar sekarang</a></p>
      <p class="text-center small mt-2 mb-0"><a href="<?= e(url('beranda.php')) ?>">← Lanjut melihat produk tanpa login</a></p>
    </div>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>