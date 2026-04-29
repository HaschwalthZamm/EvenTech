<?php
/**
 * dashboard_admin.php — Dashboard Administrator
 * EvenTech Platform
 */

require_once 'includes/guard.php';
require_once 'koneksi.php';
guard_admin();

$flash = getFlash();

// ── Handle POST actions (CRUD event & user) ───────────────
$act = $_POST['act'] ?? '';

// Tambah Event
if ($act === 'tambah_event') {
    $judul    = esc($_POST['judul'] ?? '');
    $deskripsi= esc($_POST['deskripsi'] ?? '');
    $kategori = esc($_POST['kategori'] ?? 'seminar');
    $tanggal  = esc($_POST['tanggal'] ?? '');
    $waktu    = esc($_POST['waktu'] ?? '00:00');
    $lokasi   = esc($_POST['lokasi'] ?? '');
    $kuota    = (int)($_POST['kuota'] ?? 100);
    $harga    = (int)($_POST['harga'] ?? 0);
    $uid      = $currentUser['id'];

    $stmt = $conn->prepare("INSERT INTO events (judul,deskripsi,kategori,tanggal,waktu,lokasi,kuota,harga,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssssii', $judul,$deskripsi,$kategori,$tanggal,$waktu,$lokasi,$kuota,$harga,$uid);
    $stmt->execute() ? setFlash('success','Event berhasil ditambahkan!') : setFlash('error','Gagal menambah event.');
    $stmt->close();
    header('Location: dashboard_admin.php'); exit;
}

// Edit Event
if ($act === 'edit_event') {
    $id       = (int)$_POST['id'];
    $judul    = esc($_POST['judul'] ?? '');
    $deskripsi= esc($_POST['deskripsi'] ?? '');
    $kategori = esc($_POST['kategori'] ?? 'seminar');
    $tanggal  = esc($_POST['tanggal'] ?? '');
    $waktu    = esc($_POST['waktu'] ?? '00:00');
    $lokasi   = esc($_POST['lokasi'] ?? '');
    $kuota    = (int)($_POST['kuota'] ?? 100);
    $harga    = (int)($_POST['harga'] ?? 0);

    $stmt = $conn->prepare("UPDATE events SET judul=?,deskripsi=?,kategori=?,tanggal=?,waktu=?,lokasi=?,kuota=?,harga=? WHERE id=?");
    $stmt->bind_param('ssssssiii', $judul,$deskripsi,$kategori,$tanggal,$waktu,$lokasi,$kuota,$harga,$id);
    $stmt->execute() ? setFlash('success','Event berhasil diperbarui!') : setFlash('error','Gagal memperbarui event.');
    $stmt->close();
    header('Location: dashboard_admin.php'); exit;
}

// Hapus Event
if ($act === 'hapus_event') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute() ? setFlash('success','Event berhasil dihapus.') : setFlash('error','Gagal menghapus event.');
    $stmt->close();
    header('Location: dashboard_admin.php'); exit;
}

// Hapus User
if ($act === 'hapus_user') {
    $id = (int)$_POST['id'];
    if ($id === $currentUser['id']) {
        setFlash('error','Tidak bisa menghapus akun sendiri.');
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='user'");
        $stmt->bind_param('i', $id);
        $stmt->execute() ? setFlash('success','User berhasil dihapus.') : setFlash('error','Gagal menghapus user.');
        $stmt->close();
    }
    header('Location: dashboard_admin.php'); exit;
}

// ── Ambil data statistik ──────────────────────────────────
$total_events  = $conn->query("SELECT COUNT(*) FROM events")->fetch_row()[0];
$total_users   = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$total_reg     = $conn->query("SELECT COUNT(*) FROM registrasi")->fetch_row()[0];
$event_aktif   = $conn->query("SELECT COUNT(*) FROM events WHERE tanggal >= CURDATE()")->fetch_row()[0];

// ── Ambil semua events ────────────────────────────────────
$events = $conn->query("SELECT e.*, COUNT(r.id) as peserta FROM events e LEFT JOIN registrasi r ON r.event_id=e.id GROUP BY e.id ORDER BY e.tanggal DESC");

