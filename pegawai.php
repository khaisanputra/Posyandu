<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_login();

$role = current_user_role();
if ($role !== 'pegawai') {
    header('Location: index.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$error = '';
$success = '';

function can_manage_target(array $target, string $currentRole, int $currentUserId): bool {
    if ($currentRole === 'pegawai') {
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_profile') {
            $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $newPassword = $_POST['password_baru'] ?? '';

            if ($namaLengkap === '' || $username === '') {
                throw new RuntimeException('Nama lengkap dan username wajib diisi.');
            }

            $dup = $db->prepare('SELECT id_user FROM users WHERE username = :username AND id_user <> :id LIMIT 1');
            $dup->execute([':username' => $username, ':id' => $userId]);
            if ($dup->fetch()) {
                throw new RuntimeException('Username sudah digunakan akun lain.');
            }

            if ($newPassword !== '') {
                if (strlen($newPassword) < 6) {
                    throw new RuntimeException('Password baru minimal 6 karakter.');
                }
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, password_hash = :password_hash WHERE id_user = :id');
                $stmt->execute([
                    ':username' => $username,
                    ':nama_lengkap' => $namaLengkap,
                    ':password_hash' => $hash,
                    ':id' => $userId,
                ]);
            } else {
                $stmt = $db->prepare('UPDATE users SET username = :username, nama_lengkap = :nama_lengkap WHERE id_user = :id');
                $stmt->execute([
                    ':username' => $username,
                    ':nama_lengkap' => $namaLengkap,
                    ':id' => $userId,
                ]);
            }

            $_SESSION['username'] = $username;
            $_SESSION['nama_lengkap'] = $namaLengkap;
            $success = 'Profil pegawai berhasil diperbarui.';
        }

        if ($action === 'create_user') {
            $namaLengkap = trim($_POST['new_nama_lengkap'] ?? '');
            $username = trim($_POST['new_username'] ?? '');
            $password = $_POST['new_password'] ?? '';
            $targetRole = $_POST['new_role'] ?? 'warga';

            if ($namaLengkap === '' || $username === '' || $password === '') {
                throw new RuntimeException('Data user baru wajib lengkap.');
            }
            if (strlen($password) < 6) {
                throw new RuntimeException('Password user baru minimal 6 karakter.');
            }

            $dup = $db->prepare('SELECT id_user FROM users WHERE username = :username LIMIT 1');
            $dup->execute([':username' => $username]);
            if ($dup->fetch()) {
                throw new RuntimeException('Username user baru sudah dipakai.');
            }

            $stmt = $db->prepare('INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES (:username, :password_hash, :nama_lengkap, :role)');
            $stmt->execute([
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':nama_lengkap' => $namaLengkap,
                ':role' => $targetRole,
            ]);

            $success = 'User baru berhasil dibuat.';
        }

        if ($action === 'update_user') {
            $targetId = (int) ($_POST['target_id'] ?? 0);
            $namaLengkap = trim($_POST['edit_nama_lengkap'] ?? '');
            $username = trim($_POST['edit_username'] ?? '');
            $targetRole = $_POST['edit_role'] ?? 'warga';
            $newPassword = $_POST['edit_password'] ?? '';

            if ($targetId <= 0 || $namaLengkap === '' || $username === '') {
                throw new RuntimeException('Data edit user tidak valid.');
            }

            $q = $db->prepare('SELECT id_user, role FROM users WHERE id_user = :id LIMIT 1');
            $q->execute([':id' => $targetId]);
            $target = $q->fetch();
            if (!$target) {
                throw new RuntimeException('User target tidak ditemukan.');
            }
            if (!can_manage_target($target, $role, $userId)) {
                throw new RuntimeException('Anda tidak diizinkan mengubah user ini.');
            }

            $dup = $db->prepare('SELECT id_user FROM users WHERE username = :username AND id_user <> :id LIMIT 1');
            $dup->execute([':username' => $username, ':id' => $targetId]);
            if ($dup->fetch()) {
                throw new RuntimeException('Username sudah digunakan akun lain.');
            }

            if ($newPassword !== '' && strlen($newPassword) < 6) {
                throw new RuntimeException('Password edit minimal 6 karakter.');
            }

            if ($newPassword !== '') {
                $stmt = $db->prepare('UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, role = :role, password_hash = :password_hash WHERE id_user = :id');
                $stmt->execute([
                    ':username' => $username,
                    ':nama_lengkap' => $namaLengkap,
                    ':role' => $targetRole,
                    ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    ':id' => $targetId,
                ]);
            } else {
                $stmt = $db->prepare('UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, role = :role WHERE id_user = :id');
                $stmt->execute([
                    ':username' => $username,
                    ':nama_lengkap' => $namaLengkap,
                    ':role' => $targetRole,
                    ':id' => $targetId,
                ]);
            }

            if ($targetId === $userId) {
                $_SESSION['username'] = $username;
                $_SESSION['nama_lengkap'] = $namaLengkap;
                $_SESSION['role'] = $targetRole;
            }

            $success = 'Data user berhasil diperbarui.';
        }

        if ($action === 'delete_user') {
            $targetId = (int) ($_POST['target_id'] ?? 0);
            if ($targetId <= 0) {
                throw new RuntimeException('Target hapus tidak valid.');
            }
            if ($targetId === $userId) {
                throw new RuntimeException('Akun yang sedang dipakai tidak bisa dihapus.');
            }

            $q = $db->prepare('SELECT id_user, role FROM users WHERE id_user = :id LIMIT 1');
            $q->execute([':id' => $targetId]);
            $target = $q->fetch();
            if (!$target) {
                throw new RuntimeException('User target tidak ditemukan.');
            }
            if (!can_manage_target($target, $role, $userId)) {
                throw new RuntimeException('Anda tidak diizinkan menghapus user ini.');
            }

            $stmt = $db->prepare('DELETE FROM users WHERE id_user = :id');
            $stmt->execute([':id' => $targetId]);

            $success = 'User berhasil dihapus.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$meStmt = $db->prepare('SELECT id_user, username, nama_lengkap, role FROM users WHERE id_user = :id LIMIT 1');
$meStmt->execute([':id' => $userId]);
$me = $meStmt->fetch();

if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$usersStmt = $db->query('SELECT id_user, username, nama_lengkap, role FROM users ORDER BY id_user DESC');
$users = $usersStmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pegawai - Posyandu</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <script src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>" defer></script>
</head>
<body data-page="pegawai" data-role="<?= htmlspecialchars(current_user_role(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="sidebar">
    <h2><i class="fa-solid fa-house-medical"></i> Posyandu</h2>
    <ul>
      <li><a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
      <li><a href="balita.php"><i class="fa-solid fa-baby"></i> Data Balita</a></li>
      <li><a href="ibu.php"><i class="fa-solid fa-person-pregnant"></i> Data Ibu Hamil</a></li>
      <li><a href="imunisasi.php"><i class="fa-solid fa-syringe"></i> Imunisasi</a></li>
      <li><a href="pertumbuhan.php"><i class="fa-solid fa-chart-column"></i> Pertumbuhan</a></li>
      <li><a href="jadwal.php"><i class="fa-solid fa-calendar-day"></i> Jadwal</a></li>
      <li><a href="laporan.php"><i class="fa-solid fa-file-lines"></i> Laporan</a></li>
      <li><a href="pegawai.php" class="active"><i class="fa-solid fa-users-gear"></i> Pegawai</a></li>
      <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main">
    <header><h1>Menu Pegawai</h1></header>

    <?php if ($error !== ''): ?>
      <p class="auth-error" style="max-width: 720px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <p class="auth-success" style="max-width: 720px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form class="form" method="post">
      <h3>Profil Pegawai</h3>
      <input type="hidden" name="action" value="update_profile">
      <label>Nama Lengkap</label>
      <input name="nama_lengkap" value="<?= htmlspecialchars($me['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" required>
      <label>Username</label>
      <input name="username" value="<?= htmlspecialchars($me['username'], ENT_QUOTES, 'UTF-8') ?>" required>
      <label>Password Baru (opsional)</label>
      <input name="password_baru" type="password" minlength="6" placeholder="Kosongkan jika tidak diubah">
      <button type="submit">Simpan Profil</button>
    </form>

    <form class="form" method="post" style="margin-top: 16px;">
      <h3>Manage User</h3>
      <input type="hidden" name="action" value="create_user">
      <label>Nama Lengkap</label>
      <input name="new_nama_lengkap" required>
      <label>Username</label>
      <input name="new_username" required>
      <label>Password</label>
      <input name="new_password" type="password" minlength="6" required>
      <label>Role</label>
      <select name="new_role">
        <option value="warga">Warga</option>
        <option value="pegawai">Pegawai</option>
      </select>
      <button type="submit">Tambah User</button>
    </form>

    <div class="table-box">
      <table>
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Lengkap</th>
            <th>Username</th>
            <th>Role</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $idx => $u): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td><?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="actions">
                <details>
                  <summary class="btn-edit small" style="display:inline-block; cursor:pointer;">Edit</summary>
                  <form method="post" class="form" style="margin-top:10px; max-width:420px;">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="target_id" value="<?= (int) $u['id_user'] ?>">
                    <label>Nama Lengkap</label>
                    <input name="edit_nama_lengkap" value="<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <label>Username</label>
                    <input name="edit_username" value="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <label>Password Baru (opsional)</label>
                    <input name="edit_password" type="password" minlength="6" placeholder="Kosongkan jika tidak diubah">
                    <label>Role</label>
                    <select name="edit_role">
                      <option value="warga" <?= $u['role'] === 'warga' ? 'selected' : '' ?>>Warga</option>
                      <option value="pegawai" <?= $u['role'] === 'pegawai' ? 'selected' : '' ?>>Pegawai</option>
                    </select>
                    <button type="submit">Simpan Perubahan</button>
                  </form>
                </details>

                <?php if ((int) $u['id_user'] !== $userId): ?>
                  <form method="post" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="target_id" value="<?= (int) $u['id_user'] ?>">
                    <button type="submit" class="btn-delete small">Hapus</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>

