<?php
$host = '127.0.0.1';
$dbname = 'Posyandu Terpadu';
$user = 'root';
$pass = '';

try {
    $db = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Koneksi database gagal: ' . $e->getMessage());
}
?>
