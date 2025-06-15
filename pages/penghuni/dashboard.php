<?php
require_once '../../includes/auth.php';
checkRole(['penghuni']);
require_once '../../config/db.php';

$userId = intval($_SESSION['user']['id'] ?? 0);

// --- Ambil data statistik ---
$izinAktifCount = 0;
$totalKunjungan = 0;

$q1 = $conn->query("SELECT COUNT(*) AS total FROM izin_kunjungan_penghuni WHERE id_user = $userId AND status = 'disetujui' AND tanggal_kunjungan >= CURDATE()");
if ($q1) {
    $izinAktifCount = $q1->fetch_assoc()['total'] ?? 0;
}

$q2 = $conn->query("SELECT COUNT(*) AS total FROM izin_kunjungan_penghuni WHERE id_user = $userId");
if ($q2) {
    $totalKunjungan = $q2->fetch_assoc()['total'] ?? 0;
}

// --- Notifikasi dinamis dari database ---
$notifikasi = [];

$sql = "
    SELECT 
        ikp.status,
        ikp.created_at,
        t.nama AS nama_tamu
    FROM izin_kunjungan_penghuni ikp
    LEFT JOIN tamu t ON ikp.id_tamu = t.id
    WHERE ikp.id_user = $userId
    ORDER BY ikp.created_at DESC
    LIMIT 10
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nama = htmlspecialchars($row['nama_tamu'] ?? 'Tamu Tidak Diketahui');
        $status = $row['status'];
        $createdAt = $row['created_at'];

        switch ($status) {
            case 'disetujui':
                $message = "Izin kunjungan untuk <strong>$nama</strong> telah disetujui.";
                break;
            case 'ditolak':
                $message = "Izin kunjungan Anda untuk <strong>$nama</strong> telah ditolak.";
                break;
            case 'selesai':
                $message = "Kunjungan <strong>$nama</strong> telah selesai.";
                break;
            case 'menunggu':
                $message = "Permintaan kunjungan untuk <strong>$nama</strong> sedang menunggu persetujuan.";
                break;
            default:
                $message = "Status kunjungan <strong>$nama</strong>: $status.";
        }

        $notifikasi[] = [
            'message' => $message,
            'created_at' => $createdAt
        ];
    }
}

$semuaNotifikasi = $notifikasi;

// --- Data statis ---
$pengumuman = [
    "Pengecekan listrik dilakukan pada tanggal 20 Mei 2025.",
    "Peraturan baru penggunaan fasilitas gym mulai berlaku Juli 2025."
];

$panduan = [
    "Login ke sistem dengan username dan password Anda.",
    "Buat izin kunjungan tamu sebelum kedatangan.",
    "Gunakan menu Riwayat Kunjungan untuk melihat status izin."
];

$infoSingkat = [
    "Jam operasional security 24 jam.",
    "Pelayanan front office tersedia dari pukul 08.00 - 20.00.",
    "Hubungi satpam jika ada keadaan darurat."
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Penghuni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #3d7b65;
            min-height: 100vh;
            padding: 20px;
            color: white;
            width: 220px;
        }
        .sidebar a {
            color: #e0f0e6;
            text-decoration: none;
            display: block;
            padding: 10px 0;
        }
        .sidebar a:hover {
            background-color: #2e5e4d;
            border-radius: 6px;
        }
        .logo {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }
        .logo img {
            width: 30px;
            margin-right: 8px;
        }
        .sidebar-toggler {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                transition: left 0.3s ease;
                z-index: 999;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggler {
                display: block;
                margin: 10px;
                background-color: #3d7b65;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }
            .overlay.show {
                display: block;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo"
                 style="height:38px; width:auto; border-radius:6px; background:#fff; padding:3px; margin-right:8px;" />
            Visitor Pass
        </div>
        <div><strong>Penghuni:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="buat_izin.php">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="data_tamu.php">Data Tamu</a>
        <a href="riwayat_kunjungan.php">Riwayat Kunjungan</a>
        <a href="laporan_penghuni.php">Laporan Penghuni</a>
        <a href="blacklist_laporan.php">Blacklist & Masalah</a>
        <a href="pengaturan_akun.php">Pengaturan Akun</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay sidebar untuk mobile -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Konten Utama -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Dashboard Penghuni</h4>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card text-bg-success h-100">
                    <div class="card-body">
                        <h5 class="card-title">Izin Kunjungan Aktif</h5>
                        <p class="card-text display-6"><?= $izinAktifCount ?></p>
                        <p class="card-text"><small>Jumlah izin kunjungan tamu yang masih aktif hari ini.</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card text-bg-primary h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Semua Kunjungan</h5>
                        <p class="card-text display-6"><?= $totalKunjungan ?></p>
                        <p class="card-text"><small>Total seluruh izin kunjungan yang pernah diajukan.</small></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifikasi -->
        <h5>Notifikasi Terbaru</h5>
        <?php if (count($semuaNotifikasi) > 0): ?>
            <ul class="list-group mb-4">
                <?php foreach ($semuaNotifikasi as $notif): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <?= $notif['message'] ?>
                            <div class="small text-muted">
                                <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                            </div>
                        </div>
                        <span class="badge bg-warning">Baru</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-muted mb-4">Tidak ada notifikasi baru.</div>
        <?php endif; ?>

        <!-- Pengumuman -->
        <div class="mb-5">
            <h5>Pengumuman</h5>
            <ul class="list-group">
                <?php foreach ($pengumuman as $p): ?>
                    <li class="list-group-item"><?= htmlspecialchars($p) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Panduan -->
        <div class="mb-5">
            <h5>Panduan Penggunaan</h5>
            <ol class="list-group list-group-numbered">
                <?php foreach ($panduan as $pd): ?>
                    <li class="list-group-item"><?= htmlspecialchars($pd) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- Info Singkat -->
        <div class="mb-5">
            <h5>Info Singkat</h5>
            <ul class="list-group">
                <?php foreach ($infoSingkat as $info): ?>
                    <li class="list-group-item"><?= htmlspecialchars($info) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");
        sidebar.classList.toggle("show");
        overlay.classList.toggle("show");
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>