<?php
/**
 * register.php — Halaman Registrasi (hanya untuk user)
 * EvenTech Platform
 */

session_start();

// Jika sudah login, redirect ke event.php
if (isset($_SESSION['user_id'])) {
    header('Location: event.php');
    exit;
}

require_once 'koneksi.php';

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar — EvenTech</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <style>
    /* ── CSS Variables ─────────────────────────────────── */
    :root {
      --bg:        #0D0F14;
      --bg2:       #13161E;
      --surface:   #1A1D27;
      --surface2:  #21253A;
      --border:    rgba(255,255,255,0.07);
      --gold:      #C8963E;
      --gold-lt:   #E8B86D;
      --gold-dk:   #8A641F;
      --amber:     #F0A500;
      --teal:      #2DD4BF;
      --text:      #F0EDE8;
      --text-sub:  #9B98A0;
      --red:       #FF5572;
      --green:     #22C55E;
      --radius:    16px;
      --radius-sm: 10px;
      --shadow:    0 24px 60px rgba(0,0,0,0.5);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      overflow: hidden;
    }

    /* ── LEFT PANEL ──────────────────────────────────── */
    .left-panel {
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px;
      background: linear-gradient(145deg, #1C1200 0%, #2D1B00 40%, #3D2500 70%, #1A1500 100%);
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 60% 60% at 30% 20%, rgba(200,150,62,0.25) 0%, transparent 60%),
        radial-gradient(ellipse 50% 50% at 80% 80%, rgba(45,212,191,0.1) 0%, transparent 60%);
    }

    .hex-grid {
      position: absolute;
      top: 0; right: -100px;
      width: 500px; height: 100%;
      opacity: 0.06;
      background-image:
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66L0 50V17L28 0l28 17v33z' fill='none' stroke='%23C8963E' stroke-width='1'/%3E%3Cpath d='M28 100L0 83V50l28-17 28 17v33z' fill='none' stroke='%23C8963E' stroke-width='1'/%3E%3C/svg%3E");
    }

    .left-logo {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 1;
    }

    .left-logo .logo-icon {
      width: 44px; height: 44px;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
    }

    .left-logo .logo-name {
      font-family: 'Syne', sans-serif;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .left-logo .logo-name span { color: var(--gold); }

    .left-hero {
      position: relative;
      z-index: 1;
    }

    .badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(200,150,62,0.15);
      border: 1px solid rgba(200,150,62,0.3);
      color: var(--gold-lt);
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      padding: 6px 14px;
      border-radius: 100px;
      margin-bottom: 24px;
    }

    .badge-pill::before {
      content: '';
      width: 6px; height: 6px;
      background: var(--gold);
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(1.4); }
    }

    .left-hero h1 {
      font-family: 'Syne', sans-serif;
      font-size: clamp(32px, 3.5vw, 50px);
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 20px;
      letter-spacing: -1px;
    }

    .left-hero h1 em {
      font-style: normal;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt), var(--amber));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .left-hero p {
      color: rgba(240,237,232,0.65);
      font-size: 15px;
      line-height: 1.7;
      max-width: 380px;
      margin-bottom: 36px;
    }

    .stats-row {
      display: flex;
      gap: 24px;
    }

    .stat-item {
      display: flex;
      flex-direction: column;
    }

    .stat-item .num {
      font-family: 'Syne', sans-serif;
      font-size: 28px;
      font-weight: 800;
      color: var(--gold-lt);
    }

    .stat-item .lbl {
      font-size: 12px;
      color: var(--text-sub);
      margin-top: 2px;
    }

    .features {
      position: relative;
      z-index: 1;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .feat-tag {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.1);
      color: rgba(240,237,232,0.7);
      font-size: 12px;
      padding: 7px 14px;
      border-radius: 100px;
    }

    /* ── RIGHT PANEL (Form) ── (ditambah overflow agar bisa scroll) */
    .right-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 56px;
      background: var(--bg2);
      overflow-y: auto;   /* tambahan agar bisa scroll jika konten melebihi tinggi */
      height: 100vh;      /* agar scroll memenuhi viewport */
    }

    .auth-card {
      width: 100%;
      max-width: 400px;
      animation: slideUp 0.5s ease both;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .auth-card h2 {
      font-family: 'Syne', sans-serif;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 6px;
      letter-spacing: -0.5px;
    }

    .auth-card .subtitle {
      color: var(--text-sub);
      font-size: 14px;
      margin-bottom: 32px;
    }

    .mode-toggle {
      display: flex;
      background: var(--surface);
      border-radius: var(--radius-sm);
      padding: 4px;
      margin-bottom: 28px;
      border: 1px solid var(--border);
    }

    .mode-toggle a {
      flex: 1;
      text-align: center;
      padding: 9px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      color: var(--text-sub);
      text-decoration: none;
      transition: all 0.25s;
    }

    .mode-toggle a.active {
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      color: #fff;
    }

    .flash {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      margin-bottom: 20px;
      animation: slideUp 0.3s ease;
    }

    .flash.error   { background: rgba(255,85,114,0.12); border: 1px solid rgba(255,85,114,0.3); color: #FF8EA3; }
    .flash.success { background: rgba(34,197,94,0.12);  border: 1px solid rgba(34,197,94,0.3);  color: #4ADE80; }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      color: var(--text-sub);
      margin-bottom: 8px;
      letter-spacing: 0.3px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap .icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-sub);
      font-size: 16px;
      pointer-events: none;
    }

    .input-wrap input,
    .input-wrap select {
      width: 100%;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      padding: 12px 14px 12px 42px;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }

    .input-wrap input:focus,
    .input-wrap select:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(200,150,62,0.15);
    }

    .input-wrap select option { background: var(--surface2); }

    .pass-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-sub);
      cursor: pointer;
      font-size: 16px;
      padding: 2px;
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, var(--gold-dk), var(--gold));
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      font-family: 'Syne', sans-serif;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 0.3px;
      transition: all 0.25s;
      margin-top: 8px;
    }

    .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--text-sub);
      font-size: 12px;
      margin: 20px 0;
    }

    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .demo-box {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 14px;
      font-size: 12px;
      text-align: center;
    }

    .demo-box a {
      color: var(--gold-lt);
      text-decoration: none;
    }

    @media (max-width: 768px) {
      body { grid-template-columns: 1fr; }
      .left-panel { display: none; }
      .right-panel { padding: 32px 24px; height: auto; }
    }
  </style>
