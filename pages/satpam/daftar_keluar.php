<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

// Ambil data tamu yang sudah keluar
$result = $conn->query("
    SELECT t.nama_tamu, i.tujuan, k.waktu_keluar
    FROM kunjungan k
    JOIN izin_kunjungan_engineering i ON k.id_izin = i.id
    JOIN tamu t ON i.id_tamu = t.id
    WHERE k.waktu_keluar IS NOT NULL
    ORDER BY k.waktu_keluar DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Tamu Keluar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            background-color: #2e7d32;
            min-height: 100vh;
            padding: 20px;
            color: white;
            width: 220px;
        }

        .sidebar a {
            color: #c8e6c9;
            text-decoration: none;
            display: block;
            padding: 10px 0;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: #1b5e20;
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
            border-radius: 6px;
            background: #fff;
            padding: 3px;
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
                background-color: #2e7d32;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
                font-weight: bold;
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
                <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
                Visitor Pass
            </div>
            <div><strong>Satpam:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
            <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="scan.php">Scan QR</a>
        <a href="validasi_manual.php">Validasi Manual</a>
        <a href="form_kurir.php">Check-In Kurir</a>
        <a href="daftar_masuk.php">Daftar Masuk</a>
        <a href="daftar_keluar.php">Daftar Keluar</a>
        <a href="tamu_di_lokasi.php">Tamu di Lokasi</a>
        <a href="laporan_harian.php">Laporan Harian</a>
        <a href="riwayat_scan.php">Riwayat Scan</a>
        <a href="catatan_shift.php">Catatan Shift</a>
        <a href="daftar_blacklist.php">Daftar Blacklist</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
        </div>

        <!-- Overlay untuk sidebar mobile -->
        <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

        <!-- Konten utama -->
        <div class="flex-grow-1 p-4">
            <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

            <h4 class="mb-4">Daftar Tamu Keluar</h4>

            <table class="table table-bordered table-hover">
                <thead class="table-success">
                    <tr>
                        <th>Nama Tamu</th>
                        <th>Tujuan</th>
                        <th>Waktu Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_tamu']) ?></td>
                                <td><?= htmlspecialchars($row['tujuan']) ?></td>
                                <td><?= date('d M Y H:i', strtotime($row['waktu_keluar'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Belum ada tamu yang keluar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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