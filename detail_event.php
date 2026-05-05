<?php
/**
 * detail_event.php — Halaman Detail Event (PUBLIK)
 * EvenTech Platform
 */

session_start();
require_once 'koneksi.php';

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? 'guest';

// ── Handle Daftar Event ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'daftar') {
    if (!$userId) {
        setFlash('error', 'Silakan login terlebih dahulu untuk mendaftar event.');
        redirect('login.php');
    }
    
    if ($userRole === 'admin') {
        setFlash('error', 'Admin tidak dapat mendaftar sebagai peserta event.');
        redirect('event.php');
    }
    
    $event_id = (int)($_POST['event_id'] ?? 0);
    
    $ev = $conn->prepare("SELECT id, kuota FROM events WHERE id=? AND status='published' LIMIT 1");
    $ev->bind_param('i', $event_id);
    $ev->execute();
    $evRow = $ev->get_result()->fetch_assoc();
    $ev->close();
    if (!$evRow) {
        setFlash('error','Event tidak ditemukan.');
        redirect('event.php');
    }
    
    $cek = $conn->prepare("SELECT id FROM registrasi WHERE user_id=? AND event_id=? LIMIT 1");
    $cek->bind_param('ii', $userId, $event_id);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $cek->close();
        setFlash('error','Kamu sudah terdaftar di event ini!');
        redirect('event.php');
    }
    $cek->close();
    
    $peserta = $conn->query("SELECT COUNT(*) FROM registrasi WHERE event_id=$event_id")->fetch_row()[0];
    if ($peserta >= $evRow['kuota']) {
        setFlash('error','Kuota event sudah penuh.');
        redirect('event.php');
    }
    
    $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->bind_param('i', $userId);
    $checkUser->execute();
    $checkUser->store_result();
    if ($checkUser->num_rows == 0) {
        setFlash('error', 'Akun tidak ditemukan. Silakan login ulang.');
        session_destroy();
        redirect('login.php');
    }
    $checkUser->close();
    
    $ins = $conn->prepare("INSERT INTO registrasi (user_id, event_id) VALUES (?, ?)");
    $ins->bind_param('ii', $userId, $event_id);
    if ($ins->execute()) {
        setFlash('success', 'Berhasil mendaftar! Selamat bergabung! 🎉');
    } else {
        setFlash('error', 'Gagal mendaftar. Coba lagi.');
    }
    $ins->close();
    redirect('event.php');
}

// ── Ambil data event ─────────────────────────────────────
$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('event.php'); }