// ── Ambil semua users (role=user) ─────────────────────────
$users = $conn->query("SELECT u.*, COUNT(r.id) as total_reg FROM users u LEFT JOIN registrasi r ON r.user_id=u.id WHERE u.role='user' GROUP BY u.id ORDER BY u.created_at DESC");

// Untuk edit: ambil event yg dipilih
$editEvent = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = $conn->prepare("SELECT * FROM events WHERE id=?");
    $res->bind_param('i', $eid);
    $res->execute();
    $editEvent = $res->get_result()->fetch_assoc();
    $res->close();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — EvenTech</title>
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

    /* ── SIDEBAR ───────────────────────────────────── */
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

    .sidebar-logo {
      display: flex; align-items: center; gap: 10px;
      padding: 0 8px;
      margin-bottom: 36px;
    }

    .sidebar-logo .ico {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
    }

    .sidebar-logo .name {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 17px;
    }

    .sidebar-logo .name span { color: var(--gold); }

    .sidebar-label {
      font-size: 10px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--text-sub);
      padding: 0 12px;
      margin-bottom: 8px;
      margin-top: 20px;
    }

    .nav-item {
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
      margin-bottom: 2px;
    }

    .nav-item:hover { background: var(--surface); color: var(--text); }
    .nav-item.active { background: rgba(200,150,62,0.15); color: var(--gold-lt); }
    .nav-item.active .nav-ico { color: var(--gold); }

    .nav-ico { font-size: 16px; width: 20px; text-align: center; }

    .sidebar-bottom { margin-top: auto; }

    .user-chip {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px;
      background: var(--surface);
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
    }

    .user-avatar {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 14px;
      color: #fff;
      flex-shrink: 0;
    }

    .user-info .uname { font-size: 13px; font-weight: 600; }
    .user-info .urole { font-size: 11px; color: var(--gold); }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 12px;
      border-radius: var(--radius-sm);
      color: var(--red);
      font-size: 13px;
      text-decoration: none;
      transition: background 0.2s;
      margin-top: 8px;
    }

    .btn-logout:hover { background: rgba(255,85,114,0.1); }

    /* ── MAIN ──────────────────────────────────────── */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      padding: 32px 36px;
      overflow-x: hidden;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 32px;
    }

    .page-title h1 {
      font-family: 'Syne', sans-serif;
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .page-title p { color: var(--text-sub); font-size: 13px; margin-top: 3px; }

    /* Flash */
    .flash {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: var(--radius-sm);
      font-size: 13px; margin-bottom: 24px;
      animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .flash.error   { background: rgba(255,85,114,0.12);  border: 1px solid rgba(255,85,114,0.3);  color: #FF8EA3; }
    .flash.success { background: rgba(34,197,94,0.12);   border: 1px solid rgba(34,197,94,0.3);   color: #4ADE80; }

    /* ── STAT CARDS ────────────────────────────────── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 22px;
      transition: transform 0.2s, box-shadow 0.2s;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
    }

    .stat-card.c1::before { background: linear-gradient(90deg, var(--gold), var(--amber)); }
    .stat-card.c2::before { background: linear-gradient(90deg, var(--teal), #06B6D4); }
    .stat-card.c3::before { background: linear-gradient(90deg, var(--purple), #EC4899); }
    .stat-card.c4::before { background: linear-gradient(90deg, var(--green), #84CC16); }

    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }

    .stat-icon {
      width: 42px; height: 42px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
      margin-bottom: 16px;
    }

    .stat-card.c1 .stat-icon { background: rgba(200,150,62,0.15); }
    .stat-card.c2 .stat-icon { background: rgba(45,212,191,0.12); }
    .stat-card.c3 .stat-icon { background: rgba(167,139,250,0.12); }
    .stat-card.c4 .stat-icon { background: rgba(34,197,94,0.12); }

    .stat-num {
      font-family: 'Syne', sans-serif;
      font-size: 32px;
      font-weight: 800;
      letter-spacing: -1px;
      line-height: 1;
    }

    .stat-card.c1 .stat-num { color: var(--gold-lt); }
    .stat-card.c2 .stat-num { color: var(--teal); }
    .stat-card.c3 .stat-num { color: var(--purple); }
    .stat-card.c4 .stat-num { color: var(--green); }

    .stat-label { color: var(--text-sub); font-size: 13px; margin-top: 4px; }

    /* ── SECTION PANELS ────────────────────────────── */
    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }

    .section-head h2 {
      font-family: 'Syne', sans-serif;
      font-size: 18px;
      font-weight: 700;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      border-radius: var(--radius-sm);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      border: none;
      transition: all 0.2s;
    }

    .btn-gold {
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      color: #fff;
    }

    .btn-gold:hover { opacity: 0.9; transform: translateY(-1px); }

    .btn-ghost {
      background: var(--surface2);
      color: var(--text-sub);
      border: 1px solid var(--border);
    }

    .btn-ghost:hover { color: var(--text); border-color: rgba(255,255,255,0.2); }

    .btn-danger {
      background: rgba(255,85,114,0.12);
      color: var(--red);
      border: 1px solid rgba(255,85,114,0.2);
    }

    .btn-danger:hover { background: rgba(255,85,114,0.2); }

    .btn-sm { padding: 6px 12px; font-size: 12px; }

    /* ── TABLE ─────────────────────────────────────── */
    .table-wrap {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      margin-bottom: 32px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      background: var(--surface2);
      padding: 13px 16px;
      text-align: left;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--text-sub);
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 14px 16px;
      font-size: 13px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255,255,255,0.02); }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 100px;
      font-size: 11px;
      font-weight: 600;
      text-transform: capitalize;
    }

    .badge-seminar    { background: rgba(200,150,62,0.15); color: var(--gold-lt); }
    .badge-workshop   { background: rgba(45,212,191,0.12); color: var(--teal); }
    .badge-lomba      { background: rgba(167,139,250,0.12); color: var(--purple); }
    .badge-webinar    { background: rgba(34,197,94,0.12); color: var(--green); }
    .badge-conference { background: rgba(240,165,0,0.12); color: var(--amber); }
    .badge-bootcamp   { background: rgba(249,115,22,0.12); color: #FB923C; }

    .actions { display: flex; gap: 6px; }

    /* ── MODAL ─────────────────────────────────────── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .modal-overlay.open { display: flex; }

    .modal {
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      width: 100%;
      max-width: 540px;
      max-height: 90vh;
      overflow-y: auto;
      padding: 28px;
      animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
    }

    @keyframes modalIn {
      from { opacity: 0; transform: scale(0.92) translateY(20px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
    }

    .modal-head h3 {
      font-family: 'Syne', sans-serif;
      font-size: 18px;
      font-weight: 700;
    }

    .close-btn {
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--text-sub);
      width: 32px; height: 32px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s;
    }

    .close-btn:hover { color: var(--text); background: var(--surface2); }

    /* Form inside modal */
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; color: var(--text-sub); margin-bottom: 7px; font-weight: 500; }
    .form-group input, .form-group textarea, .form-group select {
      width: 100%;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      padding: 10px 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      -webkit-appearance: none;
    }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(200,150,62,0.15);
    }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-group select option { background: var(--surface2); }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }

    /* ── Tabs ──────────────────────────────────────── */
    .tabs {
      display: flex;
      gap: 4px;
      background: var(--surface);
      border-radius: var(--radius-sm);
      padding: 4px;
      margin-bottom: 24px;
      width: fit-content;
      border: 1px solid var(--border);
    }

    .tab-btn {
      padding: 8px 20px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-sub);
      cursor: pointer;
      border: none;
      background: none;
      transition: all 0.2s;
    }

    .tab-btn.active { background: linear-gradient(135deg, var(--gold-dk), var(--gold)); color: #fff; }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ── Responsive ────────────────────────────────── */
    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 900px) {
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

  <span class="sidebar-label">Menu Utama</span>
  <a class="nav-item active" onclick="showTab('events')">
    <span class="nav-ico">🗓</span> Kelola Event
  </a>
  <a class="nav-item" onclick="showTab('users')">
    <span class="nav-ico">👥</span> Kelola User
  </a>
  <a class="nav-item" href="event.php">
    <span class="nav-ico">🌐</span> Lihat Event Page
  </a>

  <div class="sidebar-bottom">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(substr($currentUser['nama'], 0, 1)) ?></div>
      <div class="user-info">
        <div class="uname"><?= htmlspecialchars($currentUser['nama']) ?></div>
        <div class="urole">Administrator</div>
      </div>
    </div>
    <a class="btn-logout" href="logout.php">🚪 Keluar</a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <div class="page-title">
      <h1>Dashboard Admin</h1>
      <p>Selamat datang, <?= htmlspecialchars($currentUser['nama']) ?> — Kelola semua event & user di sini.</p>
    </div>
    <button class="btn btn-gold" onclick="openModal('modal-event')">＋ Tambah Event</button>
  </div>

  <!-- Flash -->
  <?php if ($flash): ?>
  <div class="flash <?= $flash['type'] ?>">
    <?= $flash['type'] === 'error' ? '✕' : '✓' ?>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card c1">
      <div class="stat-icon">🗓</div>
      <div class="stat-num"><?= $total_events ?></div>
      <div class="stat-label">Total Event</div>
    </div>
    <div class="stat-card c2">
      <div class="stat-icon">👥</div>
      <div class="stat-num"><?= $total_users ?></div>
      <div class="stat-label">Total User</div>
    </div>
    <div class="stat-card c3">
      <div class="stat-icon">📋</div>
      <div class="stat-num"><?= $total_reg ?></div>
      <div class="stat-label">Total Registrasi</div>
    </div>
    <div class="stat-card c4">
      <div class="stat-icon">🚀</div>
      <div class="stat-num"><?= $event_aktif ?></div>
      <div class="stat-label">Event Mendatang</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="showTab('events')">🗓 Event (<?= $total_events ?>)</button>
    <button class="tab-btn" onclick="showTab('users')">👥 User (<?= $total_users ?>)</button>
  </div>

  <!-- Tab: Events -->
  <div class="tab-panel active" id="tab-events">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Judul Event</th>
            <th>Kategori</th>
            <th>Tanggal</th>
            <th>Kuota</th>
            <th>Peserta</th>
            <th>Harga</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($ev = $events->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--text-sub)"><?= $no++ ?></td>
            <td>
              <strong><?= htmlspecialchars($ev['judul']) ?></strong>
              <div style="font-size:11px;color:var(--text-sub);margin-top:2px"><?= htmlspecialchars($ev['lokasi']) ?></div>
            </td>
            <td><span class="badge badge-<?= $ev['kategori'] ?>"><?= ucfirst($ev['kategori']) ?></span></td>
            <td><?= tglIndo($ev['tanggal']) ?></td>
            <td><?= $ev['kuota'] ?></td>
            <td>
              <span style="color:<?= $ev['peserta'] >= $ev['kuota'] ? 'var(--red)' : 'var(--green)' ?>">
                <?= $ev['peserta'] ?>/<?= $ev['kuota'] ?>
              </span>
            </td>
            <td><?= rupiah($ev['harga']) ?></td>
            <td>
              <div class="actions">
                <button class="btn btn-ghost btn-sm"
                  onclick="editEvent(<?= htmlspecialchars(json_encode($ev)) ?>)">✏ Edit</button>
                <form method="POST" onsubmit="return confirm('Hapus event ini?')">
                  <input type="hidden" name="act" value="hapus_event">
                  <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab: Users -->
  <div class="tab-panel" id="tab-users">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Event Diikuti</th>
            <th>Bergabung</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($usr = $users->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--text-sub)"><?= $no++ ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold-dk),var(--gold));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;color:#fff;flex-shrink:0">
                  <?= strtoupper(substr($usr['nama'],0,1)) ?>
                </div>
                <?= htmlspecialchars($usr['nama']) ?>
              </div>
            </td>
            <td style="color:var(--text-sub)"><?= htmlspecialchars($usr['email']) ?></td>
            <td><span style="color:var(--teal);font-weight:600"><?= $usr['total_reg'] ?> event</span></td>
            <td style="font-size:12px;color:var(--text-sub)"><?= date('d M Y', strtotime($usr['created_at'])) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Hapus user ini?')">
                <input type="hidden" name="act" value="hapus_user">
                <input type="hidden" name="id" value="<?= $usr['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">🗑 Hapus</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- MODAL: Tambah/Edit Event -->
<div class="modal-overlay" id="modal-event">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modal-title">Tambah Event</h3>
      <button class="close-btn" onclick="closeModal('modal-event')">✕</button>
    </div>

    <form method="POST" id="event-form">
      <input type="hidden" name="act" id="form-act" value="tambah_event">
      <input type="hidden" name="id"  id="form-id"  value="">

      <div class="form-group">
        <label>Judul Event *</label>
        <input type="text" name="judul" id="f-judul" placeholder="Contoh: AI Summit 2026" required>
      </div>

      <div class="form-group">
        <label>Deskripsi *</label>
        <textarea name="deskripsi" id="f-deskripsi" placeholder="Jelaskan tentang event ini..." required></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Kategori</label>
          <select name="kategori" id="f-kategori">
            <option value="seminar">Seminar</option>
            <option value="workshop">Workshop</option>
            <option value="lomba">Lomba / Hackathon</option>
            <option value="webinar">Webinar</option>
            <option value="conference">Conference</option>
            <option value="bootcamp">Bootcamp</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="closed">Closed</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tanggal *</label>
          <input type="date" name="tanggal" id="f-tanggal" required>
        </div>
        <div class="form-group">
          <label>Waktu</label>
          <input type="time" name="waktu" id="f-waktu" value="09:00">
        </div>
      </div>

      <div class="form-group">
        <label>Lokasi / Platform *</label>
        <input type="text" name="lokasi" id="f-lokasi" placeholder="Contoh: Jakarta Convention Center" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Kuota Peserta</label>
          <input type="number" name="kuota" id="f-kuota" value="100" min="1">
        </div>
        <div class="form-group">
          <label>Harga (Rp, 0 = Gratis)</label>
          <input type="number" name="harga" id="f-harga" value="0" min="0">
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-event')">Batal</button>
        <button type="submit" class="btn btn-gold">Simpan Event</button>
      </div>
    </form>
  </div>
</div>

<script>
  // ── Tab switching ───────────────────────────────
  const tabs = document.querySelectorAll('.tab-btn');
  function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    tabs.forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.add('active');
    tabs.forEach(b => { if (b.textContent.toLowerCase().includes(name)) b.classList.add('active'); });
  }
  tabs.forEach(b => b.addEventListener('click', () => {
    const t = b.textContent.includes('Event') ? 'events' : 'users';
    showTab(t);
  }));

  // ── Modal ───────────────────────────────────────
  function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
    // Reset form
    document.getElementById('form-act').value = 'tambah_event';
    document.getElementById('form-id').value  = '';
    document.getElementById('modal-title').textContent = 'Tambah Event';
    document.getElementById('event-form').reset();
  }

  // Close on overlay click
  document.getElementById('modal-event').addEventListener('click', function(e) {
    if (e.target === this) closeModal('modal-event');
  });

  // ── Edit Event ──────────────────────────────────
  function editEvent(ev) {
    document.getElementById('modal-title').textContent = 'Edit Event';
    document.getElementById('form-act').value  = 'edit_event';
    document.getElementById('form-id').value   = ev.id;
    document.getElementById('f-judul').value   = ev.judul;
    document.getElementById('f-deskripsi').value = ev.deskripsi;
    document.getElementById('f-kategori').value  = ev.kategori;
    document.getElementById('f-tanggal').value   = ev.tanggal;
    document.getElementById('f-waktu').value     = ev.waktu;
    document.getElementById('f-lokasi').value    = ev.lokasi;
    document.getElementById('f-kuota').value     = ev.kuota;
    document.getElementById('f-harga').value     = ev.harga;
    openModal('modal-event');
  }

  // Auto-close flash
  setTimeout(() => {
    const f = document.querySelector('.flash');
    if (f) f.style.display = 'none';
  }, 4000);
</script>
</body>
</html>
