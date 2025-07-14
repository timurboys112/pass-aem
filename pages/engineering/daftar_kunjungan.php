<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

// Ambil data tamu masuk dari tabel kunjungan
$sqlMasuk = "SELECT t.nama_tamu, i.tujuan, k.waktu_masuk
FROM kunjungan k
JOIN izin_kunjungan_engineering i ON k.id_izin = i.id
JOIN tamu t ON i.id_tamu = t.id
WHERE k.waktu_masuk IS NOT NULL
ORDER BY k.waktu_masuk DESC";
$resultMasuk = $conn->query($sqlMasuk);
if (!$resultMasuk) {
    die("Query error data tamu masuk: " . $conn->error);
}

// Ambil data izin kunjungan dengan status menunggu
$query = "SELECT izin_kunjungan_engineering.*, tamu.nama_tamu 
          FROM izin_kunjungan_engineering 
          JOIN tamu ON izin_kunjungan_engineering.id_tamu = tamu.id 
          WHERE izin_kunjungan_engineering.status = 'menunggu'";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Kunjungan - Engineering</title>
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
        .sidebar a:hover,
        .sidebar .active {
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
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass
        </div>
        <div><strong>Engineering:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="daftar_kunjungan.php" class="active">Daftar Kunjungan</a>
        <a href="form_izin_tamu.php">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="laporan_teknisi.php">Laporan Teknisi</a>
        <a href="setting.php">Pengaturan</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </nav>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Daftar Kunjungan Vendor / Teknisi</h4>
        <table class="table table-bordered table-hover">
            <thead class="table-success">
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $status = $row['status'];
                            $badgeClass = 'secondary';
                            switch (strtolower($status)) {
                                case 'pending':
                                    $badgeClass = 'warning';
                                    break;
                                case 'disetujui':
                                    $badgeClass = 'success';
                                    break;
                                case 'ditolak':
                                    $badgeClass = 'danger';
                                    break;
                                case 'selesai':
                                    $badgeClass = 'primary';
                                    break;
                                case 'menunggu':
                                    $badgeClass = 'info';
                                    break;
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['nama_tamu']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y H:i', strtotime($row['jam_kunjungan']))) ?></td>
                            <td><span class="badge bg-<?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($status)) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada data kunjungan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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