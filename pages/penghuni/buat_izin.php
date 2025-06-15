<?php
require_once '../../includes/auth.php';
checkRole(['penghuni']);
require_once '../../config/db.php';

$successMessage = '';
$errorMessage = '';
$qrSvg = '';
$id_user = $_SESSION['user_id'] ?? null;

function generateSimpleQrSvg($data) {
    $hash = hash('sha512', $data);
    $size = 21;
    $svgSize = $size * 10;
    $bits = '';
    for ($i = 0; $i < $size * $size; $i++) {
        $hexIndex = (int)floor($i / 4);
        $hexDigit = hexdec($hash[$hexIndex]);
        $bitPosition = 3 - ($i % 4);
        $bit = ($hexDigit >> $bitPosition) & 1;
        $bits .= $bit;
    }
    $svg = "<svg width='{$svgSize}' height='{$svgSize}' xmlns='http://www.w3.org/2000/svg' style='background:#fff'>";
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $i = $y * $size + $x;
            if ($bits[$i] === '1') {
                $px = $x * 10;
                $py = $y * 10;
                $svg .= "<rect x='{$px}' y='{$py}' width='10' height='10' fill='#000'/>";
            }
        }
    }
    $svg .= "</svg>";
    return $svg;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_tamu     = trim($_POST['nama_tamu'] ?? '');
    $no_identitas  = trim($_POST['no_identitas'] ?? '');
    $no_hp         = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $unit_tujuan   = trim($_POST['unit_tujuan'] ?? '');
    $keperluan     = trim($_POST['keperluan'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');
    $jadwal        = trim($_POST['jadwal'] ?? '');
    $foto_ktp      = $_FILES['foto_ktp'] ?? null;

    if (!$nama_tamu || !$no_identitas || !$no_hp || !$jenis_kelamin || !$unit_tujuan || !$keperluan || !$alamat || !$jadwal || !$foto_ktp['name']) {
        $errorMessage = "Semua kolom bertanda * harus diisi.";
    } elseif (!preg_match('/^[0-9\+]+$/', $no_hp)) {
        $errorMessage = "Nomor telepon tidak valid.";
    } elseif ($foto_ktp['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Gagal mengunggah foto KTP.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024;
        if (!in_array($foto_ktp['type'], $allowedTypes)) {
            $errorMessage = "Format foto KTP harus JPG atau PNG.";
        } elseif ($foto_ktp['size'] > $maxSize) {
            $errorMessage = "Ukuran foto KTP maksimal 2MB.";
        } else {
            $jenis_kelamin_enum = $jenis_kelamin === 'Laki-laki' ? 'L' : ($jenis_kelamin === 'Perempuan' ? 'P' : null);
            if (!$jenis_kelamin_enum) {
                $errorMessage = "Jenis kelamin tidak valid.";
            }
        }
    }

    if (!$errorMessage) {
        $ktpExt = pathinfo($foto_ktp['name'], PATHINFO_EXTENSION);
        $ktpName = uniqid('ktp_') . '.' . $ktpExt;
        $targetPath = '../../uploads/ktp/' . $ktpName;
        if (!move_uploaded_file($foto_ktp['tmp_name'], $targetPath)) {
            $errorMessage = "Gagal menyimpan file KTP.";
        } else {
            $stmt1 = $conn->prepare("INSERT INTO tamu (nama_tamu, no_identitas, no_hp, jenis_kelamin, tujuan, alamat, foto_ktp, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            if ($stmt1) {
                $stmt1->bind_param("sssssss", $nama_tamu, $no_identitas, $no_hp, $jenis_kelamin_enum, $unit_tujuan, $alamat, $ktpName);
                if ($stmt1->execute()) {
                    $id_tamu = $conn->insert_id;
                    $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $jadwal);
                    if ($datetime) {
                        $tanggal_kunjungan = $datetime->format('Y-m-d');
                        $jam_kunjungan = $datetime->format('H:i:s');
                        $jenis_izin = 'penghuni';

                        $stmt2 = $conn->prepare("INSERT INTO izin_kunjungan_penghuni (id_user, id_tamu, tujuan, tanggal_kunjungan, jam_kunjungan, jenis_izin, status, keperluan) VALUES (?, ?, ?, ?, ?, ?, 'menunggu', ?)");
                        if ($stmt2) {
                            $stmt2->bind_param("iisssss", $id_user, $id_tamu, $unit_tujuan, $tanggal_kunjungan, $jam_kunjungan, $jenis_izin, $keperluan);
                            if ($stmt2->execute()) {
                                $successMessage = "Izin tamu berhasil dibuat.";
                                $qrData = "Nama: $nama_tamu\nID: $no_identitas";
                                $qrSvg = generateSimpleQrSvg($qrData);
                            } else {
                                $errorMessage = "Gagal menyimpan izin: " . htmlspecialchars($stmt2->error);
                            }
                            $stmt2->close();
                        } else {
                            $errorMessage = "Query izin gagal: " . htmlspecialchars($conn->error);
                        }
                    } else {
                        $errorMessage = "Format tanggal/jam salah.";
                    }
                } else {
                    $errorMessage = "Gagal menyimpan tamu: " . htmlspecialchars($stmt1->error);
                }
                $stmt1->close();
            } else {
                $errorMessage = "Query tamu gagal: " . htmlspecialchars($conn->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Izin Tamu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #3d7b65; min-height: 100vh; padding: 20px; color: white; width: 220px; }
        .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; }
        .sidebar a.active, .sidebar a:hover { background-color: #2e5e4d; border-radius: 6px; }
        .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
        .logo img { width: 30px; margin-right: 8px; }
        .sidebar-toggler { display: none; }
        .qr-container { margin-top: 20px; background: white; padding: 15px; border: 2px solid #3d7b65; display: inline-block; text-align: center; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -250px; top: 0; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px; background: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.4); z-index: 998; }
            .overlay.show { display: block; }
        }
        @media print {
            body * { visibility: hidden; }
            .qr-container, .qr-container * { visibility: visible; }
            .qr-container { position: absolute; top: 0; left: 0; }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" style="height:38px; width:auto; border-radius:6px; background:#fff; padding:3px;" />
            Visitor Pass
        </div>
        <div><strong>Penghuni:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="buat_izin.php" class="active">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="data_tamu.php">Data Tamu</a>
        <a href="riwayat_kunjungan.php">Riwayat Kunjungan</a>
        <a href="laporan_penghuni.php">Laporan Penghuni</a>
        <a href="blacklist_laporan.php">Blacklist & Masalah</a>
        <a href="pengaturan_akun.php">Pengaturan Akun</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Buat Izin Tamu</h4>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php if ($qrSvg): ?>
                <div class="qr-container">
                    <h5>QR Code Tamu:</h5>
                    <?= $qrSvg ?>
                    <p class="mt-3"><strong>Nama:</strong> <?= htmlspecialchars($nama_tamu) ?></p>
                    <p><strong>No. Identitas:</strong> <?= htmlspecialchars($no_identitas) ?></p>
                    <button class="btn btn-primary mt-2" onclick="window.print()">Print</button>
                </div>
            <?php endif; ?>
        <?php elseif ($errorMessage): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nama_tamu" class="form-label">Nama Tamu *</label>
                    <input type="text" class="form-control" id="nama_tamu" name="nama_tamu" required>
                </div>
                <div class="col-md-6">
                    <label for="no_identitas" class="form-label">No. Identitas *</label>
                    <input type="text" class="form-control" id="no_identitas" name="no_identitas" required>
                </div>
                <div class="col-md-6">
                    <label for="no_hp" class="form-label">No. HP *</label>
                    <input type="text" class="form-control" id="no_hp" name="no_hp" required>
                </div>
                <div class="col-md-6">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin *</label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="unit_tujuan" class="form-label">Unit Tujuan *</label>
                    <input type="text" class="form-control" id="unit_tujuan" name="unit_tujuan" required>
                </div>
                <div class="col-md-6">
                    <label for="keperluan" class="form-label">Keperluan *</label>
                    <input type="text" class="form-control" id="keperluan" name="keperluan" required>
                </div>
                <div class="col-12">
                    <label for="alamat" class="form-label">Alamat Tamu *</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" required></textarea>
                </div>
                <div class="col-md-6">
                    <label for="jadwal" class="form-label">Jadwal Kunjungan *</label>
                    <input type="datetime-local" class="form-control" id="jadwal" name="jadwal" required>
                </div>
                <div class="col-md-6">
                    <label for="foto_ktp" class="form-label">Foto KTP *</label>
                    <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept="image/*" required>
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-4">Simpan dan Buat QR</button>
        </form>
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