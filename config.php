<?php
// ============================================================
//  EvenTech — config.php
// ============================================================
define('DB_FILE',  __DIR__ . '/eventech.db');
define('APP_NAME', 'EvenTech');
define('UPLOAD_DIR', __DIR__ . '/uploads/events/');
define('UPLOAD_URL', 'uploads/events/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        initDB($pdo);
    }
    return $pdo;
}

function initDB(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        nama       TEXT    NOT NULL,
        email      TEXT    NOT NULL UNIQUE,
        password   TEXT    NOT NULL,
        role       TEXT    NOT NULL DEFAULT 'user',
        status     TEXT    NOT NULL DEFAULT 'aktif',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS events (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        judul         TEXT    NOT NULL,
        deskripsi     TEXT,
        kategori      TEXT    DEFAULT 'Seminar',
        tanggal       DATE    NOT NULL,
        waktu_mulai   TEXT,
        waktu_selesai TEXT,
        lokasi        TEXT,
        kuota         INTEGER DEFAULT 100,
        harga         REAL    DEFAULT 0,
        gambar        TEXT    DEFAULT NULL,
        status        TEXT    DEFAULT 'Aktif',
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS registrasi (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id    INTEGER NOT NULL,
        event_id   INTEGER NOT NULL,
        status     TEXT    DEFAULT 'Terdaftar',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        UNIQUE(user_id, event_id)
    );
    ");

    // Migrasi: tambah kolom gambar jika belum ada (untuk DB yang sudah ada)
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN gambar TEXT DEFAULT NULL");
    } catch (PDOException $e) { /* kolom sudah ada, abaikan */ }

    // Seed data
    $cnt = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($cnt == 0) {
        $us = $pdo->prepare("INSERT INTO users (nama,email,password,role) VALUES (?,?,?,?)");
        // 4 Admin
        $us->execute(['Zamali',  'zam@admin.id',   hashPassword('zamali'),  'admin']);
        $us->execute(['Opick',   'opick@admin.id', hashPassword('opick'),   'admin']);
        $us->execute(['Zahra',   'zahra@admin.id', hashPassword('zahra'),   'admin']);
        $us->execute(['Nova',    'nova@admin.id',  hashPassword('novalia'), 'admin']);
        // Sample users
        $us->execute(['Budi Santoso', 'budi@mail.com', hashPassword('budi123'), 'user']);
        $us->execute(['Siti Rahayu',  'siti@mail.com', hashPassword('siti123'), 'user']);

        // Sample events
        $ev = $pdo->prepare("INSERT INTO events (judul,deskripsi,kategori,tanggal,waktu_mulai,waktu_selesai,lokasi,kuota,harga,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $ev->execute(['Tech Summit Indonesia 2025',
            'Konferensi teknologi terbesar se-Indonesia. Hadirkan 50+ pembicara dari perusahaan top seperti Google, Microsoft, dan Tokopedia. Networking dengan ribuan profesional IT dari seluruh penjuru nusantara.',
            'Seminar','2025-07-15','08:00','17:00','Jakarta Convention Center, Jakarta',500,0,'Aktif']);
        $ev->execute(['AI & Machine Learning Workshop',
            'Workshop intensif 2 hari belajar Machine Learning dari nol hingga deploy model ke production. Materi mencakup Python, TensorFlow, scikit-learn, dan cloud deployment di AWS.',
            'Workshop','2025-07-20','09:00','16:00','Gedung Innovation Hub, Bandung',60,250000,'Aktif']);
        $ev->execute(['National Hackathon 2025',
            'Kompetisi coding 36 jam dengan total hadiah Rp 150 juta. Tema: solusi teknologi untuk UMKM Indonesia. Terbuka untuk mahasiswa dan profesional muda.',
            'Hackathon','2025-08-05','08:00','20:00','Universitas Indonesia, Depok',300,0,'Aktif']);
        $ev->execute(['Webinar: Cloud Architecture Best Practices',
            'Diskusi mendalam tentang arsitektur cloud modern, microservices, dan DevOps culture bersama praktisi dari perusahaan unicorn Indonesia.',
            'Webinar','2025-06-28','19:00','21:00','Online via Zoom',2000,0,'Aktif']);
        $ev->execute(['UI/UX Design Competition 2025',
            'Kompetisi desain produk digital berhadiah total Rp 50 juta. Kerjakan tantangan desain real dari perusahaan sponsor dalam waktu 48 jam.',
            'Lomba','2025-08-15','08:00','17:00','Bali Nusa Dua Convention',150,75000,'Aktif']);
        $ev->execute(['Cybersecurity Bootcamp',
            'Bootcamp 3 hari untuk pemula dan menengah. Pelajari ethical hacking, penetration testing, dan digital forensics bersama praktisi keamanan siber nasional.',
            'Workshop','2025-09-01','08:00','17:00','Surabaya Tech Center',40,350000,'Aktif']);

        $reg = $pdo->prepare("INSERT OR IGNORE INTO registrasi (user_id,event_id,status) VALUES (?,?,?)");
        $reg->execute([5,1,'Terdaftar']);
        $reg->execute([5,3,'Terdaftar']);
        $reg->execute([6,1,'Terdaftar']);
    }
}

