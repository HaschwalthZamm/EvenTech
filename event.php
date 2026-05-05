<?php
/**
 * event.php — Halaman Daftar Event (PUBLIK, tidak perlu login)
 * EvenTech Platform
 */

session_start();
require_once 'koneksi.php';

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? 'guest';
$userName = $_SESSION['user_nama'] ?? 'Tamu';

$flash = getFlash();

// ── Filter & Search ───────────────────────────────────────
$search   = esc($_GET['search'] ?? '');
$kategori = esc($_GET['kategori'] ?? '');
$filter   = $_GET['filter'] ?? ''; // 'my' untuk event saya

$where = "WHERE e.status = 'published'";

if ($search)   $where .= " AND e.judul LIKE '%$search%'";
if ($kategori) $where .= " AND e.kategori = '$kategori'";

if ($filter === 'my' && $userId) {
    $where .= " AND e.id IN (SELECT event_id FROM registrasi WHERE user_id = $userId)";
}

// Query ambil event + gambar
if ($userId) {
    $events = $conn->query("
        SELECT e.*,
            COUNT(DISTINCT r.id) AS peserta,
            MAX(CASE WHEN r.user_id = $userId THEN 1 ELSE 0 END) AS sudah_daftar
        FROM events e
        LEFT JOIN registrasi r ON r.event_id = e.id
        $where
        GROUP BY e.id
        ORDER BY e.tanggal ASC
    ");
} else {
    $events = $conn->query("
        SELECT e.*,
            COUNT(DISTINCT r.id) AS peserta,
            0 AS sudah_daftar
        FROM events e
        LEFT JOIN registrasi r ON r.event_id = e.id
        $where
        GROUP BY e.id
        ORDER BY e.tanggal ASC
    ");
}

$kategoriList = ['seminar','workshop','lomba','webinar','conference','bootcamp'];

// Fungsi untuk mendapatkan URL gambar (fallback jika kosong)
function eventImageUrl($gambar, $kategori) {
    if (!empty($gambar)) {
        // Jika sudah berupa URL lengkap (diawali http), pakai langsung
        if (strpos($gambar, 'http') === 0) {
            return $gambar;
        }
        // Jika hanya nama file, ambil dari folder lokal
        return 'uploads/events/' . $gambar;
    }
    // fallback placeholder...
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event — EvenTech</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <style>
        /* ===== CSS Variables (Dark Mode - Default) ===== */
    :root {
      --bg:       #0D0F14;
      --bg2:      #13161E;
      --surface:  #1A1D27;
      --surface2: #21253A;
      --border:   rgba(255,255,255,0.08);
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
      --shadow:   0 20px 40px rgba(0,0,0,0.5);
      --shadow-card-hover: 0 20px 40px rgba(0,0,0,0.6);
      --hero-bg: linear-gradient(145deg, #1C1200, #2D1B00, #3D2500);
      --hero-text: rgba(240,237,232,0.65);
      --hero-border: rgba(200,150,62,0.15);
      --badge-bg: rgba(0,0,0,0.55);
      --badge-text: #fff;
      --price-gratis: #4ADE80;
      --price-bayar: #FBBF24;
      --card-thumb-bg: #1e1e2f;
      --toggle-hover: var(--surface2);
    }

    /* ===== Light Mode Variables ===== */
    body.light-mode {
      --bg:       #F8F6F0;
      --bg2:      #FFFFFF;
      --surface:  #FFFFFF;
      --surface2: #F4EFE6;
      --border:   #E8E0D5;
      --gold:     #C8963E;
      --gold-lt:  #D4A84C;
      --gold-dk:  #A67C2E;
      --amber:    #E5A100;
      --teal:     #0F766E;
      --purple:   #7C3AED;
      --text:     #1A1A1A;
      --text-sub: #6B6B6B;
      --red:      #DC2626;
      --green:    #16A34A;
      --shadow:   0 4px 12px rgba(0,0,0,0.04);
      --shadow-card-hover: 0 20px 40px rgba(0,0,0,0.08);
      --hero-bg: linear-gradient(145deg, #FDFBF7, #F8F4EC, #F4EDDE);
      --hero-text: #4A4A4A;
      --hero-border: #E8E0D5;
      --badge-bg: rgba(255,255,255,0.9);
      --badge-text: #1A1A1A;
      --price-gratis: #16A34A;
      --price-bayar: #C8963E;
      --card-thumb-bg: #EDE7DC;
      --toggle-hover: #F4EFE6;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      min-height: 100vh;
      transition: background 0.3s, color 0.2s;
    }

    /* ===== SIDEBAR ===== */
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
      transition: background 0.3s, border-color 0.2s;
    }
    .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 0 8px; margin-bottom: 36px; }
    .sidebar-logo .ico { width: 36px; height: 36px; background: linear-gradient(135deg,var(--gold-dk),var(--gold)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .sidebar-logo .name { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 17px; color: var(--text); }
    .sidebar-logo .name span { color: var(--gold); }
    .sidebar-label { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-sub); padding: 0 12px; margin-bottom: 8px; margin-top: 20px; }
    .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--radius-sm); color: var(--text-sub); font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; margin-bottom: 2px; }
    .nav-item:hover { background: var(--surface2); color: var(--text); }
    .nav-item.active { background: rgba(200,150,62,0.15); color: var(--gold-lt); }
    .nav-ico { font-size: 16px; width: 20px; text-align: center; }
    .sidebar-bottom { margin-top: auto; }
    .user-chip { display: flex; align-items: center; gap: 10px; padding: 12px; background: var(--surface2); border-radius: var(--radius-sm); border: 1px solid var(--border); transition: background 0.3s; }
    .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg,var(--gold-dk),var(--gold)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Syne',sans-serif; font-weight: 800; font-size: 14px; color: #fff; flex-shrink: 0; }
    .user-info .uname { font-size: 13px; font-weight: 600; }
    .user-info .urole { font-size: 11px; color: var(--gold); }
    .btn-logout { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: var(--radius-sm); color: var(--red); font-size: 13px; text-decoration: none; transition: background 0.2s; margin-top: 8px; }
    .btn-logout:hover { background: rgba(255,85,114,0.1); }

    /* Toggle Button */
    .theme-toggle {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: var(--radius-sm);
      color: var(--text-sub);
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      margin-bottom: 12px;
      background: none;
      border: none;
      width: 100%;
      font-family: 'DM Sans', sans-serif;
    }
    .theme-toggle:hover { background: var(--toggle-hover); color: var(--text); }
    .theme-toggle .toggle-icon {
      width: 20px; height: 20px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .theme-toggle .toggle-icon svg {
      width: 18px; height: 18px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }
    .icon-sun { display: none; }
    .icon-moon { display: block; }
    body.light-mode .icon-sun { display: block; }
    body.light-mode .icon-moon { display: none; }

    /* Main */
    .main { margin-left: var(--sidebar-w); flex: 1; padding: 36px; }
    .hero-banner {
      position: relative;
      background: var(--hero-bg);
      border-radius: var(--radius);
      padding: 40px 48px;
      margin-bottom: 32px;
      overflow: hidden;
      border: 1px solid var(--hero-border);
      transition: background 0.3s, border-color 0.2s;
    }
    .hero-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse 60% 80% at 90% 50%, rgba(45,212,191,0.08) 0%, transparent 70%),
                  radial-gradient(ellipse 50% 60% at 10% 30%, rgba(200,150,62,0.2) 0%, transparent 60%);
      opacity: 0.4;
    }
    body.light-mode .hero-banner::before { opacity: 1; }
    .hero-banner .inner { position: relative; z-index: 1; }
    .hero-tag { display: inline-flex; align-items: center; gap: 6px; background: rgba(200,150,62,0.15); border: 1px solid rgba(200,150,62,0.3); color: var(--gold-lt); font-size: 11px; font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; padding: 5px 12px; border-radius: 100px; margin-bottom: 16px; }
    .hero-banner h1 { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 10px; color: var(--text); }
    .hero-banner h1 em { font-style: normal; color: var(--gold); }
    .hero-banner p { color: var(--hero-text); font-size: 14px; max-width: 500px; transition: color 0.2s; }

    /* Filter */
    .filter-row { display: flex; gap: 12px; align-items: center; margin-bottom: 28px; flex-wrap: wrap; }
    .search-wrap { position: relative; flex: 1; min-width: 220px; }
    .search-wrap input { width: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px; padding: 11px 14px 11px 40px; outline: none; transition: all 0.2s; }
    .search-wrap input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(200,150,62,0.12); }
    .search-wrap .ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-sub); }
    .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .chip { padding: 8px 16px; border-radius: 100px; font-size: 12px; font-weight: 600; border: 1px solid var(--border); background: var(--surface); color: var(--text-sub); text-decoration: none; transition: all 0.2s; }
    .chip:hover { color: var(--text); border-color: var(--gold-lt); }
    .chip.active { background: rgba(200,150,62,0.15); border-color: var(--gold); color: var(--gold); }

    .section-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text); }

    /* Grid */
    .events-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .event-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: transform 0.25s, box-shadow 0.25s, background 0.3s, border-color 0.2s; text-decoration: none; color: inherit; display: flex; flex-direction: column; }
    .event-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-card-hover); border-color: rgba(200,150,62,0.3); }
    .card-img-wrap { position: relative; width: 100%; padding-top: 125%; background: var(--card-thumb-bg); overflow: hidden; transition: background 0.3s; }
    .card-img-wrap img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
    .card-badge { position: absolute; top: 12px; left: 12px; background: var(--badge-bg); backdrop-filter: blur(6px); border: 1px solid rgba(200,150,62,0.3); border-radius: 100px; padding: 4px 10px; font-size: 11px; font-weight: 600; color: var(--badge-text); z-index: 2; }
    .card-price-tag { position: absolute; top: 12px; right: 12px; background: var(--badge-bg); backdrop-filter: blur(6px); border: 1px solid rgba(200,150,62,0.3); border-radius: 100px; padding: 4px 10px; font-size: 11px; font-weight: 700; z-index: 2; }
    .card-price-tag.gratis { color: var(--price-gratis); border-color: rgba(22,163,74,0.3); }
    .card-price-tag.bayar  { color: var(--price-bayar); }
    .card-body { padding: 16px; flex: 1; display: flex; flex-direction: column; background: var(--surface); transition: background 0.3s; }
    .card-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; line-height: 1.3; margin-bottom: 6px; color: var(--text); }
    .card-meta { display: flex; gap: 10px; font-size: 11px; color: var(--text-sub); margin-bottom: 10px; flex-wrap: wrap; }
    .card-meta span { display: flex; align-items: center; gap: 4px; }
    .kuota-wrap { margin-bottom: 12px; }
    .kuota-label { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-sub); margin-bottom: 4px; }
    .kuota-bar { height: 4px; background: var(--surface2); border-radius: 2px; overflow: hidden; }
    .kuota-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--gold-dk), var(--gold)); }
    .kuota-fill.full { background: linear-gradient(90deg, var(--red), #FF8EA3); }
    .card-actions { display: flex; gap: 8px; margin-top: auto; }
    .card-btn { flex: 1; padding: 10px; border-radius: var(--radius-sm); font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; text-align: center; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; display: inline-block; }
    .card-btn.primary { background: linear-gradient(135deg, var(--gold-dk), var(--gold)); color: #fff; border: none; }
    .card-btn.primary:hover { opacity: 0.9; transform: translateY(-1px); }
    .card-btn.enrolled { background: rgba(34,197,94,0.12); color: var(--green); border: 1px solid rgba(34,197,94,0.25); cursor: default; }
    .card-btn.full { background: rgba(255,85,114,0.1); color: var(--red); border: 1px solid rgba(255,85,114,0.2); cursor: default; }
    .card-btn.detail { background: var(--surface2); color: var(--text-sub); border: 1px solid var(--border); }
    .card-btn.detail:hover { color: var(--text); background: var(--border); }

    .flash { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 20px; }
    .flash.error   { background: rgba(255,85,114,0.12); border: 1px solid rgba(255,85,114,0.3); color: #FF8EA3; }
    .flash.success { background: rgba(34,197,94,0.12);  border: 1px solid rgba(34,197,94,0.3);  color: #4ADE80; }
    .empty-state { text-align: center; padding: 80px 20px; color: var(--text-sub); }
    .empty-state .ico { font-size: 56px; margin-bottom: 16px; }
    .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 20px; color: var(--text); margin-bottom: 8px; }

    @media (max-width: 900px) { .sidebar { transform: translateX(-100%); } .main { margin-left: 0; padding: 20px 16px; } .events-grid { grid-template-columns: 1fr; } }
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
  <a class="nav-item <?= $filter !== 'my' ? 'active' : '' ?>" href="event.php"><span class="nav-ico">🗓</span> Semua Event</a>
  <?php if ($userId): ?>
    <a class="nav-item <?= $filter === 'my' ? 'active' : '' ?>" href="event.php?filter=my"><span class="nav-ico">🎟</span> Event Saya</a>
  <?php endif; ?>
  <?php if ($userRole === 'admin'): ?>
    <span class="sidebar-label">Admin</span>
    <a class="nav-item" href="dashboard_admin.php"><span class="nav-ico">⚙️</span> Dashboard Admin</a>
  <?php endif; ?>

  <!-- TOMBOL TOGGLE TEMA -->
  <button class="theme-toggle" onclick="toggleTheme()" title="Ganti tema">
    <span class="toggle-icon">
      <!-- Ikon Bulan (mode gelap) -->
      <svg class="icon-moon" viewBox="0 0 24 24">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
      <!-- Ikon Matahari (mode terang) -->
      <svg class="icon-sun" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/>
        <line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/>
        <line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
      </svg>
    </span>
    <span class="toggle-label">Tema</span>
  </button>

  <div class="sidebar-bottom">
    <?php if ($userId): ?>
      <div class="user-chip">
        <div class="user-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
        <div class="user-info">
          <div class="uname"><?= htmlspecialchars($userName) ?></div>
          <div class="urole"><?= ucfirst($userRole) ?></div>
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
  <!-- Tombol tema -->

  <?php if ($flash): ?>
  <div class="flash <?= $flash['type'] ?>">
    <?= $flash['type'] === 'error' ? '✕' : '✓' ?> <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif; ?>

  <!-- Hero banner (tetap) -->
  <div class="hero-banner">
    <div class="inner">
      <div class="hero-tag">✨ <?= $filter === 'my' ? 'Event Favoritmu' : 'Explore Events' ?></div>
      <h1><?= $filter === 'my' ? 'Event yang <em>Kamu Ikuti</em>' : 'Temukan Event IT<br><em>Terbaik</em> untukmu' ?></h1>
      <p><?= $filter === 'my' ? 'Berikut daftar event yang sudah kamu daftarkan.' : 'Seminar, workshop, hackathon, hingga webinar — semuanya ada di sini.' ?></p>
    </div>
  </div>

  <!-- Filter (kecuali jika filter 'my') -->
  <?php if ($filter !== 'my'): ?>
  <form method="GET" class="filter-row">
    <div class="search-wrap">
      <span class="ico">🔍</span>
      <input type="text" name="search" placeholder="Cari event..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="filter-chips">
      <a class="chip <?= !$kategori ? 'active' : '' ?>" href="event.php<?= $search ? '?search='.$search : '' ?>">Semua</a>
      <?php foreach ($kategoriList as $k): ?>
      <a class="chip <?= $kategori === $k ? 'active' : '' ?>" href="?kategori=<?= $k ?><?= $search ? '&search='.$search : '' ?>"><?= ucfirst($k) ?></a>
      <?php endforeach; ?>
    </div>
    <button type="submit" style="display:none"></button>
  </form>
  <?php endif; ?>

  <?php
  $eventCount = $events->num_rows;
  ?>
  <div class="section-title">
    <?php if ($filter === 'my'): ?>
      <?= $eventCount ?> Event yang Kamu Ikuti
    <?php else: ?>
      <?= $eventCount ?> Event Ditemukan <?= $kategori ? '— <span style="color:var(--gold)">'.ucfirst($kategori).'</span>' : '' ?>
    <?php endif; ?>
  </div>

  <?php if ($eventCount === 0 && $filter === 'my'): ?>
    <div class="empty-state">
      <div class="ico">🎟️</div>
      <h3>Belum ada event yang diikuti</h3>
      <p>Yuk, cari event menarik dan daftar sekarang!</p>
      <a href="event.php" class="card-btn primary" style="width:auto; display:inline-block; margin-top:16px;">Lihat Semua Event →</a>
    </div>
  <?php elseif ($eventCount === 0): ?>
    <div class="empty-state">
      <div class="ico">🔍</div>
      <h3>Tidak ada event ditemukan</h3>
      <p>Coba ubah filter atau kata kunci pencarianmu.</p>
    </div>
  <?php else: ?>
    <!-- ===== GRID KARTU INSTAGRAM-STYLE ===== -->
    <div class="events-grid">
      <?php while ($ev = $events->fetch_assoc()):
        $pct = $ev['kuota'] > 0 ? round($ev['peserta'] / $ev['kuota'] * 100) : 0;
        $isFull = $ev['peserta'] >= $ev['kuota'];
        $isDaftar = ($userId && $ev['sudah_daftar'] == 1);
        $imgUrl = eventImageUrl($ev['gambar'], $ev['kategori']);
      ?>
      <div class="event-card">
        <!-- GAMBAR POSTER -->
        <div class="card-img-wrap">
          <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($ev['judul']) ?>" loading="lazy">
          <!-- Overlay hanya dipakai jika ingin menampilkan judul di atas gambar, tapi kita sudah di body -->
          <!-- Badge kategori & harga tetap di pojok -->
          <span class="card-badge"><?= ucfirst($ev['kategori']) ?></span>
          <span class="card-price-tag <?= $ev['harga'] == 0 ? 'gratis' : 'bayar' ?>"><?= rupiah((int)$ev['harga']) ?></span>
        </div>

        <!-- BODY KARTU -->
        <div class="card-body">
          <div class="card-title"><?= htmlspecialchars($ev['judul']) ?></div>
          <div class="card-meta">
            <span>📅 <?= tglIndo($ev['tanggal']) ?></span>
            <span>📍 <?= htmlspecialchars(mb_strimwidth($ev['lokasi'], 0, 25, '...')) ?></span>
            <span>👥 <?= $ev['peserta'] ?>/<?= $ev['kuota'] ?></span>
          </div>

          <!-- Kuota bar -->
          <div class="kuota-wrap">
            <div class="kuota-label">
              <span>Kuota terisi</span>
              <span><?= $pct ?>%</span>
            </div>
            <div class="kuota-bar">
              <div class="kuota-fill <?= $isFull ? 'full' : '' ?>" style="width:<?= min($pct,100) ?>%"></div>
            </div>
          </div>

          <!-- Tombol aksi -->
          <div class="card-actions">
            <a href="detail_event.php?id=<?= $ev['id'] ?>" class="card-btn detail">Detail</a>
            
            <?php if ($userRole === 'admin'): ?>
              <a href="dashboard_admin.php" class="card-btn detail">⚙️ Kelola</a>
            <?php elseif ($isDaftar): ?>
              <span class="card-btn enrolled">✓ Terdaftar</span>
            <?php elseif ($isFull): ?>
              <span class="card-btn full">Kuota Penuh</span>
            <?php elseif (!$userId): ?>
              <a href="login.php" class="card-btn primary">Daftar</a>
            <?php else: ?>
              <form method="POST" action="detail_event.php" style="flex:1" onsubmit="return confirm('Daftar event ini?')">
                <input type="hidden" name="act" value="daftar">
                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <button type="submit" class="card-btn primary" style="width:100%">Daftar</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</main>

<script>
  function applyTheme(mode) {
    if (mode === 'light') {
      document.body.classList.add('light-mode');
    } else {
      document.body.classList.remove('light-mode');
    }
  }
  function toggleTheme() {
    if (document.body.classList.contains('light-mode')) {
      document.body.classList.remove('light-mode');
      localStorage.setItem('theme', 'dark');
    } else {
      document.body.classList.add('light-mode');
      localStorage.setItem('theme', 'light');
    }
  }
  (function() {
    const saved = localStorage.getItem('theme');
    if (saved === 'light') applyTheme('light');
    else applyTheme('dark');
  })();
  setTimeout(() => {
    const f = document.querySelector('.flash');
    if (f) { f.style.opacity='0'; f.style.transition='opacity 0.5s'; setTimeout(()=>f.remove(),500); }
  }, 3500);
</script>
</body>
</html>