-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `created_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'Admin Apartemen', 'admin@apartemen.com', '081234567890', '2025-05-26 15:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `blacklist`
--

CREATE TABLE `blacklist` (
  `id` int(11) NOT NULL,
  `id_tamu` int(11) DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `tamu_type` enum('tamu','engineering','kilat') NOT NULL,
  `id_satpam` int(11) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blacklist`
--

INSERT INTO `blacklist` (`id`, `id_tamu`, `alasan`, `tanggal`, `tamu_type`, `id_satpam`, `id_admin`) VALUES
(2, 14, 'tidak membayar ipl', '2025-06-18 22:08:14', 'tamu', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `catatan_shift`
--

CREATE TABLE `catatan_shift` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `shift` enum('Pagi','Siang','Malam') NOT NULL,
  `catatan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catatan_shift`
--

INSERT INTO `catatan_shift` (`id`, `id_user`, `tanggal`, `shift`, `catatan`, `created_at`) VALUES
(3, 2, '2025-06-16', 'Malam', 'cctv cek', '2025-06-16 05:20:41');

-- --------------------------------------------------------

--
-- Table structure for table `engineering_users`
--

CREATE TABLE `engineering_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `engineering_users`
--

INSERT INTO `engineering_users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `unit`, `created_at`) VALUES
(1, 'engineer01', '471272c1e0ca0f444f6b140024466943', 'Anton Teknik', 'anton@apartemen.com', '081234567893', 'Service Room', '2025-05-26 15:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `fo_users`
--

CREATE TABLE `fo_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fo_users`
--

INSERT INTO `fo_users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `created_at`) VALUES
(1, 'resepsionis1', '3eccd57c1155c5d3d6edc969d54a34bf', 'FO Satu', 'fo1@apartemen.com', '081234567891', '2025-05-26 15:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `izin_kunjungan_engineering`
--

CREATE TABLE `izin_kunjungan_engineering` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_tamu` int(11) DEFAULT NULL,
  `tujuan` text DEFAULT NULL,
  `jenis_izin` varchar(50) DEFAULT NULL,
  `tanggal_kunjungan` date DEFAULT NULL,
  `jam_kunjungan` time DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak','selesai') DEFAULT 'menunggu',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `izin_kunjungan_engineering`
--

INSERT INTO `izin_kunjungan_engineering` (`id`, `id_user`, `id_tamu`, `tujuan`, `jenis_izin`, `tanggal_kunjungan`, `jam_kunjungan`, `keperluan`, `status`, `created_at`) VALUES
(2, NULL, 12, 'engineering', NULL, '2025-06-05', '12:00:00', 'teknisi ac', 'selesai', '2025-06-01 18:06:53'),
(7, NULL, 14, 'engineering', NULL, '2025-06-12', '10:00:00', 'cek gondola', 'menunggu', '2025-06-11 13:28:39');

-- --------------------------------------------------------

--
-- Table structure for table `izin_kunjungan_penghuni`
--

CREATE TABLE `izin_kunjungan_penghuni` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_tamu` int(11) DEFAULT NULL,
  `tujuan` text DEFAULT NULL,
  `tanggal_kunjungan` date DEFAULT NULL,
  `jam_kunjungan` time DEFAULT NULL,
  `jenis_izin` varchar(50) DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak','selesai') DEFAULT 'menunggu',
  `created_at` datetime DEFAULT current_timestamp(),
  `keperluan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `izin_kunjungan_penghuni`
--

INSERT INTO `izin_kunjungan_penghuni` (`id`, `id_user`, `id_tamu`, `tujuan`, `tanggal_kunjungan`, `jam_kunjungan`, `jenis_izin`, `status`, `created_at`, `keperluan`) VALUES
(11, NULL, 23, 'Tower Tanjung 1111', '2025-06-12', '12:45:00', 'penghuni', 'menunggu', '2025-06-11 16:46:55', 'Antar makanan dan perlengkapan untuk adik kos');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_engineering`
--

CREATE TABLE `jadwal_engineering` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `aktivitas` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_engineering`
--

INSERT INTO `jadwal_engineering` (`id`, `id_user`, `tanggal`, `jam_mulai`, `jam_selesai`, `aktivitas`, `created_at`) VALUES
(1, 1, '2025-05-15', '13:00:00', '15:00:00', 'Perbaikan AC lantai 3', '2025-05-26 15:29:21');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_hari_ini`
--

CREATE TABLE `jadwal_hari_ini` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `unit` varchar(10) NOT NULL,
  `jam` time NOT NULL,
  `tujuan` varchar(100) NOT NULL,
  `status` enum('Belum','Sedang Dikerjakan','Selesai') DEFAULT 'Belum',
  `progres` int(11) DEFAULT 0,
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_hari_ini`
--

INSERT INTO `jadwal_hari_ini` (`id`, `nama`, `unit`, `jam`, `tujuan`, `status`, `progres`, `id_user`) VALUES
(1, 'Anindya', '1203', '08:00:00', 'Perbaikan', 'Selesai', 100, NULL),
(2, 'Rizky', '1102', '09:30:00', 'Pengecekan Listrik', 'Sedang Dikerjakan', 50, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kunjungan`
--

CREATE TABLE `kunjungan` (
  `id` int(11) NOT NULL,
  `id_izin` int(11) DEFAULT NULL,
  `waktu_masuk` datetime DEFAULT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `foto_masuk` varchar(255) DEFAULT NULL,
  `foto_keluar` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `jenis_izin` enum('penghuni','engineering','kilat') DEFAULT NULL,
  `status` enum('masuk','keluar','pending') DEFAULT 'pending',
  `deleted_at` datetime DEFAULT NULL,
  `tanggal` date GENERATED ALWAYS AS (cast(`waktu_masuk` as date)) STORED,
  `id_izin_penghuni` int(11) DEFAULT NULL,
  `id_izin_engineering` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kunjungan`
--

INSERT INTO `kunjungan` (`id`, `id_izin`, `waktu_masuk`, `waktu_keluar`, `foto_masuk`, `foto_keluar`, `qr_code`, `jenis_izin`, `status`, `deleted_at`, `id_izin_penghuni`, `id_izin_engineering`) VALUES
(2, 2, '2025-06-05 12:10:00', '2025-06-05 15:30:00', 'foto_masuk_2.jpg', 'foto_keluar_2.jpg', 'QR002', 'engineering', 'pending', NULL, NULL, NULL),
(3, 7, '2025-06-02 10:05:00', NULL, 'foto_masuk_3.jpg', NULL, 'QR003', 'penghuni', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `laporan_masalah`
--

CREATE TABLE `laporan_masalah` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `teknisi` varchar(100) NOT NULL,
  `laporan` text NOT NULL,
  `waktu` time NOT NULL,
  `status` varchar(50) DEFAULT 'baru',
  `progres` int(11) DEFAULT 0,
  `tanggal_tindak_lanjut` date DEFAULT NULL,
  `id_engineer` int(11) DEFAULT NULL,
  `id_penghuni` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_masalah`
--

INSERT INTO `laporan_masalah` (`id`, `tanggal`, `teknisi`, `laporan`, `waktu`, `status`, `progres`, `tanggal_tindak_lanjut`, `id_engineer`, `id_penghuni`) VALUES
(1, '2025-05-23', 'Budi', 'AC Bocor', '10:00:00', 'baru', 0, NULL, NULL, NULL),
(2, '2025-05-24', 'Andi', 'Lampu Mati di Kamar Mandi', '14:00:00', 'baru', 0, NULL, NULL, NULL),
(3, '2025-06-06', '', 'kebocoran sink di unit 203', '17:30:00', 'baru', 0, '2025-06-09', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `laporan_teknisi`
--

CREATE TABLE `laporan_teknisi` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `isi_laporan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `waktu` time DEFAULT NULL,
  `tindak_lanjut` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_teknisi`
--

INSERT INTO `laporan_teknisi` (`id`, `id_user`, `tanggal`, `isi_laporan`, `foto`, `created_at`, `waktu`, `tindak_lanjut`) VALUES
(1, 1, '2025-05-15', 'AC unit lantai 3 diperbaiki dan diuji coba.', 'laporan_ac_3.jpg', '2025-05-26 15:29:21', '08:00:00', NULL),
(2, 1, '2025-06-01', 'perbaiki ac lantai 11', NULL, '2025-06-01 18:41:14', '10:30:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp(),
  `id_template` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `id_user`, `message`, `status`, `created_at`, `id_template`) VALUES
(1, 1, 'Tamu Anda Doni Saputra sudah masuk.', 'unread', '2025-05-26 15:29:21', NULL),
(2, 1, 'Jadwal periksa AC hari ini jam 14:00.', 'unread', '2025-05-26 15:29:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_template`
--

CREATE TABLE `notifikasi_template` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `status` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi_template`
--

INSERT INTO `notifikasi_template` (`id`, `nama`, `isi`, `status`, `created_at`) VALUES
(1, 'Notifikasi Tamu Datang', 'Tamu {nama_tamu} telah tiba di resepsionis.', 'Aktif', '2025-06-02 13:19:05'),
(2, 'Notifikasi Kunjungan Disetujui', 'Kunjungan {nama_tamu} disetujui oleh penghuni.', 'Aktif', '2025-06-02 13:19:05'),
(3, 'Notifikasi Kunjungan Ditolak', 'Kunjungan {nama_tamu} ditolak oleh penghuni.', 'Nonaktif', '2025-06-02 13:19:05'),
(4, 'Notifikasi Maintenance', 'Jadwal maintenance pada tanggal {tanggal} di unit {unit}.', 'Aktif', '2025-06-02 13:19:05'),
(5, 'Notifikasi Paket Datang', 'Paket untuk {nama_penghuni} telah tiba di resepsionis.', 'Aktif', '2025-06-02 13:19:05');

-- --------------------------------------------------------

--
-- Table structure for table `panic_logs`
--

CREATE TABLE `panic_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panic_logs`
--

INSERT INTO `panic_logs` (`id`, `user_id`, `time`, `status`, `description`, `lokasi`) VALUES
(3, 1, '2025-06-16 06:46:42', 'pending', NULL, 'Lantai 3');

-- --------------------------------------------------------

--
-- Table structure for table `penghuni_users`
--

CREATE TABLE `penghuni_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `blok` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penghuni_users`
--

INSERT INTO `penghuni_users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `unit`, `created_at`, `blok`) VALUES
(1, 'penghuni01', '319078908845aa06103676f05e7a010c', 'Budi Santoso', 'budi@apartemen.com', '081234567892', 'A-101', '2025-05-26 15:29:20', NULL),
(5, 'anindya16', '25bfae1f2750a18df73e5d65b7ccb2a1', 'Anindya Bulan Shintania', 'bulan@menteng.com', '087803415729', 'Tower Rasamala', '2025-06-13 15:59:19', '1607');

-- --------------------------------------------------------

--
-- Table structure for table `satpam_users`
--

CREATE TABLE `satpam_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `shift` varchar(20) DEFAULT 'Pagi',
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `satpam_users`
--

INSERT INTO `satpam_users` (`id`, `username`, `password`, `nama_lengkap`, `shift`, `email`, `no_hp`, `created_at`) VALUES
(1, 'satpamnew', 'c6ed74388c6bb6a0a993aea3884c57cd', 'Rio Aditya Pratama', 'Malam', 'rio@mentengbaru.com', '0812-7755-3344', '2025-06-02 11:15:56'),
(2, 'satpam01', 'ff16aca7572a4789a591e52b545e47c1', 'Andi Satpam', 'Pagi', 'andi@satpam.com', '081234567894', '2025-05-26 15:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `tamu`
--

CREATE TABLE `tamu` (
  `id` int(11) NOT NULL,
  `nama_tamu` varchar(255) NOT NULL,
  `no_identitas` varchar(50) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `jenis_kelamin` char(1) NOT NULL,
  `tujuan` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `foto_ktp` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `waktu_checkin` datetime DEFAULT current_timestamp(),
  `id_penghuni` int(11) DEFAULT NULL,
  `izin_penghuni` enum('Naik ke Unit','Tetap di Lobby') DEFAULT 'Tetap di Lobby'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tamu`
--

INSERT INTO `tamu` (`id`, `nama_tamu`, `no_identitas`, `no_hp`, `jenis_kelamin`, `tujuan`, `alamat`, `status`, `foto_ktp`, `created_at`, `waktu_checkin`, `id_penghuni`, `izin_penghuni`) VALUES
(12, 'pt mandala teknologi utama', '3275091208970003', '080808080808', 'L', 'Engineering', 'ciputat', 'checkedout', NULL, '2025-06-01 04:06:53', '2025-06-01 18:06:53', NULL, 'Tetap di Lobby'),
(13, 'Arief Nur Ramadhan', '3273032209000005', '+628207877612', 'L', 'cendana 12', 'Cibubur', 'pending', 'ktp_6847ce274c262.jpeg', '2025-06-10 06:18:15', '2025-06-10 13:18:15', NULL, 'Tetap di Lobby'),
(14, 'Kevin Maulana', '3275031205960011', '0813-2299-8833', 'L', 'engineering', 'Cilandak', 'Menunggu di Lobby', NULL, '2025-06-11 06:28:39', '2025-06-11 13:28:39', NULL, ''),
(23, 'Nur Azizah', '3273031602030012', '+628207877612', 'P', 'Tower Tanjung 1111', 'Cibinong', 'checkedin', 'ktp_6849508f77f23.jpeg', '2025-06-11 09:46:55', '2025-06-11 16:46:55', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `tamu_kilat`
--

CREATE TABLE `tamu_kilat` (
  `id` int(11) NOT NULL,
  `nama_pengantar` varchar(100) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `layanan` varchar(50) DEFAULT NULL,
  `tujuan_unit` varchar(20) DEFAULT NULL,
  `waktu_masuk` datetime DEFAULT current_timestamp(),
  `foto_ktp` varchar(255) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak','selesai') DEFAULT 'menunggu',
  `id_satpam` int(11) DEFAULT NULL,
  `id_fo` int(11) DEFAULT NULL,
  `id_tamu` int(11) DEFAULT NULL,
  `izin_penghuni` enum('Naik ke Unit','Tetap di Lobby') DEFAULT 'Tetap di Lobby'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tamu_kilat`
--

INSERT INTO `tamu_kilat` (`id`, `nama_pengantar`, `nik`, `layanan`, `tujuan_unit`, `waktu_masuk`, `foto_ktp`, `qr_code_data`, `status`, `id_satpam`, `id_fo`, `id_tamu`, `izin_penghuni`) VALUES
(1, 'DANURENDRA TEN AGASTHA', '1234567891011121', 'Gojek', 'tower palem 1507', '2025-06-01 14:10:40', 'ktp_683bfcf021141.jpeg', NULL, 'menunggu', NULL, NULL, NULL, 'Tetap di Lobby');

-- --------------------------------------------------------

--
-- Table structure for table `tamu_users`
--

CREATE TABLE `tamu_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `nama_penghuni` varchar(100) DEFAULT NULL,
  `tujuan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tamu_users`
--

INSERT INTO `tamu_users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `created_at`, `nama_penghuni`, `tujuan`) VALUES
(1, 'tamu2', '26364ac030f3791242f334bc3749fe36', 'Lisa Andriani', 'lisa.tamu@example.com', '089812345678', '2025-05-26 15:29:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('admin','fo','penghuni','engineering','satpam','tamu') NOT NULL,
  `id_role_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `id_role_user`) VALUES
(1, 'admin', 1),
(2, 'fo', 1),
(3, 'penghuni', 1),
(4, 'engineering', 1),
(5, 'satpam', 1),
(6, 'tamu', 1),
(8, 'penghuni', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `blacklist`
--
ALTER TABLE `blacklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_blacklist_id_satpam` (`id_satpam`),
  ADD KEY `fk_blacklist_id_admin` (`id_admin`);

--
-- Indexes for table `catatan_shift`
--
ALTER TABLE `catatan_shift`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `engineering_users`
--
ALTER TABLE `engineering_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `fo_users`
--
ALTER TABLE `fo_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `izin_kunjungan_engineering`
--
ALTER TABLE `izin_kunjungan_engineering`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_izin_kunjungan_engineering_user` (`id_user`),
  ADD KEY `fk_izin_engineering_tamu` (`id_tamu`);

--
-- Indexes for table `izin_kunjungan_penghuni`
--
ALTER TABLE `izin_kunjungan_penghuni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_izin_kunjungan_penghuni_user` (`id_user`),
  ADD KEY `fk_izin_penghuni_tamu` (`id_tamu`);

--
-- Indexes for table `jadwal_engineering`
--
ALTER TABLE `jadwal_engineering`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `jadwal_hari_ini`
--
ALTER TABLE `jadwal_hari_ini`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jadwal_hari_ini_id_user` (`id_user`);

--
-- Indexes for table `kunjungan`
--
ALTER TABLE `kunjungan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_izin` (`id_izin`),
  ADD KEY `fk_kunjungan_penghuni` (`id_izin_penghuni`),
  ADD KEY `fk_kunjungan_engineering` (`id_izin_engineering`);

--
-- Indexes for table `laporan_masalah`
--
ALTER TABLE `laporan_masalah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_laporan_masalah_engineer` (`id_engineer`),
  ADD KEY `fk_laporan_masalah_penghuni` (`id_penghuni`);

--
-- Indexes for table `laporan_teknisi`
--
ALTER TABLE `laporan_teknisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `fk_notifikasi_template` (`id_template`);

--
-- Indexes for table `notifikasi_template`
--
ALTER TABLE `notifikasi_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `panic_logs`
--
ALTER TABLE `panic_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penghuni_users`
--
ALTER TABLE `penghuni_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `satpam_users`
--
ALTER TABLE `satpam_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tamu`
--
ALTER TABLE `tamu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tamu_kilat`
--
ALTER TABLE `tamu_kilat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tamu_kilat_satpam` (`id_satpam`),
  ADD KEY `fk_tamu_kilat_fo` (`id_fo`),
  ADD KEY `fk_tamu_kilat_tamu` (`id_tamu`);

--
-- Indexes for table `tamu_users`
--
ALTER TABLE `tamu_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blacklist`
--
ALTER TABLE `blacklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `catatan_shift`
--
ALTER TABLE `catatan_shift`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `engineering_users`
--
ALTER TABLE `engineering_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fo_users`
--
ALTER TABLE `fo_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `izin_kunjungan_engineering`
--
ALTER TABLE `izin_kunjungan_engineering`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `izin_kunjungan_penghuni`
--
ALTER TABLE `izin_kunjungan_penghuni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `jadwal_hari_ini`
--
ALTER TABLE `jadwal_hari_ini`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kunjungan`
--
ALTER TABLE `kunjungan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `laporan_masalah`
--
ALTER TABLE `laporan_masalah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `panic_logs`
--
ALTER TABLE `panic_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `penghuni_users`
--
ALTER TABLE `penghuni_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `satpam_users`
--
ALTER TABLE `satpam_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tamu`
--
ALTER TABLE `tamu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tamu_kilat`
--
ALTER TABLE `tamu_kilat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tamu_users`
--
ALTER TABLE `tamu_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `fk_admin_user_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blacklist`
--
ALTER TABLE `blacklist`
  ADD CONSTRAINT `fk_blacklist_id_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blacklist_id_satpam` FOREIGN KEY (`id_satpam`) REFERENCES `satpam_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `catatan_shift`
--
ALTER TABLE `catatan_shift`
  ADD CONSTRAINT `fk_catatan_shift_satpam` FOREIGN KEY (`id_user`) REFERENCES `satpam_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `engineering_users`
--
ALTER TABLE `engineering_users`
  ADD CONSTRAINT `fk_engineering_user_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fo_users`
--
ALTER TABLE `fo_users`
  ADD CONSTRAINT `fk_fo_user_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `izin_kunjungan_engineering`
--
ALTER TABLE `izin_kunjungan_engineering`
  ADD CONSTRAINT `fk_izin_engineering_tamu` FOREIGN KEY (`id_tamu`) REFERENCES `tamu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_izin_kunjungan_engineer` FOREIGN KEY (`id_user`) REFERENCES `engineering_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_izin_kunjungan_engineering_user` FOREIGN KEY (`id_user`) REFERENCES `engineering_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `izin_kunjungan_penghuni`
--
ALTER TABLE `izin_kunjungan_penghuni`
  ADD CONSTRAINT `fk_izin_kunjungan_penghuni_user` FOREIGN KEY (`id_user`) REFERENCES `penghuni_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_izin_penghuni_tamu` FOREIGN KEY (`id_tamu`) REFERENCES `tamu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `jadwal_engineering`
--
ALTER TABLE `jadwal_engineering`
  ADD CONSTRAINT `fk_jadwal_engineering_id_user` FOREIGN KEY (`id_user`) REFERENCES `engineering_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `jadwal_hari_ini`
--
ALTER TABLE `jadwal_hari_ini`
  ADD CONSTRAINT `fk_jadwal_hari_ini_id_user` FOREIGN KEY (`id_user`) REFERENCES `engineering_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `kunjungan`
--
ALTER TABLE `kunjungan`
  ADD CONSTRAINT `fk_kunjungan_engineering` FOREIGN KEY (`id_izin_engineering`) REFERENCES `izin_kunjungan_engineering` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kunjungan_penghuni` FOREIGN KEY (`id_izin_penghuni`) REFERENCES `izin_kunjungan_penghuni` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `laporan_masalah`
--
ALTER TABLE `laporan_masalah`
  ADD CONSTRAINT `fk_laporan_masalah_engineer` FOREIGN KEY (`id_engineer`) REFERENCES `engineering_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_laporan_masalah_penghuni` FOREIGN KEY (`id_penghuni`) REFERENCES `penghuni_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `laporan_teknisi`
--
ALTER TABLE `laporan_teknisi`
  ADD CONSTRAINT `fk_laporan_teknisi_engineer` FOREIGN KEY (`id_user`) REFERENCES `engineering_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notifikasi_admin` FOREIGN KEY (`id_user`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifikasi_template` FOREIGN KEY (`id_template`) REFERENCES `notifikasi_template` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `penghuni_users`
--
ALTER TABLE `penghuni_users`
  ADD CONSTRAINT `fk_penghuni_user_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `satpam_users`
--
ALTER TABLE `satpam_users`
  ADD CONSTRAINT `fk_satpam_user_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tamu_kilat`
--
ALTER TABLE `tamu_kilat`
  ADD CONSTRAINT `fk_tamu_kilat_fo` FOREIGN KEY (`id_fo`) REFERENCES `fo_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tamu_kilat_satpam` FOREIGN KEY (`id_satpam`) REFERENCES `satpam_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tamu_kilat_tamu` FOREIGN KEY (`id_tamu`) REFERENCES `tamu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tamu_users`
--
ALTER TABLE `tamu_users`
  ADD CONSTRAINT `fk_tamu_user_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