if ($userId) {
    $stmt = $conn->prepare("
        SELECT e.*,
            COUNT(DISTINCT r.id) AS peserta,
            MAX(CASE WHEN r.user_id = ? THEN 1 ELSE 0 END) AS sudah_daftar
        FROM events e
        LEFT JOIN registrasi r ON r.event_id = e.id
        WHERE e.id = ? AND e.status = 'published'
        GROUP BY e.id
    ");
    $stmt->bind_param('ii', $userId, $id);
} else {
    $stmt = $conn->prepare("
        SELECT e.*,
            COUNT(DISTINCT r.id) AS peserta,
            0 AS sudah_daftar
        FROM events e
        LEFT JOIN registrasi r ON r.event_id = e.id
        WHERE e.id = ? AND e.status = 'published'
        GROUP BY e.id
    ");
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$ev = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ev) { setFlash('error','Event tidak ditemukan.'); redirect('event.php'); }

// Ambil daftar peserta (untuk admin)
$pesertaList = null;
if ($userRole === 'admin') {
    $pesertaList = $conn->query("
        SELECT u.nama, u.email, r.registered_at
        FROM registrasi r
        JOIN users u ON u.id = r.user_id
        WHERE r.event_id = $id
        ORDER BY r.registered_at ASC
    ");
}

$pct    = $ev['kuota'] > 0 ? round($ev['peserta'] / $ev['kuota'] * 100) : 0;
$isFull = $ev['peserta'] >= $ev['kuota'];
$isDaft = ($userId && $ev['sudah_daftar'] == 1);

// Fungsi ambil URL gambar
function eventImageUrl($gambar, $kategori) {
    if (!empty($gambar)) {
        if (strpos($gambar, 'http') === 0) {
            return $gambar;
        }
        return 'uploads/events/' . $gambar;
    }
    $colors = [
        'seminar'   => '1E5FA0',
        'workshop'  => 'C8813A',
        'lomba'     => '7C3AED',
        'webinar'   => '2E7D55',
        'conference'=> 'B45309',
        'bootcamp'  => 'DC2626'
    ];
    $hex = $colors[$kategori] ?? '6B7280';
    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='500' viewBox='0 0 400 500'%3E%3Crect width='400' height='500' fill='%23{$hex}'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='rgba(255,255,255,0.15)' font-size='28' font-family='sans-serif'%3E".urlencode(strtoupper($kategori))."%3C/text%3E%3C/svg%3E";
}

$flash = getFlash();
$imgUrl = eventImageUrl($ev['gambar'], $ev['kategori']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($ev['judul']) ?> — EvenTech</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:       #0D0F14;
      --bg2:      #13161E;
      --surface:  #1A1D27;
      --surface2: #21253A;
      --border:   rgba(255,255,255,0.07);
      --gold:     #C8963E;
      --gold-lt:  #E8B86D;
      --gold-dk:  #8A641F;
      --amber:    #F0A500;
      --teal:     #2DD4BF;
      --purple:   #A78BFA;
      --text:     #F0EDE8;
      --text-sub: #9B98A0;
      --red:      #FF5572;
      --green:    #22C55E;
      --radius:   16px;
      --radius-sm:10px;
      --sidebar-w:240px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      min-height: 100vh;
    }

    /* ── SIDEBAR ────────────────────────────── */
    .sidebar {
      width: var(--sidebar-w);
      min-height: 100vh;
      background: var(--bg2);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      padding: 28px 16px;
      position: fixed;
      top: 0; left: 0;
      z-index: 100;
    }
    .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 0 8px; margin-bottom: 36px; }
    .sidebar-logo .ico { width: 36px; height: 36px; background: linear-gradient(135deg,var(--gold-dk),var(--gold)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .sidebar-logo .name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 17px; }
    .sidebar-logo .name span { color: var(--gold); }
    .sidebar-label { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-sub); padding: 0 12px; margin-bottom: 8px; margin-top: 20px; }
    .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--radius-sm); color: var(--text-sub); font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; margin-bottom: 2px; }
    .nav-item:hover { background: var(--surface); color: var(--text); }
    .nav-item.active { background: rgba(200,150,62,0.15); color: var(--gold-lt); }
    .nav-ico { font-size: 16px; width: 20px; text-align: center; }
    .sidebar-bottom { margin-top: auto; }
    .user-chip { display: flex; align-items: center; gap: 10px; padding: 12px; background: var(--surface); border-radius: var(--radius-sm); border: 1px solid var(--border); }
    .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg,var(--gold-dk),var(--gold)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Syne',sans-serif; font-weight: 800; font-size: 14px; color: #fff; flex-shrink: 0; }
    .user-info .uname { font-size: 13px; font-weight: 600; }
    .user-info .urole { font-size: 11px; color: var(--gold); }
    .btn-logout { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: var(--radius-sm); color: var(--red); font-size: 13px; text-decoration: none; transition: background 0.2s; margin-top: 8px; }
    .btn-logout:hover { background: rgba(255,85,114,0.1); }

    /* ── MAIN CONTENT ───────────────────────── */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      padding: 36px;
      max-width: 1100px;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--text-sub);
      font-size: 13px;
      text-decoration: none;
      margin-bottom: 24px;
      transition: color 0.2s;
    }
    .back-link:hover { color: var(--text); }

    /* Flash */
    .flash {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: var(--radius-sm);
      font-size: 13px; margin-bottom: 20px;
    }
    .flash.error { background: rgba(255,85,114,0.12); border: 1px solid rgba(255,85,114,0.3); color: #FF8EA3; }
    .flash.success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ADE80; }

    /* ── LAYOUT DETAIL (2 KOLOM) ────────────── */
    .detail-container {
      display: flex;
      gap: 32px;
      align-items: flex-start;
    }

    /* Kolom kiri: gambar poster */
    .detail-image-col {
      flex: 0 0 400px;
      position: sticky;
      top: 24px;
    }
    .detail-image-wrap {
    position: relative;
    width: 100%;
    padding-top: 125%; /* 4:5 portrait */
    border-radius: var(--radius);
    overflow: hidden;
    background: #1e1e2f;
    border: 1px solid var(--border);
}
    .detail-image-wrap img {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .detail-image-badge {
      position: absolute;
      top: 14px;
      left: 14px;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 100px;
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 600;
      color: #fff;
      z-index: 2;
    }
    .detail-image-price {
      position: absolute;
      top: 14px;
      right: 14px;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 100px;
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 700;
      z-index: 2;
    }
    .detail-image-price.gratis { color: #4ADE80; }
    .detail-image-price.bayar  { color: #FBBF24; }

    /* Kolom kanan: info & form */
    .detail-info-col {
      flex: 1;
      min-width: 0;
    }
    .detail-meta-chips {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }
    .meta-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      border-radius: 100px;
      background: var(--surface);
      border: 1px solid var(--border);
      font-size: 12px;
      color: var(--text-sub);
    }
    .detail-title {
      font-family: 'Syne', sans-serif;
      font-size: 26px;
      font-weight: 800;
      letter-spacing: -0.5px;
      margin-bottom: 16px;
      line-height: 1.2;
    }
    .detail-desc {
      font-size: 14px;
      line-height: 1.7;
      color: rgba(240,237,232,0.75);
      margin-bottom: 24px;
    }

    /* Card pendaftaran */
    .register-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
    }
    .price-big {
      font-family: 'Syne', sans-serif;
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 4px;
    }
    .price-big.gratis { color: var(--green); }
    .price-big.bayar  { color: var(--gold-lt); }
    .price-sub {
      font-size: 13px;
      color: var(--text-sub);
      margin-bottom: 18px;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 9px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }
    .info-row:last-of-type { border-bottom: none; }
    .info-row .lbl { color: var(--text-sub); }
    .info-row .val { font-weight: 600; }

    .kuota-bar-wrap { margin: 16px 0; }
    .kuota-bar-label { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-sub); margin-bottom: 5px; }
    .kuota-bar { height: 5px; background: var(--surface2); border-radius: 3px; overflow: hidden; }
    .kuota-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--gold-dk), var(--gold)); transition: width 0.4s; }
    .kuota-fill.full { background: linear-gradient(90deg, var(--red), #FF8EA3); }

    .daftar-btn {
      display: block;
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      font-family: 'Syne', sans-serif;
      font-size: 15px;
      font-weight: 700;
      text-align: center;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.25s;
      margin-top: 4px;
    }
    .daftar-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .daftar-btn.enrolled {
      background: rgba(34,197,94,0.12);
      color: var(--green);
      cursor: default;
      border: 1px solid rgba(34,197,94,0.25);
    }
    .daftar-btn.full {
      background: rgba(255,85,114,0.1);
      color: var(--red);
      cursor: default;
      border: 1px solid rgba(255,85,114,0.2);
    }
    .daftar-btn.admin-btn { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
    .daftar-btn.admin-btn:hover { border-color: var(--gold); }

    /* Daftar peserta */
    .peserta-box {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .peserta-box .head {
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      font-family: 'Syne',sans-serif;
      font-weight: 700;
      font-size: 14px;
    }
    .peserta-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 20px;
      border-bottom: 1px solid var(--border);
      font-size: 12px;
    }
    .peserta-item:last-child { border-bottom: none; }
    .p-avatar {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 12px; color: #fff; flex-shrink: 0;
    }
    .p-email { font-size: 11px; color: var(--text-sub); }

    /* Responsive */
    @media (max-width: 960px) {
      .detail-container { flex-direction: column; }
      .detail-image-col { flex: unset; width: 100%; position: static; }
      .detail-image-col .detail-image-wrap { max-width: 400px; margin: 0 auto; }
    }
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .main { margin-left: 0; padding: 20px 16px; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="ico">⚡</div>
    <div class="name">Even<span>Tech</span></div>
  </div>
  <span class="sidebar-label">Navigasi</span>
  <a class="nav-item" href="event.php"><span class="nav-ico">🗓</span> Semua Event</a>
  <?php if ($userId && $userRole === 'admin'): ?>
    <span class="sidebar-label">Admin</span>
    <a class="nav-item" href="dashboard_admin.php"><span class="nav-ico">⚙️</span> Dashboard Admin</a>
  <?php endif; ?>
  <div class="sidebar-bottom">
    <?php if ($userId): ?>
      <div class="user-chip">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_nama'] ?? 'U', 0, 1)) ?></div>
        <div class="user-info">
          <div class="uname"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></div>
          <div class="urole"><?= ucfirst($_SESSION['user_role'] ?? 'user') ?></div>
        </div>
      </div>
      <a class="btn-logout" href="logout.php">🚪 Keluar</a>
    <?php else: ?>
      <a class="nav-item" href="login.php" style="margin-bottom:8px;"><span class="nav-ico">🔑</span> Login</a>
      <a class="nav-item" href="register.php"><span class="nav-ico">📝</span> Daftar</a>
    <?php endif; ?>
  </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">
  <a href="event.php" class="back-link">← Kembali ke Daftar Event</a>

  <?php if ($flash): ?>
  <div class="flash <?= $flash['type'] ?>">
    <?= $flash['type'] === 'error' ? '✕' : '✓' ?> <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif; ?>

  <div class="detail-container">
    <!-- KOLOM KIRI: Gambar Poster -->
    <div class="detail-image-col">
      <div class="detail-image-wrap">
        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($ev['judul']) ?>" loading="lazy">
        <span class="detail-image-badge"><?= ucfirst($ev['kategori']) ?></span>
        <span class="detail-image-price <?= $ev['harga'] == 0 ? 'gratis' : 'bayar' ?>">
          <?= rupiah((int)$ev['harga']) ?>
        </span>
      </div>
    </div>

    <!-- KOLOM KANAN: Info + Form -->
    <div class="detail-info-col">
      <!-- Meta chips -->
      <div class="detail-meta-chips">
        <span class="meta-chip">📅 <?= tglIndo($ev['tanggal']) ?></span>
        <span class="meta-chip">⏰ <?= substr($ev['waktu'],0,5) ?> WIB</span>
        <span class="meta-chip">📍 <?= htmlspecialchars($ev['lokasi']) ?></span>
      </div>

      <!-- Judul -->
      <h1 class="detail-title"><?= htmlspecialchars($ev['judul']) ?></h1>

      <!-- Deskripsi -->
      <div class="detail-desc"><?= nl2br(htmlspecialchars($ev['deskripsi'])) ?></div>

      <!-- Card Pendaftaran -->
      <div class="register-card">
        <div class="price-big <?= $ev['harga'] == 0 ? 'gratis' : 'bayar' ?>">
          <?= rupiah((int)$ev['harga']) ?>
        </div>
        <div class="price-sub"><?= $ev['harga'] == 0 ? 'Event ini gratis untuk semua' : 'Biaya pendaftaran' ?></div>

        <div class="info-row">
          <span class="lbl">Tanggal</span>
          <span class="val"><?= tglIndo($ev['tanggal']) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Waktu</span>
          <span class="val"><?= substr($ev['waktu'],0,5) ?> WIB</span>
        </div>
        <div class="info-row">
          <span class="lbl">Lokasi</span>
          <span class="val"><?= htmlspecialchars($ev['lokasi']) ?></span>
        </div>
        <div class="info-row">
          <span class="lbl">Kategori</span>
          <span class="val"><?= ucfirst($ev['kategori']) ?></span>
        </div>

        <!-- Kuota bar -->
        <div class="kuota-bar-wrap">
          <div class="kuota-bar-label">
            <span>Kuota terisi</span>
            <span><?= $ev['peserta'] ?>/<?= $ev['kuota'] ?> peserta (<?= $pct ?>%)</span>
          </div>
          <div class="kuota-bar">
            <div class="kuota-fill <?= $isFull ? 'full' : '' ?>" style="width:<?= min($pct,100) ?>%"></div>
          </div>
        </div>

        <!-- Tombol aksi -->
        <?php if ($userRole === 'admin'): ?>
          <a href="dashboard_admin.php" class="daftar-btn admin-btn">⚙️ Kelola di Dashboard Admin</a>
        <?php elseif (!$userId): ?>
          <a href="login.php" class="daftar-btn">Login untuk Mendaftar</a>
        <?php elseif ($isDaft): ?>
          <span class="daftar-btn enrolled">✓ Kamu Sudah Terdaftar</span>
        <?php elseif ($isFull): ?>
          <span class="daftar-btn full">Kuota Penuh</span>
        <?php else: ?>
          <form method="POST">
            <input type="hidden" name="act" value="daftar">
            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
            <button type="submit" class="daftar-btn">Daftar Sekarang →</button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Daftar peserta (khusus admin) -->
      <?php if ($pesertaList && $pesertaList->num_rows > 0): ?>
      <div class="peserta-box">
        <div class="head">👥 Peserta Terdaftar (<?= $ev['peserta'] ?>)</div>
        <?php while ($p = $pesertaList->fetch_assoc()): ?>
        <div class="peserta-item">
          <div class="p-avatar"><?= strtoupper(substr($p['nama'],0,1)) ?></div>
          <div>
            <div><?= htmlspecialchars($p['nama']) ?></div>
            <div class="p-email"><?= htmlspecialchars($p['email']) ?></div>
          </div>
          <div style="margin-left:auto;font-size:11px;color:var(--text-sub)"><?= date('d M Y', strtotime($p['registered_at'])) ?></div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php elseif ($userRole === 'admin'): ?>
      <div class="peserta-box">
        <div class="head">👥 Peserta Terdaftar</div>
        <div style="padding:20px;text-align:center;color:var(--text-sub);font-size:13px;">Belum ada peserta.</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
  setTimeout(() => {
    const f = document.querySelector('.flash');
    if (f) { f.style.opacity='0'; f.style.transition='opacity 0.5s'; setTimeout(()=>f.remove(),500); }
  }, 3500);
</script>
</body>
</html>