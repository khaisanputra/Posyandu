<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['role']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user_name(): string {
    return $_SESSION['nama_lengkap'] ?? 'Pengguna';
}

function current_user_role(): string {
    return $_SESSION['role'] ?? 'public';
}

function is_admin(): bool {
    return current_user_role() === 'admin';
}
?>
