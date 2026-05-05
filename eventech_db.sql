-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Bulan Mei 2026 pada 15.48
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eventech_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `kategori` enum('seminar','workshop','lomba','webinar','conference','bootcamp') NOT NULL DEFAULT 'seminar',
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `kuota` int(11) NOT NULL DEFAULT 100,
  `harga` decimal(12,0) NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'published',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `events`
--

INSERT INTO `events` (`id`, `judul`, `deskripsi`, `kategori`, `tanggal`, `waktu`, `lokasi`, `kuota`, `harga`, `gambar`, `thumbnail`, `status`, `created_by`, `created_at`) VALUES
(1, 'AI Summit 2026', 'Konferensi tahunan membahas perkembangan Artificial Intelligence dan Machine Learning terkini. Hadirkan para pakar industri dari Google, OpenAI, hingga startup lokal Indonesia.', 'conference', '2026-05-15', '09:00:00', 'Jakarta Convention Center', 300, 150000, NULL, NULL, 'published', 1, '2026-04-12 10:47:05'),
(2, 'Fullstack Bootcamp: React & Node', 'Workshop intensif 2 hari membangun aplikasi fullstack modern menggunakan React.js dan Node.js. Cocok untuk developer yang ingin naik level.', 'bootcamp', '2026-05-22', '08:00:00', 'Tech Hub Bandung', 50, 500000, NULL, NULL, 'published', 1, '2026-04-12 10:47:05'),
(3, 'Hackathon Nasional 2026', 'Kompetisi coding 48 jam non-stop. Bangun solusi digital untuk tantangan nyata Indonesia. Total hadiah Rp 150 juta untuk 3 tim terbaik.', 'lomba', '2026-06-07', '07:00:00', 'Universitas Indonesia, Depok', 200, 0, 'https://i.pinimg.com/1200x/e5/cb/f3/e5cbf32a5a443bd5ee898e8f03b8e94c.jpg', NULL, 'published', 1, '2026-04-12 10:47:05'),
(5, 'UXplore 2026', 'Workshop praktis design thinking dan prototyping menggunakan Figma. Dari wireframe hingga prototype yang siap dipresentasikan ke investor.', 'webinar', '2026-05-10', '09:00:00', 'Zoom Meeting', 150, 0, NULL, NULL, 'published', 1, '2026-04-12 10:47:05'),
(6, 'Bem Visit HIMDIKO', 'Kegiatan BEM Fasilkom Unsri Bersua bersama Himpunan Mahasiswa Diploma Komputer dalam mempererat persaudaraan internal fasilkom', 'conference', '2026-04-28', '13:00:00', 'Social Market(SOMA)', 150, 99999, NULL, NULL, 'published', 1, '2026-04-27 02:42:37'),
(7, 'Design Class', 'bootcamp tentang design yang di selenggarakan oleh fkph fh ub', 'bootcamp', '2026-09-22', '14:30:00', 'Online Via Zoom Meeting', 150, 0, 'https://i.pinimg.com/736x/45/92/cb/4592cb92e517b63f8daadfe9397625a5.jpg', NULL, 'published', 6, '2026-05-05 11:58:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `registrasi`
--

CREATE TABLE `registrasi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `registrasi`
--

INSERT INTO `registrasi` (`id`, `user_id`, `event_id`, `status`, `registered_at`) VALUES
(4, NULL, 2, 'confirmed', '2026-04-27 02:14:02'),
(5, NULL, 5, 'confirmed', '2026-04-27 02:29:49'),
(8, NULL, 6, 'confirmed', '2026-04-27 03:50:18'),
(9, NULL, 1, 'confirmed', '2026-04-27 03:50:35'),
(13, 9, 6, 'confirmed', '2026-04-28 07:28:35'),
(14, 9, 3, 'confirmed', '2026-04-28 07:28:49'),
(15, 10, 2, 'confirmed', '2026-04-28 08:12:56'),
(16, 10, 6, 'confirmed', '2026-04-28 08:42:46'),
(17, 11, 6, 'confirmed', '2026-04-28 08:56:52'),
(18, 10, 5, 'confirmed', '2026-05-04 03:20:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `avatar`, `created_at`) VALUES
(1, 'Administrator', 'admin@eventech.id', '74590d9966619b05d36b77f5001b99040a3bc66f48966837ffc44dff83f23618', 'admin', NULL, '2026-04-12 10:47:05'),
(6, 'Zamali', 'zam@eventech.id', '7072a104a6c564f1475c67fd25489d8f7430f785f5f7c62b7c20ac1354c80db3', 'admin', NULL, '2026-04-28 07:25:43'),
(7, 'Opick', 'oprl@eventech.id', 'edca24468531c361c8dc24e2da8a5f474cc9b6ed425a82e05a4de18e6b22d694', 'admin', NULL, '2026-04-28 07:25:43'),
(8, 'Novalia', 'nov@eventech.id', 'f4e70abe6e6a1c6b9799c2bc22caef1d1f9cc6bc03c971f1f598fa8da8050f38', 'admin', NULL, '2026-04-28 07:25:43'),
(9, 'novalia', 'novaliafitriani@gmail.com', 'cdf4a007e2b02a0c49fc9b7ccfbb8a10c644f635e1765dcf2a7ab794ddc7edac', 'user', NULL, '2026-04-28 07:28:08'),
(10, 'Irsyad Zamali', 'm.irsyadzamali@gmail.com', '7072a104a6c564f1475c67fd25489d8f7430f785f5f7c62b7c20ac1354c80db3', 'user', NULL, '2026-04-28 08:02:23'),
(11, 'Muhamad Irsyad Zamali', 'zammagain@gmail.com', '7072a104a6c564f1475c67fd25489d8f7430f785f5f7c62b7c20ac1354c80db3', 'user', NULL, '2026-04-28 08:04:49');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `registrasi`
--
ALTER TABLE `registrasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reg` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `registrasi`
--
ALTER TABLE `registrasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `registrasi`
--
ALTER TABLE `registrasi`
  ADD CONSTRAINT `registrasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrasi_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
