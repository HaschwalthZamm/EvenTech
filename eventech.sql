-- ============================================================
--  EvenTech Database Schema
--  Platform Manajemen Event IT
-- ============================================================

CREATE DATABASE IF NOT EXISTS `eventech_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `eventech_db`;

-- -------------------------------------------------------
-- Tabel users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,  -- MD5 + SHA-256 hash
  `role`       ENUM('admin','user') NOT NULL DEFAULT 'user',
  `avatar`     VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Tabel events
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `judul`       VARCHAR(200) NOT NULL,
  `deskripsi`   TEXT         NOT NULL,
  `kategori`    ENUM('seminar','workshop','lomba','webinar','conference','bootcamp') NOT NULL DEFAULT 'seminar',
  `tanggal`     DATE         NOT NULL,
  `waktu`       TIME         NOT NULL,
  `lokasi`      VARCHAR(255) NOT NULL,
  `kuota`       INT(11)      NOT NULL DEFAULT 100,
  `harga`       DECIMAL(12,0) NOT NULL DEFAULT 0,
  `thumbnail`   VARCHAR(255) DEFAULT NULL,
  `status`      ENUM('draft','published','closed') NOT NULL DEFAULT 'published',
  `created_by`  INT(11)      NOT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Tabel registrasi peserta
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registrasi` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)   NOT NULL,
  `event_id`   INT(11)   NOT NULL,
  `status`     ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
  `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reg` (`user_id`, `event_id`),
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Seed: Admin default
-- Password: Admin@123  → hash(sha256(md5('Admin@123')))
-- md5  → 3b6e02f5e0ac2aa0ef6d5d3a79af8c26
-- sha256 of that → stored below
-- -------------------------------------------------------
INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@eventech.id',
 SHA2(MD5('Admin@123'), 256),
 'admin');

-- -------------------------------------------------------
-- Seed: Contoh events
-- -------------------------------------------------------
INSERT INTO `events` (`judul`,`deskripsi`,`kategori`,`tanggal`,`waktu`,`lokasi`,`kuota`,`harga`,`status`,`created_by`) VALUES
('AI Summit 2026',
 'Konferensi tahunan membahas perkembangan Artificial Intelligence dan Machine Learning terkini. Hadirkan para pakar industri dari Google, OpenAI, hingga startup lokal Indonesia.',
 'conference','2026-05-15','09:00:00','Jakarta Convention Center',300,150000,'published',1),

('Fullstack Bootcamp: React & Node',
 'Workshop intensif 2 hari membangun aplikasi fullstack modern menggunakan React.js dan Node.js. Cocok untuk developer yang ingin naik level.',
 'bootcamp','2026-05-22','08:00:00','Tech Hub Bandung',50,500000,'published',1),

('Hackathon Nasional 2026',
 'Kompetisi coding 48 jam non-stop. Bangun solusi digital untuk tantangan nyata Indonesia. Total hadiah Rp 150 juta untuk 3 tim terbaik.',
 'lomba','2026-06-07','07:00:00','Universitas Indonesia, Depok',200,0,'published',1),

('Webinar: Cybersecurity di Era Cloud',
 'Pelajari strategi keamanan siber terbaru untuk infrastruktur cloud modern. Narasumber dari BSSN dan perusahaan keamanan global.',
 'webinar','2026-04-25','13:00:00','Online (Zoom)',500,0,'published',1),

('UI/UX Design Sprint',
 'Workshop praktis design thinking dan prototyping menggunakan Figma. Dari wireframe hingga prototype yang siap dipresentasikan ke investor.',
 'workshop','2026-05-10','09:00:00','Creative Space Surabaya',40,350000,'published',1);
