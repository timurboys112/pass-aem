<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

// Ambil data tamu biasa (dari tabel tamu)
$sqlTamu = "SELECT 
    id,
    nama_tamu AS nama,
    status,
    waktu_checkin AS waktu_masuk,
    jenis_kelamin,
    no_hp,
    alamat,
    tujuan AS unit -- Ganti dengan kolom unit asli jika ada
FROM tamu";

// Ambil data tamu kurir dari tabel tamu_kilat
$sqlKurir = "SELECT 
    id,
    nama_pengantar AS nama,
    'Kurir' AS status,
    waktu_masuk,
    NULL AS jenis_kelamin,
    NULL AS no_hp,
    NULL AS alamat,
    tujuan_unit AS unit
FROM tamu_kilat";

// Eksekusi query tamu biasa
$tamuList = [];
$resultTamu = $conn->query($sqlTamu);
if ($resultTamu && $resultTamu->num_rows > 0) {
    while ($row = $resultTamu->fetch_assoc()) {
        $tamuList[] = $row;
    }
} else if (!$resultTamu) {
    die("Query error data tamu biasa: " . $conn->error);
}

// Eksekusi query tamu kurir
$resultKurir = $conn->query($sqlKurir);
if ($resultKurir && $resultKurir->num_rows > 0) {
    while ($row = $resultKurir->fetch_assoc()) {
        $tamuList[] = $row;
    }
} else if (!$resultKurir) {
    die("Query error data tamu kurir: " . $conn->error);
}

// Urutkan gabungan data tamu berdasarkan waktu_masuk DESC
usort($tamuList, function($a, $b) {
    return strtotime($b['waktu_masuk']) <=> strtotime($a['waktu_masuk']);
});

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Daftar Tamu Masuk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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

            <h4>Daftar Tamu Masuk</h4>
            <p>Berikut adalah daftar tamu yang telah masuk ke area Anda, baik tamu biasa maupun kurir.</p>
            
            <!-- Tabel data tamu dari tabel kunjungan -->
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Waktu Masuk</th>
                        <th>Jenis Kelamin</th>
                        <th>No HP</th>
                        <th>Alamat</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tamuList)): ?>
                        <?php foreach ($tamuList as $tamu): ?>
                            <tr>
                                <td><?= htmlspecialchars($tamu['nama']) ?></td>
                                <td><?= htmlspecialchars($tamu['status']) ?></td>
                                <td><?= !empty($tamu['waktu_masuk']) ? date('d M Y H:i', strtotime($tamu['waktu_masuk'])) : '-' ?></td>
                                <td><?= htmlspecialchars($tamu['jenis_kelamin'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($tamu['no_hp'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($tamu['alamat'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($tamu['unit'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data tamu.</td>
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