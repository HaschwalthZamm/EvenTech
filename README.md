# ⚡ EvenTech — Platform Manajemen Event IT

Platform manajemen event IT berbasis PHP Native + MySQL, dengan desain modern dark-theme terinspirasi dari estetika tech startup.

---

## 🗂 Struktur Folder

```
eventech/
├── index.php              → Halaman Login/Register
├── auth.php               → Logic autentikasi
├── logout.php             → Hapus sesi & redirect
├── dashboard_admin.php    → Dashboard Admin (CRUD event, kelola user)
├── event.php              → Daftar Event untuk User
├── detail_event.php       → Detail Event & Pendaftaran
├── koneksi.php            → Koneksi database + helper functions
├── eventech.sql           → Schema + seed data database
└── includes/
    └── guard.php          → Middleware autentikasi & role guard
```

---

## 🚀 Cara Menjalankan di XAMPP

### 1. Install XAMPP
Download XAMPP dari https://apachefriends.org dan install.

### 2. Copy Project
```
Salin folder `eventech/` ke:
C:\xampp\htdocs\eventech\         (Windows)
/Applications/XAMPP/htdocs/eventech/  (macOS)
/opt/lampp/htdocs/eventech/       (Linux)
```

### 3. Import Database
1. Buka XAMPP Control Panel → Start **Apache** dan **MySQL**
2. Buka browser → http://localhost/phpmyadmin
3. Klik **"New"** → buat database `eventech_db`
4. Klik tab **"Import"** → pilih file `eventech.sql`
5. Klik **"Go"**

### 4. (Opsional) Sesuaikan Konfigurasi
Edit `koneksi.php` jika password MySQL kamu bukan kosong:
```php
define('DB_PASS', 'password_mysql_kamu');
```

### 5. Akses Aplikasi
Buka browser dan kunjungi:
```
http://localhost/eventech/
```

---

## 🔑 Akun Default

| Role  | Email               | Password    |
|-------|---------------------|-------------|
| Admin | admin@eventech.id   | Admin@123   |

Untuk akun user, silakan daftar melalui halaman register.

---

## 🛡 Keamanan Password

Password di-hash menggunakan kombinasi **MD5 + SHA-256**:
```
password → MD5(password) → SHA256(hasil_md5)
```

Implementasi di `koneksi.php`:
```php
function hashPassword(string $password): string {
    return hash('sha256', md5($password));
}
```

---

## 🎯 Fitur Lengkap

### Admin
- ✅ Dashboard statistik (total event, user, registrasi)
- ✅ CRUD Event (tambah, edit, hapus) via modal
- ✅ Lihat daftar peserta per event
- ✅ Hapus user

### User
- ✅ Daftar & login dengan validasi
- ✅ Browse semua event dengan filter kategori & search
- ✅ Lihat detail event
- ✅ Daftar event (dengan cek kuota & duplikasi)
- ✅ Indikator status pendaftaran

---

## 🗄 Schema Database

### Tabel `users`
| Kolom      | Tipe         | Keterangan              |
|------------|--------------|-------------------------|
| id         | INT PK       | Auto increment          |
| nama       | VARCHAR(100) | Nama lengkap            |
| email      | VARCHAR(150) | Unique, untuk login     |
| password   | VARCHAR(255) | Hash MD5+SHA256         |
| role       | ENUM         | 'admin' / 'user'        |
| created_at | TIMESTAMP    | Waktu registrasi        |

### Tabel `events`
| Kolom      | Tipe         | Keterangan              |
|------------|--------------|-------------------------|
| id         | INT PK       | Auto increment          |
| judul      | VARCHAR(200) | Nama event              |
| deskripsi  | TEXT         | Deskripsi lengkap       |
| kategori   | ENUM         | seminar/workshop/dll    |
| tanggal    | DATE         | Tanggal pelaksanaan     |
| waktu      | TIME         | Jam mulai               |
| lokasi     | VARCHAR(255) | Tempat/platform         |
| kuota      | INT          | Kapasitas peserta       |
| harga      | DECIMAL      | 0 = Gratis              |
| status     | ENUM         | published/draft/closed  |
| created_by | INT FK       | Referensi ke users      |

### Tabel `registrasi`
| Kolom         | Tipe      | Keterangan          |
|---------------|-----------|---------------------|
| id            | INT PK    | Auto increment      |
| user_id       | INT FK    | Referensi ke users  |
| event_id      | INT FK    | Referensi ke events |
| status        | ENUM      | confirmed/cancelled |
| registered_at | TIMESTAMP | Waktu daftar        |

---

## 🎨 Design System

- **Font**: Syne (heading) + DM Sans (body)
- **Warna Utama**: Gold (#C8963E) pada dark background (#0D0F14)
- **Accent**: Teal (#2DD4BF), Purple (#A78BFA), Amber (#F0A500)
- **Komponen**: Card dengan shadow, modal animasi, sidebar navigation

---

*Dibuat dengan PHP Native + MySQL. No framework, pure code.*
