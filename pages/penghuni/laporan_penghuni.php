<?php
require_once '../../includes/auth.php';
checkRole(['penghuni']);
require_once '../../config/db.php';

// Proses Form Tambah Laporan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_laporan'])) {
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $laporan = $_POST['laporan'];

    // Tanggal estimasi tindak lanjut (misal 3 hari setelah tanggal laporan)
    $tanggal_tindak_lanjut = date('Y-m-d', strtotime($tanggal . ' +3 days'));

    // Insert query tanpa teknisi, tapi dengan tanggal_tindak_lanjut
    $query = "INSERT INTO laporan_masalah (tanggal, laporan, waktu, tanggal_tindak_lanjut)
              VALUES ('$tanggal', '$laporan', '$waktu', '$tanggal_tindak_lanjut')";

    if (mysqli_query($conn, $query)) {
        $successMessage = "Laporan berhasil ditambahkan. Estimasi tindak lanjut pada tanggal <strong>$tanggal_tindak_lanjut</strong>.";
    } else {
        $errorMessage = "Gagal menambahkan laporan: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Laporan Penghuni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #3d7b65; min-height: 100vh; padding: 20px; color: white; width: 220px; }
        .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; }
        .sidebar a:hover, .sidebar .active { background-color: #2e5e4d; border-radius: 6px; }
        .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
        .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
        .sidebar-toggler { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px; background-color: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; }
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
        <div><strong>penghuni:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="buat_izin.php">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="data_tamu.php">Data Tamu</a>
        <a href="riwayat_kunjungan.php">Riwayat Kunjungan</a>
        <a href="laporan_penghuni.php" class="active">Laporan Penghuni</a>
        <a href="blacklist_laporan.php">Blacklist & Masalah</a>
        <a href="pengaturan_akun.php">Pengaturan Akun</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4>Laporan Penghuni</h4>

        <?php if (!empty($successMessage)) echo "<div class='alert alert-success'>$successMessage</div>"; ?>
        <?php if (!empty($errorMessage)) echo "<div class='alert alert-danger'>$errorMessage</div>"; ?>

        <!-- FORM TAMBAH -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Tambah Laporan Masalah</h5>
                <form method="post" action="">
                    <input type="hidden" name="tambah_laporan" value="1" />
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="waktu">Waktu</label>
                            <input type="time" class="form-control" name="waktu" required>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label for="laporan">Laporan Masalah</label>
                            <textarea class="form-control" name="laporan" rows="3" required></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mt-2">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('overlay').classList.toggle('show');
    }
</script>

</body>
</html>