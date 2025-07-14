<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';

checkRole(['penghuni']);

$sql = "
    SELECT b.id, t.nama_tamu AS nama_tamu, b.alasan
    FROM blacklist b
    JOIN tamu t ON b.id_tamu = t.id
    WHERE b.tamu_type = 'tamu'

    UNION ALL

    SELECT b.id, k.nama_pengantar AS nama_tamu, b.alasan
    FROM blacklist b
    JOIN tamu_kilat k ON b.id_tamu = k.id
    WHERE b.tamu_type = 'kilat'

    UNION ALL

    SELECT b.id, e.id_tamu AS nama_tamu, b.alasan
    FROM blacklist b
    JOIN izin_kunjungan_engineering e ON b.id_tamu = e.id
    WHERE b.tamu_type = 'engineering'

    ORDER BY id DESC
";

$result = $conn->query($sql);
$blacklist = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $blacklist[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Blacklist (Penghuni)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar {
            background-color: #3d7b65;
            min-height: 100vh;
            padding: 20px;
            color: #e0f0e6;
            width: 220px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar a {
            color: #e0f0e6;
            text-decoration: none;
            display: block;
            padding: 10px 0;
            border-radius: 6px;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #2e5e4d;
            color: white;
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
            margin-bottom: 10px;
            background-color: #3d7b65;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .overlay.show {
            display: block;
        }
        .sidebar.show {
            left: 0;
        }
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
                position: fixed;
            }
            .sidebar-toggler {
                display: block;
            }
        }
        main {
            margin-left: 220px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
            }
        }
        .card.border-success {
            border-color: #2e5e4d !important;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .card.border-success:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .card .text-success {
            color: #2e5e4d !important;
        }
    </style>
</head>
<body>
<div>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
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

    <main>
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Blacklist Tamu (Hanya lihat)</h4>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Nama</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($blacklist) > 0): ?>
                        <?php foreach ($blacklist as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama_tamu']) ?></td>
                                <td><?= htmlspecialchars($item['alasan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">Data blacklist kosong.</td>
                        </tr>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>