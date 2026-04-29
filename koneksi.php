<?php
/**
 * koneksi.php — Konfigurasi & koneksi database
 * EvenTech Platform
 */

// ── Konfigurasi Database ──────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Sesuaikan password MySQL XAMPP Anda
define('DB_NAME', 'eventech_db');
define('DB_CHARSET', 'utf8mb4');

// ── Koneksi MySQLi ────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'error' => true,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]));
}

// Set charset untuk mendukung karakter Unicode
$conn->set_charset(DB_CHARSET);

/**
 * Helper: Escape string aman untuk query
 */
function esc(string $val): string {
    global $conn;
    return $conn->real_escape_string(trim($val));
}

/**
 * Helper: Hash password dengan MD5 + SHA-256
 * Pertama di-MD5, lalu hasilnya di-SHA256
 */
function hashPassword(string $password): string {
    return hash('sha256', md5($password));
}

/**
 * Helper: Verifikasi password
 */
function verifyPassword(string $input, string $stored): bool {
    return hashPassword($input) === $stored;
}

/**
 * Helper: Redirect
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Helper: Flash message via session
 */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Helper: Format rupiah
 */
function rupiah(int $amount): string {
    if ($amount === 0) return 'GRATIS';
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Helper: Format tanggal Indonesia
 */
function tglIndo(string $date): string {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $d = explode('-', $date);
    return intval($d[2]) . ' ' . $bulan[intval($d[1])] . ' ' . $d[0];
}
