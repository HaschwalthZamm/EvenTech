<?php
/**
 * includes/guard.php — Proteksi halaman yang butuh login
 * Gunakan require di awal setiap halaman terproteksi
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

/**
 * guard_admin() — Hanya izinkan role admin
 */
function guard_admin(): void {
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: event.php');
        exit;
    }
}

// Shortcut: data user aktif (dengan fallback aman)
$currentUser = [
    'id'    => $_SESSION['user_id']    ?? 0,
    'nama'  => $_SESSION['user_nama']  ?? 'Unknown',
    'email' => $_SESSION['user_email'] ?? '',
    'role'  => $_SESSION['user_role']  ?? 'user',
];