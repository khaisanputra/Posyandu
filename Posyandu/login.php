<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$activeMode = 'guest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activeMode = $_POST['mode'] ?? 'guest';

    if ($activeMode === 'guest') {
        $guestName = trim($_POST['guest_name'] ?? 'Pengunjung');
        if ($guestName === '') {
            $guestName = 'Pengunjung';
        }

        $_SESSION['user_id'] = 0;
        $_SESSION['username'] = 'guest';
        $_SESSION['nama_lengkap'] = $guestName;
        $_SESSION['role'] = 'user';

        header('Location: index.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password admin wajib diisi.';
    } else {
        $stmt = $db->prepare('SELECT id_user, username, password_hash, nama_lengkap, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int) $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            header('Location: index.php');
            exit;
        }

        $error = 'Username atau password admin salah.';
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
        <?php if ($error !== ''): ?>
          <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="mode-switch" role="tablist" aria-label="Pilih mode login">
          <button type="button" id="btnGuest" class="mode-btn<?= $activeMode === 'guest' ? ' active' : '' ?>">User Biasa</button>
          <button type="button" id="btnAdmin" class="mode-btn<?= $activeMode === 'admin' ? ' active' : '' ?>">Admin</button>
        </div>

        <form action="login.php" method="post" id="loginForm" class="auth-form" autocomplete="on">
          <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($activeMode, ENT_QUOTES, 'UTF-8') ?>">

          <div id="guestFields" class="mode-fields<?= $activeMode === 'guest' ? ' show' : '' ?>">
            <label for="guest_name">Nama User</label>
            <input id="guest_name" type="text" name="guest_name" placeholder="Contoh: Siswanto RT 04">
          </div>

          <div id="adminFields" class="mode-fields<?= $activeMode === 'admin' ? ' show' : '' ?>">
            <label for="username">Username Admin</label>
            <input id="username" type="text" name="username" placeholder="Masukkan username">

            <label for="password">Password Admin</label>
            <input id="password" type="password" name="password" placeholder="Masukkan password">
          </div>

          <button type="submit" class="btn-submit">LOGIN</button>
        </form>
      </div>
    </section>
  </main>

  <script>
    const btnGuest = document.getElementById('btnGuest');
    const btnAdmin = document.getElementById('btnAdmin');
    const modeInput = document.getElementById('modeInput');
    const guestFields = document.getElementById('guestFields');
    const adminFields = document.getElementById('adminFields');
    const username = document.getElementById('username');
    const password = document.getElementById('password');

    function setMode(mode) {
      const isAdmin = mode === 'admin';
      modeInput.value = mode;

      btnGuest.classList.toggle('active', !isAdmin);
      btnAdmin.classList.toggle('active', isAdmin);
      guestFields.classList.toggle('show', !isAdmin);
      adminFields.classList.toggle('show', isAdmin);

      username.required = isAdmin;
      password.required = isAdmin;
    }

    btnGuest.addEventListener('click', () => setMode('guest'));
    btnAdmin.addEventListener('click', () => setMode('admin'));

    setMode(modeInput.value || 'guest');
  </script>
</body>
</html>