</head>
<body>

  <!-- LEFT PANEL -->
  <div class="left-panel">
    <div class="hex-grid"></div>
    <div class="left-logo">
      <div class="logo-icon">⚡</div>
      <div class="logo-name">Even<span>Tech</span></div>
    </div>
    <div class="left-hero">
      <div class="badge-pill">Platform Event IT #1</div>
      <h1>Gabung Komunitas<br>Teknologi <em>Sekarang.</em></h1>
      <p>Daftar gratis dan mulailah perjalananmu dalam dunia event IT. Akses webinar, workshop, hackathon, dan perluas network profesionalmu.</p>
    </div>
    <div class="features">
      <span class="feat-tag">🏆 Hackathon</span>
      <span class="feat-tag">📡 Webinar</span>
      <span class="feat-tag">🛠 Workshop</span>
      <span class="feat-tag">🎓 Seminar</span>
      <span class="feat-tag">🚀 Bootcamp</span>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">
    <div class="auth-card">
      <h2>Buat Akun</h2>
      <p class="subtitle">Daftar gratis dan mulai perjalanan teknologimu.</p>

      <div class="mode-toggle">
        <a href="login.php">Masuk</a>
        <a href="register.php" class="active">Daftar</a>
      </div>

      <?php if ($flash): ?>
      <div class="flash <?= $flash['type'] ?>">
        <?= $flash['type'] === 'error' ? '✕' : '✓' ?> <?= htmlspecialchars($flash['msg']) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="auth.php" onsubmit="return validatePassword()">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="role" value="user">

        <div class="form-group">
          <label>Nama Lengkap</label>
          <div class="input-wrap">
            <span class="icon">👤</span>
            <input type="text" name="nama" placeholder="Irsyad Zamali" required autocomplete="name">
          </div>
        </div>

        <div class="form-group">
          <label>Email</label>
          <div class="input-wrap">
            <span class="icon">✉</span>
            <input type="email" name="email" placeholder="kamu@email.com" required autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <span class="icon">🔒</span>
            <input type="password" name="password" id="passField" placeholder="Minimal 6 karakter" required autocomplete="new-password">
            <button type="button" class="pass-toggle" onclick="togglePass()">👁</button>
          </div>
        </div>

        <!-- TAMBAHAN: verifikasi password -->
        <div class="form-group">
          <label>Verifikasi Password</label>
          <div class="input-wrap">
            <span class="icon">🔒</span>
            <input type="password" name="password_confirm" id="passConfirm" placeholder="Ulangi password" required>
            <button type="button" class="pass-toggle" onclick="togglePassConfirm()">👁</button>
          </div>
        </div>

        <button type="submit" class="btn-primary">Buat Akun Gratis →</button>
      </form>

      <div class="divider"></div>
      <div class="demo-box">
        <p>Sudah punya akun? <a href="login.php" style="color: var(--gold-lt);">Login di sini →</a></p>
      </div>
    </div>
  </div>

  <script>
    function togglePass() {
      const f = document.getElementById('passField');
      f.type = f.type === 'password' ? 'text' : 'password';
    }
    function togglePassConfirm() {
      const f = document.getElementById('passConfirm');
      f.type = f.type === 'password' ? 'text' : 'password';
    }
    function validatePassword() {
      const pass = document.getElementById('passField').value;
      const confirm = document.getElementById('passConfirm').value;
      if (pass !== confirm) {
        alert('Password dan verifikasi password tidak cocok!');
        return false;
      }
      if (pass.length < 6) {
        alert('Password minimal 6 karakter!');
        return false;
      }
      return true;
    }
  </script>
</body>
</html>