<?php
require_once '../../includes/auth.php';
checkRole(['penghuni']);
require_once '../../config/db.php';

$userId = $_SESSION['id_user'] ?? 0;

// Revisi: Ambil kolom yang benar dari database
$historyQuery = "
    SELECT 
        ikp.id AS izin_id,
        t.nama_tamu,
        ikp.tujuan,
        ikp.tanggal_kunjungan,
        ikp.jam_kunjungan,
        ikp.status
    FROM izin_kunjungan_penghuni ikp
    LEFT JOIN tamu t ON ikp.id_tamu = t.id
    WHERE ikp.id_user = ?
    ORDER BY ikp.tanggal_kunjungan DESC, ikp.jam_kunjungan DESC
";

$stmt = $conn->prepare($historyQuery);
if (!$stmt) {
    die("Prepare failed: " . $conn->error); // 👈 Debug bantuan jika query salah
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$historyResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Riwayat Kunjungan - Penghuni</title>
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
                width: 220px;
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
                background: rgba(0,0,0,0.5);
                z-index: 998;
            }
            .overlay.show {
                display: block;
            }
        }
        table.table-bordered {
            background: white;
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

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <main class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

        <h4 class="mb-4">Riwayat Kunjungan Penghuni Unit</h4>

        <h5>Daftar Riwayat Kunjungan</h5>
        <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-success">
                <tr>
                    <th>ID Izin</th>
                    <th>Nama Tamu</th>
                    <th>Tujuan</th>
                    <th>Tanggal & Jam Kunjungan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($historyResult && $historyResult->num_rows > 0): ?>
                    <?php while ($row = $historyResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['izin_id']) ?></td>
                            <td><?= htmlspecialchars($row['nama_tamu']) ?></td>
                            <td><?= htmlspecialchars($row['tujuan']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal_kunjungan']))) ?> <?= htmlspecialchars(substr($row['jam_kunjungan'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Belum ada riwayat kunjungan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </main>
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