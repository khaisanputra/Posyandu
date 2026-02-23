<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Posyandu</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <script src="script.js" defer></script>
</head>
<body data-page="index" data-role="<?= htmlspecialchars(current_user_role(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="sidebar">
    <h2><i class="fa-solid fa-house-medical"></i> Posyandu</h2>
    <ul>
      <li><a href="index.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
      <li><a href="balita.php"><i class="fa-solid fa-baby"></i> Data Balita</a></li>
      <li><a href="ibu.php"><i class="fa-solid fa-person-pregnant"></i> Data Ibu Hamil</a></li>
      <li><a href="imunisasi.php"><i class="fa-solid fa-syringe"></i> Imunisasi</a></li>
      <li><a href="pertumbuhan.php"><i class="fa-solid fa-chart-column"></i> Pertumbuhan</a></li>
      <li><a href="jadwal.php"><i class="fa-solid fa-calendar-day"></i> Jadwal</a></li>
      <li><a href="laporan.php"><i class="fa-solid fa-file-lines"></i> Laporan</a></li>
          <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main">
    <header>
      <h1>Dashboard Posyandu</h1>
      <p>Selamat datang di website posyandu terpadu</p>
    </header>

    <div class="cards">
      <div class="card pink">
        <i class="fa-solid fa-baby"></i>
        <h3>Jumlah Balita</h3>
        <p id="cardBalita">0</p>
      </div>
      <div class="card blue">
        <i class="fa-solid fa-person-pregnant"></i>
        <h3>Ibu Hamil</h3>
        <p id="cardIbu">0</p>
      </div>
      <div class="card green">
        <i class="fa-solid fa-syringe"></i>
        <h3>Total Imunisasi</h3>
        <p id="cardImun">0</p>
      </div>
      <div class="card yellow">
        <i class="fa-solid fa-calendar-day"></i>
        <h3>Jadwal Terdekat</h3>
        <p id="nextJadwal">Belum ada jadwal</p>
      </div>
    </div>
  </div>
</body>
</html>






