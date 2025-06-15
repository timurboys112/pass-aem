<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

$userId = $_SESSION['user']['id'] ?? 0;
$tanggalHariIni = date('Y-m-d');

// Simpan catatan jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shift = $_POST['shift'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($shift && $catatan) {
        $stmt = $conn->prepare("INSERT INTO catatan_shift (id_user, tanggal, shift, catatan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $tanggalHariIni, $shift, $catatan);
        $stmt->execute();
        $stmt->close();
        header("Location: catatan_shift.php?success=1");
        exit;
    }
}

// Ambil catatan shift hari ini
$catatanHariIni = [];
$stmt = $conn->prepare("
    SELECT cs.*, su.username 
    FROM catatan_shift cs 
    JOIN satpam_users su ON cs.id_user = su.id 
    WHERE cs.tanggal = ?
    ORDER BY cs.created_at DESC
");
$stmt->bind_param("s", $tanggalHariIni);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $catatanHariIni[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Catatan Shift Satpam</title>
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

        @media print {
            .no-print, .sidebar, .sidebar-toggler, .overlay {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .flex-grow-1 {
                width: 100%;
                padding: 0;
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

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Konten utama -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler no-print" onclick="toggleSidebar()">☰ Menu</button>

        <div class="no-print mb-4 d-flex justify-content-between align-items-center">
            <h4>Catatan Shift - <?= date('d M Y') ?></h4>
            <div>
                <button class="btn btn-primary" onclick="window.print()">🖨 Cetak</button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Catatan berhasil disimpan.</div>
        <?php endif; ?>

        <!-- Form Tambah Catatan -->
        <form method="POST" class="mb-4" novalidate>
            <div class="mb-3">
                <label for="shift" class="form-label">Shift</label>
                <select name="shift" id="shift" class="form-select" required>
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi">Pagi</option>
                    <option value="Siang">Siang</option>
                    <option value="Malam">Malam</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="catatan" class="form-label">Catatan</label>
                <textarea name="catatan" id="catatan" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-success">Simpan Catatan</button>
        </form>

        <!-- Daftar Catatan Hari Ini -->
        <h5>Catatan Shift Hari Ini</h5>
        <?php if (count($catatanHariIni) > 0): ?>
            <ul class="list-group">
                <?php foreach ($catatanHariIni as $cat): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($cat['shift']) ?> (<?= htmlspecialchars($cat['username']) ?>):</strong><br />
                        <?= nl2br(htmlspecialchars($cat['catatan'])) ?>
                        <div class="small text-muted mt-1"><?= date('H:i', strtotime($cat['created_at'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-muted">Belum ada catatan shift hari ini.</div>
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

</body>
</html>