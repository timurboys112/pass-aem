<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

$info_kurir = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pengantar = $_POST['nama_pengantar'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $layanan = $_POST['layanan'] ?? '';
    $tujuan_unit = $_POST['tujuan_unit'] ?? '';

    // Handle upload foto KTP
    $upload_dir = '../../uploads/';
    $foto_ktp_filename = null;
    if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['foto_ktp']['tmp_name'];
        $original_name = basename($_FILES['foto_ktp']['name']);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed_ext)) {
            $foto_ktp_filename = uniqid('ktp_') . '.' . $ext;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            move_uploaded_file($tmp_name, $upload_dir . $foto_ktp_filename);
        } else {
            die('Format file foto KTP tidak didukung.');
        }
    } else {
        die('Upload foto KTP gagal.');
    }

    // Insert ke DB
    $stmt = $conn->prepare("INSERT INTO tamu_kilat (nama_pengantar, nik, layanan, tujuan_unit, waktu_masuk, foto_ktp) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("sssss", $nama_pengantar, $nik, $layanan, $tujuan_unit, $foto_ktp_filename);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $last_id = $stmt->insert_id;
        $_SESSION['last_checkin_kurir'] = $last_id;

        $stmt->close();

        // Redirect ke halaman yang sama supaya form tidak submit ulang dan untuk tampilkan info
        header('Location: form_kurir.php');
        exit;
    } else {
        $stmt->close();
        die('Gagal check-in kurir.');
    }
}

// Kalau ada session last_checkin_kurir, ambil data untuk ditampilkan
if (isset($_SESSION['last_checkin_kurir'])) {
    $id = (int) $_SESSION['last_checkin_kurir'];
    $result = $conn->query("SELECT * FROM tamu_kilat WHERE id = $id LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $info_kurir = $result->fetch_assoc();
    }
    unset($_SESSION['last_checkin_kurir']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Check-In Kurir</title>
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
            .sidebar { position: fixed; top: 0; left: -250px; width: 220px; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px; background-color: #2e7d32; color: white; border: none; padding: 8px 12px; border-radius: 5px; font-weight: bold; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 998; }
            .overlay.show { display: block; }
        }
    </style>
</head>

<body>
<div class="d-flex">
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

    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Check-In Kurir</h4>

        <?php if ($info_kurir): ?>
        <div class="alert alert-success">
            <strong>Check-in Berhasil!</strong><br>
            <strong>Nama:</strong> <?= htmlspecialchars($info_kurir['nama_pengantar']) ?><br>
            <strong>Layanan:</strong> <?= htmlspecialchars($info_kurir['layanan']) ?><br>
            <strong>Tujuan:</strong> <?= htmlspecialchars($info_kurir['tujuan_unit']) ?><br>
            <strong>Waktu Masuk:</strong> <?= htmlspecialchars($info_kurir['waktu_masuk']) ?><br>
            <?php if ($info_kurir['foto_ktp']): ?>
                <img src="../../uploads/<?= htmlspecialchars($info_kurir['foto_ktp']) ?>" alt="Foto KTP" style="max-width: 200px; margin-top:10px; border: 1px solid #ccc;"/>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form action="form_kurir.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama_pengantar" class="form-label">Nama Pengantar</label>
                <input type="text" class="form-control" id="nama_pengantar" name="nama_pengantar" required>
            </div>
            <div class="mb-3">
                <label for="nik" class="form-label">NIK</label>
                <input type="text" class="form-control" id="nik" name="nik" required>
            </div>
            <div class="mb-3">
                <label for="layanan" class="form-label">Layanan</label>
                <select class="form-select" id="layanan" name="layanan" required>
                    <option value="" selected disabled>Pilih layanan</option>
                    <option value="Gojek">Gojek</option>
                    <option value="Grab">Grab</option>
                    <option value="ShopeeFood">ShopeeFood</option>
                    <option value="Kurir Lain">Kurir Lain</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="tujuan_unit" class="form-label">Tujuan Unit</label>
                <input type="text" class="form-control" id="tujuan_unit" name="tujuan_unit" required>
            </div>
            <div class="mb-3">
                <label for="foto_ktp" class="form-label">Upload Foto KTP</label>
                <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-success">Check-In</button>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}
</script>

</body>
</html>