// ── Hash: MD5 + SHA-256 gabungan ─────────────────────────
function hashPassword(string $pass): string {
    return hash('sha256', md5($pass));
}
function verifyPassword(string $input, string $stored): bool {
    return hashPassword($input) === $stored;
}

// ── Upload gambar event ───────────────────────────────────
function uploadGambar(array $file, ?string $oldFile = null): ?string {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // max 5MB

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $dest = UPLOAD_DIR . $name;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        // Hapus gambar lama
        if ($oldFile && file_exists(UPLOAD_DIR . $oldFile)) {
            unlink(UPLOAD_DIR . $oldFile);
        }
        return $name;
    }
    return null;
}

// ── Helpers ───────────────────────────────────────────────
function e($s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function rupiah($n): string {
    return $n == 0 ? 'Gratis' : 'Rp ' . number_format($n, 0, ',', '.');
}
function formatTgl($d): string {
    if (!$d) return '—';
    $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    [$y,$m,$day] = explode('-', $d);
    return "{$day} {$bln[(int)$m]} {$y}";
}
function formatTglLong($d): string {
    if (!$d) return '—';
    $bln = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    [$y,$m,$day] = explode('-', $d);
    return "{$day} {$bln[(int)$m]} {$y}";
}
function kategoriStyle($k): array {
    return [
        'Seminar'   => ['bg'=>'#EBF3FC','color'=>'#1E5FA0','emoji'=>'🎤'],
        'Workshop'  => ['bg'=>'#FFF0DC','color'=>'#C8813A','emoji'=>'🔧'],
        'Hackathon' => ['bg'=>'#FEF0F0','color'=>'#C0383A','emoji'=>'💻'],
        'Webinar'   => ['bg'=>'#E6F4ED','color'=>'#2E7D55','emoji'=>'📡'],
        'Lomba'     => ['bg'=>'#F5F3FF','color'=>'#7C3AED','emoji'=>'🏆'],
    ][$k] ?? ['bg'=>'#F3F4F6','color'=>'#6B7280','emoji'=>'📌'];
}
function getEventImg($gambar, $kategori = ''): string {
    if ($gambar && file_exists(UPLOAD_DIR . $gambar)) {
        return UPLOAD_URL . $gambar;
    }
    // Placeholder SVG data URI per kategori
    $colors = [
        'Seminar'   => ['#1E5FA0','#EBF3FC','🎤'],
        'Workshop'  => ['#C8813A','#FFF0DC','🔧'],
        'Hackathon' => ['#C0383A','#FEF0F0','💻'],
        'Webinar'   => ['#2E7D55','#E6F4ED','📡'],
        'Lomba'     => ['#7C3AED','#F5F3FF','🏆'],
    ];
    return ''; // kosong berarti tampilkan placeholder CSS
}