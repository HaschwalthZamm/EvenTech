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
    /* --- CSS SAMA PERSIS SEPERTI SEBELUMNYA --- */
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
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; transition: background 0.3s, color 0.2s; }
    .sidebar { width: var(--sidebar-w); min-height: 100vh; background: var(--bg2); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 28px 16px; position: fixed; top: 0; left: 0; z-index: 100; transition: background 0.3s, border-color 0.2s; }
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
    .user-chip { display: flex; align-items: center; gap: 10px; padding: 12px; background: var(--surface); border-radius: var(--radius-sm); border: 1px solid var(--border); transition: background 0.3s; }
    .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg,var(--gold-dk),var(--gold)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Syne',sans-serif; font-weight: 800; font-size: 14px; color: #fff; flex-shrink: 0; }
    .user-info .uname { font-size: 13px; font-weight: 600; }
    .user-info .urole { font-size: 11px; color: var(--gold); }
    .btn-logout { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: var(--radius-sm); color: var(--red); font-size: 13px; text-decoration: none; transition: background 0.2s; margin-top: 8px; }
    .btn-logout:hover { background: rgba(255,85,114,0.1); }
    .main { margin-left: var(--sidebar-w); flex: 1; padding: 36px; }
    /* Topbar untuk judul dan toggle */
    .topbar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 20px;
    }
    .theme-toggle-btn {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 40px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        backdrop-filter: blur(4px);
    }
    .theme-toggle-btn:hover {
        background: var(--surface2);
        border-color: var(--gold);
    }
    .hero-banner { position: relative; background: linear-gradient(145deg, #1C1200, #2D1B00, #3D2500); border-radius: var(--radius); padding: 40px 48px; margin-bottom: 32px; overflow: hidden; border: 1px solid rgba(200,150,62,0.15); transition: background 0.3s, border-color 0.2s; }
    .hero-banner::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 60% 80% at 90% 50%, rgba(45,212,191,0.08) 0%, transparent 70%), radial-gradient(ellipse 50% 60% at 10% 30%, rgba(200,150,62,0.2) 0%, transparent 60%); }
    .hero-banner .inner { position: relative; z-index: 1; }
    .hero-tag { display: inline-flex; align-items: center; gap: 6px; background: rgba(200,150,62,0.15); border: 1px solid rgba(200,150,62,0.3); color: var(--gold-lt); font-size: 11px; font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; padding: 5px 12px; border-radius: 100px; margin-bottom: 16px; }
    .hero-banner h1 { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 10px; }
    .hero-banner h1 em { font-style: normal; color: var(--gold); }
    .hero-banner p { color: rgba(240,237,232,0.65); font-size: 14px; max-width: 500px; transition: color 0.2s; }
    .filter-row { display: flex; gap: 12px; align-items: center; margin-bottom: 28px; flex-wrap: wrap; }
    .search-wrap { position: relative; flex: 1; min-width: 220px; }
    .search-wrap input { width: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px; padding: 11px 14px 11px 40px; outline: none; transition: background 0.3s; }
    .search-wrap input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(200,150,62,0.12); }
    .search-wrap .ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-sub); }
    .filter-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .chip { padding: 8px 16px; border-radius: 100px; font-size: 12px; font-weight: 600; border: 1px solid var(--border); background: var(--surface); color: var(--text-sub); text-decoration: none; transition: all 0.2s; }
    .chip:hover { color: var(--text); border-color: rgba(255,255,255,0.2); }
    .chip.active { background: rgba(200,150,62,0.15); border-color: rgba(200,150,62,0.4); color: var(--gold-lt); }
    .section-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
    .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .event-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: transform 0.25s, box-shadow 0.25s, background 0.3s; display: flex; flex-direction: column; text-decoration: none; color: inherit; }
    .event-card:hover { transform: translateY(-5px); box-shadow: 0 20px 50px rgba(0,0,0,0.4); border-color: rgba(200,150,62,0.25); }
    .card-thumb { height: 140px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 48px; }
    .card-thumb.cat-seminar    { background: linear-gradient(135deg, #2D1B00, #5C3A00); }
    .card-thumb.cat-workshop   { background: linear-gradient(135deg, #00221A, #004D3A); }
    .card-thumb.cat-lomba      { background: linear-gradient(135deg, #1A0040, #3D0080); }
    .card-thumb.cat-webinar    { background: linear-gradient(135deg, #001A00, #003300); }
    .card-thumb.cat-conference { background: linear-gradient(135deg, #1A1400, #3D3000); }
    .card-thumb.cat-bootcamp   { background: linear-gradient(135deg, #1A0A00, #3D1A00); }
    .card-thumb .glow { position: absolute; inset: 0; background: radial-gradient(ellipse 70% 70% at 30% 30%, rgba(255,255,255,0.06) 0%, transparent 70%); }
    .card-badge { position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); border-radius: 100px; padding: 4px 10px; font-size: 11px; font-weight: 600; color: var(--text); }
    .card-price-tag { position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); border-radius: 100px; padding: 4px 10px; font-size: 11px; font-weight: 700; }
    .card-price-tag.gratis { color: var(--green); border-color: rgba(34,197,94,0.3); }
    .card-price-tag.bayar  { color: var(--amber); }
    .card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; }
    .card-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; margin-bottom: 8px; line-height: 1.3; }
    .card-desc { font-size: 12px; color: var(--text-sub); line-height: 1.6; margin-bottom: 14px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .card-meta { display: flex; gap: 14px; font-size: 12px; color: var(--text-sub); margin-bottom: 16px; }
    .card-meta span { display: flex; align-items: center; gap: 5px; }
    .kuota-wrap { margin-bottom: 16px; }
    .kuota-label { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-sub); margin-bottom: 5px; }
    .kuota-bar { height: 4px; background: var(--surface2); border-radius: 2px; overflow: hidden; }
    .kuota-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--gold-dk), var(--gold)); transition: width 0.5s; }
    .kuota-fill.full { background: linear-gradient(90deg, var(--red), #FF8EA3); }
    .card-btn { display: block; width: 100%; padding: 11px; border-radius: var(--radius-sm); font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; text-align: center; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; }
    .card-btn.primary { background: linear-gradient(135deg, var(--gold-dk), var(--gold)); color: #fff; }
    .card-btn.primary:hover { opacity: 0.9; transform: translateY(-1px); }
    .card-btn.enrolled { background: rgba(34,197,94,0.12); color: var(--green); border: 1px solid rgba(34,197,94,0.25); cursor: default; }
    .card-btn.full { background: rgba(255,85,114,0.1); color: var(--red); border: 1px solid rgba(255,85,114,0.2); cursor: default; }
    .card-btn.detail { background: var(--surface2); color: var(--text-sub); border: 1px solid var(--border); }
    .card-btn.detail:hover { color: var(--text); }
    .empty-state { text-align: center; padding: 80px 20px; color: var(--text-sub); }
    .empty-state .ico { font-size: 56px; margin-bottom: 16px; }
    .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 20px; color: var(--text); margin-bottom: 8px; }
    .flash { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 20px; }
    .flash.error   { background: rgba(255,85,114,0.12); border: 1px solid rgba(255,85,114,0.3); color: #FF8EA3; }
    .flash.success { background: rgba(34,197,94,0.12);  border: 1px solid rgba(34,197,94,0.3);  color: #4ADE80; }

    /* Light mode override */
    body.light-mode {
        --bg: #F5F7FA;
        --bg2: #FFFFFF;
        --surface: #F0F2F5;
        --surface2: #E4E7EB;
        --border: rgba(0,0,0,0.1);
        --text: #1E293B;
        --text-sub: #64748B;
        --gold: #C8963E;
        --gold-lt: #D9A451;
        --gold-dk: #A87A2E;
        --amber: #F0A500;
        --teal: #0D9488;
        --purple: #7C3AED;
        --red: #DC2626;
        --green: #16A34A;
    }
    body.light-mode .hero-banner {
        background: linear-gradient(145deg, #E8DCC8, #D4C4A8);
        border-color: rgba(200,150,62,0.3);
    }
    body.light-mode .hero-banner p {
        color: #334155;
    }
    body.light-mode .hero-tag {
        background: rgba(200,150,62,0.2);
        border-color: rgba(200,150,62,0.4);
    }
    body.light-mode .card-thumb .glow {
        background: radial-gradient(ellipse 70% 70% at 30% 30%, rgba(0,0,0,0.05) 0%, transparent 70%);
    }
    body.light-mode .card-badge {
        background: rgba(255,255,255,0.7);
        color: #1E293B;
    }
    body.light-mode .card-price-tag {
        background: rgba(255,255,255,0.7);
    }
    body.light-mode .chip {
        background: var(--surface);
        border-color: var(--border);
    }
    body.light-mode .chip.active {
        background: rgba(200,150,62,0.2);
        border-color: rgba(200,150,62,0.5);
    }

    @media (max-width: 900px) { .sidebar { transform: translateX(-100%); } .main { margin-left: 0; padding: 20px 16px; } .events-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<!-- SIDEBAR (tanpa toggle) -->
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

<!-- MAIN -->
<main class="main">
  <!-- Topbar dengan toggle di kanan atas -->
  <div class="topbar">
    <button id="theme-toggle" class="theme-toggle-btn">🌓 Mode Terang/Gelap</button>
  </div>

  <?php if ($flash): ?>
  <div class="flash <?= $flash['type'] ?>">
    <?= $flash['type'] === 'error' ? '✕' : '✓' ?> <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif; ?>

  <div class="hero-banner">
    <div class="inner">
      <div class="hero-tag">✨ <?= $filter === 'my' ? 'Event Favoritmu' : 'Explore Events' ?></div>
      <h1><?= $filter === 'my' ? 'Event yang <em>Kamu Ikuti</em>' : 'Temukan Event IT<br><em>Terbaik</em> untukmu' ?></h1>
      <p><?= $filter === 'my' ? 'Berikut daftar event yang sudah kamu daftarkan. Jangan sampai ketinggalan informasi terbaru.' : 'Seminar, workshop, hackathon, hingga webinar — semuanya ada di sini. Daftarkan dirimu dan tingkatkan skill teknologimu.' ?></p>
    </div>
  </div>

  <!-- Filter bar -->
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
  $emojis = ['seminar'=>'🎤','workshop'=>'🛠','lomba'=>'🏆','webinar'=>'🎙️','conference'=>'🎓','bootcamp'=>'🚀'];
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
    <div class="events-grid">
      <?php while ($ev = $events->fetch_assoc()):
        $pct = $ev['kuota'] > 0 ? round($ev['peserta'] / $ev['kuota'] * 100) : 0;
        $isFull = $ev['peserta'] >= $ev['kuota'];
        $isDaftar = ($userId && $ev['sudah_daftar'] == 1);
      ?>
      <div class="event-card">
        <div class="card-thumb cat-<?= $ev['kategori'] ?>">
          <div class="glow"></div>
          <span><?= $emojis[$ev['kategori']] ?? '📅' ?></span>
          <div class="card-badge"><?= ucfirst($ev['kategori']) ?></div>
          <div class="card-price-tag <?= $ev['harga'] == 0 ? 'gratis' : 'bayar' ?>"><?= rupiah((int)$ev['harga']) ?></div>
        </div>
        <div class="card-body">
          <div class="card-title"><?= htmlspecialchars($ev['judul']) ?></div>
          <div class="card-desc"><?= htmlspecialchars($ev['deskripsi']) ?></div>
          <div class="card-meta">
            <span>📅 <?= tglIndo($ev['tanggal']) ?></span>
            <span>📍 <?= mb_strimwidth(htmlspecialchars($ev['lokasi']), 0, 22, '...') ?></span>
          </div>
          <div class="kuota-wrap">
            <div class="kuota-label">
              <span>Peserta terdaftar</span>
              <span><?= $ev['peserta'] ?>/<?= $ev['kuota'] ?></span>
            </div>
            <div class="kuota-bar">
              <div class="kuota-fill <?= $isFull ? 'full' : '' ?>" style="width:<?= min($pct,100) ?>%"></div>
            </div>
          </div>
          <div style="display: flex; gap: 8px;">
            <a href="detail_event.php?id=<?= $ev['id'] ?>" class="card-btn detail" style="flex:1">Detail →</a>
            
            <?php if ($userRole === 'admin'): ?>
              <a href="dashboard_admin.php" class="card-btn detail" style="flex:2; background:var(--surface2);">⚙ Kelola Event</a>
            <?php elseif ($filter === 'my'): ?>
              <span class="card-btn enrolled" style="flex:2">✓ Terdaftar</span>
            <?php elseif ($isDaftar): ?>
              <span class="card-btn enrolled" style="flex:2">✓ Terdaftar</span>
            <?php elseif ($isFull): ?>
              <span class="card-btn full" style="flex:2">Kuota Penuh</span>
            <?php elseif (!$userId): ?>
              <a href="login.php" class="card-btn primary" style="flex:2; text-align:center; text-decoration:none;">Daftar Sekarang</a>
            <?php else: ?>
              <form method="POST" action="detail_event.php" style="flex:2" onsubmit="return confirm('Yakin untuk daftar?')">
                <input type="hidden" name="act" value="daftar">
                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <button type="submit" class="card-btn primary" style="width:100%">Daftar Sekarang</button>
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
  // Theme toggle
  const toggleBtn = document.getElementById('theme-toggle');
  const currentTheme = localStorage.getItem('theme');
  if (currentTheme === 'light') {
    document.body.classList.add('light-mode');
  }
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      document.body.classList.toggle('light-mode');
      if (document.body.classList.contains('light-mode')) {
        localStorage.setItem('theme', 'light');
      } else {
        localStorage.setItem('theme', 'dark');
      }
    });
  }
  // Auto close flash
  setTimeout(() => {
    const f = document.querySelector('.flash');
    if (f) { f.style.opacity='0'; f.style.transition='opacity 0.5s'; setTimeout(()=>f.remove(),500); }
  }, 3500);
</script>
</body>
</html>