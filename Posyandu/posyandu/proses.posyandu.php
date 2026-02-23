<?php
    require_once("config.php");
    
    // mendeteksi pengiriman form
    if (isset($_POST['nama_posyandu'])) {
        // siapkan statement untuk insert
        $res = $db->prepare("
                            INSERT INTO posyandu 
                            (nama_posyandu, alamat, kecamatan_id) 
                            VALUES (?, ?, ?)    
                            ");
        // mengeksekusi statement dengan melewatkan parameter
        $res->execute([
            $_POST['nama_posyandu'], 
            $_POST['alamat'],
            $_POST['kecamatan_id']
        ]);
        // melakukan redirect ke halaman sebelumnya
        header('Location: index.php'); exit;
    }

    // mendeteksi pengiriman ID untuk menghapus
    if (isset($_GET['id'])) {
        // siapkan statement untuk delete
        $res = $db->prepare("
                            DELETE FROM posyandu WHERE id = ?  
                            ");
        // mengeksekusi statement dengan melewatkan parameter
        $res->execute([$_GET['id']]);
        // melakukan redirect ke halaman sebelumnya
        header('Location: index.php'); exit;
    }