<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($namaLengkap === '' || $username === '' || $password === '' || $confirmPassword === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $check = $db->prepare('SELECT id_user FROM users WHERE username = :username LIMIT 1');
        $check->execute([':username' => $username]);

        if ($check->fetch()) {
            $error = 'Username sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $db->prepare('INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES (:username, :password_hash, :nama_lengkap, :role)');
            $insert->execute([
                ':username' => $username,
                ':password_hash' => $hash,
                ':nama_lengkap' => $namaLengkap,
                ':role' => 'warga',
            ]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Akun - Posyandu Terpadu</title>
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
      <div class="ref-login-head"><i class="fa-solid fa-user-plus"></i> Buat Akun User</div>
      <div class="ref-login-body">
        <?php if ($error !== ''): ?>
          <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
          <p class="auth-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="register.php" method="post" class="auth-form" autocomplete="on">
          <label for="nama_lengkap">Nama Lengkap</label>
          <input id="nama_lengkap" type="text" name="nama_lengkap" required placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <label for="username">Username</label>
          <input id="username" type="text" name="username" required placeholder="Minimal 4 karakter" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <label for="password">Password</label>
          <input id="password" type="password" name="password" required placeholder="Minimal 6 karakter">

          <label for="confirm_password">Konfirmasi Password</label>
          <input id="confirm_password" type="password" name="confirm_password" required placeholder="Ulangi password">

          <button type="submit" class="btn-submit">BUAT AKUN</button>
          <p class="auth-help">Sudah punya akun? <a href="login.php">Kembali ke login</a></p>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
