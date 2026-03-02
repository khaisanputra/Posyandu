<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan - Posyandu</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <script src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>" defer></script>
</head>
<body data-page="laporan" data-role="<?= htmlspecialchars(current_user_role(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="sidebar">
    <h2><i class="fa-solid fa-house-medical"></i> Posyandu</h2>
    <ul>
      <li><a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
      <li><a href="balita.php"><i class="fa-solid fa-baby"></i> Data Balita</a></li>
      <li><a href="ibu.php"><i class="fa-solid fa-person-pregnant"></i> Data Ibu Hamil</a></li>
      <li><a href="imunisasi.php"><i class="fa-solid fa-syringe"></i> Imunisasi</a></li>
      <li><a href="pertumbuhan.php"><i class="fa-solid fa-chart-column"></i> Pertumbuhan</a></li>
      <li><a href="jadwal.php"><i class="fa-solid fa-calendar-day"></i> Jadwal</a></li>
      <li><a href="laporan.php" class="active"><i class="fa-solid fa-file-lines"></i> Laporan</a></li>
      <?php if (current_user_role() === 'pegawai'): ?>
      <li><a href="pegawai.php"><i class="fa-solid fa-users-gear"></i> Pegawai</a></li>
      <?php endif; ?>
      <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main">
    <header><h1>Laporan Posyandu</h1></header>
    <div class="form" id="laporanBox"></div>
  </div>
</body>
</html>












