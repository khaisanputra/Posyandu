<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$activeMode = 'user';

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = 'Akun berhasil dibuat. Silakan login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activeMode = $_POST['mode'] ?? 'user';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $db->prepare('SELECT id_user, username, password_hash, nama_lengkap, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        $isRoleAllowed = $activeMode === 'pegawai'
            ? ($user && $user['role'] === 'pegawai')
            : ($user && $user['role'] === 'warga');

        if ($isRoleAllowed && $user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int) $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            header('Location: index.php');
            exit;
        }

        $error = $activeMode === 'pegawai'
            ? 'Akun pegawai tidak ditemukan atau password salah.'
            : 'Akun user tidak ditemukan atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Posyandu Terpadu</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body class="login-page ref-login">
  <header class="ref-topbar">
    <div class="ref-topbar-inner">
      <span>Senin - Jumat, 08.00 - 16.00 WIB</span>
      <span>
        <a href="https://wa.me/6282245368544" target="_blank" rel="noopener noreferrer" style="color:#fff; text-decoration:none;">
          <i class="fa-brands fa-whatsapp"></i> +62 822 4536 8544
        </a>
      </span>
    </div>
  </header>

  <nav class="ref-navbar">
    <div class="ref-navbar-inner">
      <div class="ref-brand"><i class="fa-solid fa-house-medical"></i> Posyandu Terpadu</div>
    </div>
  </nav>

  <main class="ref-login-main">
    <section class="ref-login-card">
      <div class="ref-login-head"><i class="fa-solid fa-pen-to-square"></i> Login Posyandu Terpadu</div>
      <div class="ref-login-body">
        <?php if ($success !== ''): ?>
          <p class="auth-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="mode-switch" role="tablist" aria-label="Pilih mode login">
          <button type="button" id="btnUser" class="mode-btn<?= $activeMode === 'user' ? ' active' : '' ?>">User</button>
          <button type="button" id="btnAdmin" class="mode-btn<?= $activeMode === 'pegawai' ? ' active' : '' ?>">Pegawai</button>
        </div>

        <form action="login.php" method="post" id="loginForm" class="auth-form" autocomplete="on">
          <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($activeMode, ENT_QUOTES, 'UTF-8') ?>">

          <label for="username">Username</label>
          <input id="username" type="text" name="username" placeholder="Masukkan username" required>

          <label for="password">Password</label>
          <input id="password" type="password" name="password" placeholder="Masukkan password" required>

          <button type="submit" class="btn-submit">LOGIN</button>
          <p class="auth-help">Belum punya akun user? <a href="register.php">Buat akun</a></p>
        </form>
      </div>
    </section>
  </main>

  <script>
    const btnUser = document.getElementById('btnUser');
    const btnAdmin = document.getElementById('btnAdmin');
    const modeInput = document.getElementById('modeInput');

    function setMode(mode) {
      const isAdmin = mode === 'pegawai';
      modeInput.value = mode;
      btnUser.classList.toggle('active', !isAdmin);
      btnAdmin.classList.toggle('active', isAdmin);
    }

    btnUser.addEventListener('click', () => setMode('user'));
    btnAdmin.addEventListener('click', () => setMode('pegawai'));
    setMode(modeInput.value || 'user');
  </script>
</body>
</html>
