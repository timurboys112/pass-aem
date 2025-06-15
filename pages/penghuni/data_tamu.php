<?php
require_once '../../includes/auth.php';
checkRole(['penghuni']);
require_once '../../config/db.php';

// Ambil data tamu terbaru dengan JOIN ke tabel tamu
$sql = "
SELECT ikp.*, t.nama_tamu, t.no_identitas, t.jenis_kelamin, t.no_hp, t.alamat, t.foto_ktp AS foto
FROM izin_kunjungan_penghuni ikp
LEFT JOIN tamu t ON ikp.id_tamu = t.id
ORDER BY ikp.created_at DESC
";
$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Data Tamu - Penghuni</title>
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
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }

            .overlay.show {
                display: block;
            }
        }

        .foto-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ccc;
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

        <!-- Overlay untuk sidebar mobile -->
        <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

        <!-- Konten utama -->
        <div class="flex-grow-1 p-4">
            <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
            <h4 class="mb-4">Data Tamu</h4>

            <?php if ($result->num_rows > 0): ?>
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-success">
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>Nama Tamu</th>
                            <th>No. Identitas</th>
                            <th>Jenis Kelamin</th>
                            <th>No. HP</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <?php if (!empty($row['foto']) && file_exists('../../uploads/'.$row['foto'])): ?>
                                        <img src="../../uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Foto Tamu" class="foto-thumb" />
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['nama_tamu'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['no_identitas'] ?? '-') ?></td>
                                <td><?= ($row['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td><?= htmlspecialchars($row['no_hp'] ?? '-') ?></td>
                                <td><?= nl2br(htmlspecialchars($row['alamat'] ?? '-')) ?></td>
                                <td>
                                    <?php 
                                    $status = $row['status'];
                                    $badgeClass = 'secondary';
                                    switch ($status) {
                                        case 'menunggu':
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
                                    }
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada data tamu.</div>
            <?php endif; ?>
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