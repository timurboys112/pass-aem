<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
checkRole(['admin', 'satpam']);

// Ambil data blacklist dengan nama tamu sesuai tipe
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
    <title>Daftar Blacklist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #2e7d32; min-height: 100vh; padding: 20px; color: white; width: 220px; }
        .sidebar a { color: #c8e6c9; text-decoration: none; display: block; padding: 10px 0; font-weight: 500; }
        .sidebar a:hover { background-color: #1b5e20; border-radius: 6px; }
        .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
        .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
        .sidebar-toggler { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px; background-color: #2e7d32; color: white; border: none; padding: 8px 12px; border-radius: 5px; font-weight: bold; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; }
            .overlay.show { display: block; }
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
        <div><strong>User:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
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

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Blacklist Tamu</h4>

        <!-- Notifikasi -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Data blacklist berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] == 'gagal_hapus'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Gagal menghapus data blacklist.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] == 'id_tidak_valid'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                ID blacklist tidak valid.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tombol Tambah -->
        <a href="form_blacklist.php" class="btn btn-danger btn-sm mb-3">+ Tambah ke Blacklist</a>

        <!-- Tabel -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Nama</th>
                        <th>Alasan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($blacklist) > 0): ?>
                        <?php foreach ($blacklist as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama_tamu']) ?></td>
                                <td><?= htmlspecialchars($item['alasan']) ?></td>
                                <td class="text-center">
                                    <a href="hapus_blacklist.php?id=<?= htmlspecialchars($item['id']) ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Yakin hapus data blacklist ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Data blacklist kosong.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>