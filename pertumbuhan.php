<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Pertumbuhan - Posyandu</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <script src="script.js" defer></script>
</head>
<body data-page="pertumbuhan" data-role="<?= htmlspecialchars(current_user_role(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="sidebar">
    <h2><i class="fa-solid fa-house-medical"></i> Posyandu</h2>
    <ul>
      <li><a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
      <li><a href="balita.php"><i class="fa-solid fa-baby"></i> Data Balita</a></li>
      <li><a href="ibu.php"><i class="fa-solid fa-person-pregnant"></i> Data Ibu Hamil</a></li>
      <li><a href="imunisasi.php"><i class="fa-solid fa-syringe"></i> Imunisasi</a></li>
      <li><a href="pertumbuhan.php" class="active"><i class="fa-solid fa-chart-column"></i> Pertumbuhan</a></li>
      <li><a href="jadwal.php"><i class="fa-solid fa-calendar-day"></i> Jadwal</a></li>
      <li><a href="laporan.php"><i class="fa-solid fa-file-lines"></i> Laporan</a></li>
          <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main">
    <header><h1>Pertumbuhan Balita</h1></header>

    <form class="form" id="growthForm">
      <label>Nama Balita</label>
      <input name="nama" placeholder="Nama balita" required>
      <label>Berat Badan (kg)</label>
      <input name="berat" type="number" step="0.1" required>
      <label>Tinggi Badan (cm)</label>
      <input name="tinggi" type="number" step="0.1" required>
      <label>Tanggal</label>
      <input name="tanggal" type="date" required>
      <button type="submit">Simpan</button>
    </form>

    <div class="table-box">
      <table>
        <thead><tr><th>No</th><th>Nama</th><th>Berat (kg)</th><th>Tinggi (cm)</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody id="tbodyGrowth"></tbody>
      </table>
    </div>
  </div>
</body>
</html>








