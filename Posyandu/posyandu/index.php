<?php 
    require_once("config.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data  Posyandu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>
<body>

    <form action="proses.posyandu.php" method="post">
        <h3>Input Data Posyandu</h3>
        <label for="nama_posyandu">Nama Posyandu</label><br>
        <input type="text" name="nama_posyandu" id="nama_posyandu" required><br>
        <label for="alamat">Alamat</label><br>
        <input type="text" name="alamat" id="alamat" required><br>
        <label for="kecamatan">Kecamatan</label><br>
            <select name="kecamatan_id" id="kecamatan_id">
                <?php 
                    $res_kec = $db->prepare("SELECT * FROM kecamatan ORDER BY nama");
                    // mengeksekusi resource PDO
                    $res_kec->execute();
                    // melakukan loop untuk menampilkan data dari database
                    while ($row_kec = $res_kec->fetchObject()):
                ?>    
                    <option value="<?=  $row_kec->id ?>"><?= $row_kec->nama ?></option>
                <?php
                    endwhile; 
                ?>        
            </select><br>
        <button class="btn btn-primary" type="submit">Simpan</button>
    </form>

    <table class="table">
        <thead>
            <th>No.</th>
            <th>Nama Posyandu</th>
            <th>Alamat</th>
            <th>Kecamatan</th>
            <th>Aksi</th>
        </thead>
        <tbody>
<?php 
    // menyiapkan statemen PDO menjadi resource
    $res_posyandu = $db->prepare("
                                  SELECT *, kecamatan.nama AS nama_kecamatan FROM posyandu
                                  LEFT JOIN kecamatan ON kecamatan.id = posyandu.kecamatan_id
                                  ");
    // mengeksekusi resource PDO
    $res_posyandu->execute();
    $i = 0;
    // melakukan loop untuk menampilkan data dari database
    while ($row_posyandu = $res_posyandu->fetchObject()) {
?>    
    <tr>
        <td><?= ++$i ?>.</td>
        <td><?= $row_posyandu->nama_posyandu ?></td>
        <td><?= $row_posyandu->alamat ?></td>  
        <td><?= $row_posyandu->nama_kecamatan ?></td>
        <td><a class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin untuk menghapus ?')" 
            href="proses.posyandu.php?id=<?= $row_posyandu->id ?>">Hapus</a></td>
    </tr>
<?php } ?>
    </tbody>    
    </table>
</body>
</html>