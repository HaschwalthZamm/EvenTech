<?php
/**
 * auth.php — Logic autentikasi login & register
 * EvenTech Platform
 */

session_start();
require_once 'koneksi.php';

// Jika sudah login, langsung redirect ke event.php
if (isset($_SESSION['user_id'])) {
    redirect('event.php');
}

$action = $_POST['action'] ?? '';

// ── LOGIN ─────────────────────────────────────────────────
if ($action === 'login') {
    $email    = esc($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('error', 'Email dan password wajib diisi.');
        redirect('login.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Format email tidak valid.');
        redirect('login.php');
    }

    $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !verifyPassword($password, $user['password'])) {
        setFlash('error', 'Email atau password salah.');
        redirect('login.php');
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_nama'] = $user['nama'];
    $_SESSION['user_email']= $user['email'];
    $_SESSION['user_role'] = $user['role'];

    setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

    if ($user['role'] === 'admin') {
        redirect('dashboard_admin.php');
    } else {
        redirect('event.php');
    }
}

// ── REGISTER ──────────────────────────────────────────────
if ($action === 'register') {
    $nama     = esc($_POST['nama'] ?? '');
    $email    = esc($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role     = 'user'; // paksa user, tidak pakai pilihan admin

    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        setFlash('error', 'Semua field wajib diisi.');
        redirect('register.php');
    }

    if ($password !== $password_confirm) {
        setFlash('error', 'Password dan verifikasi password tidak cocok.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        setFlash('error', 'Password minimal 6 karakter.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Format email tidak valid.');
        redirect('register.php');
    }

    // Cek email sudah dipakai
    $cek = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $cek->bind_param('s', $email);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $cek->close();
        setFlash('error', 'Email sudah terdaftar. Silakan login.');
        redirect('register.php');
    }
    $cek->close();

    $hashedPass = hashPassword($password);
    $ins = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $ins->bind_param('ssss', $nama, $email, $hashedPass, $role);

    if ($ins->execute()) {
        $ins->close();
        setFlash('success', 'Akun berhasil dibuat! Silakan login.');
        redirect('login.php');
    } else {
        $ins->close();
        setFlash('error', 'Gagal membuat akun. Coba lagi.');
        redirect('register.php');
    }
}

// Jika akses langsung tanpa POST
redirect('event.php');