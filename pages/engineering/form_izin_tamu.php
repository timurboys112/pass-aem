<?php
session_start();
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

// Fungsi QR generator sederhana
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

$successMessage = '';
$errorMessage = '';
$qrSvg = '';
$nama_tamu_label = '';
$no_identitas_label = '';

$id_user = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_tamu = trim($_POST['nama_tamu'] ?? '');
    $no_identitas = trim($_POST['no_identitas'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin_enum = trim($_POST['jenis_kelamin'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $keperluan = trim($_POST['keperluan'] ?? '');
    $jadwal = trim($_POST['jadwal'] ?? '');

    if ($nama_tamu && $no_identitas && $tujuan && $jadwal && $keperluan) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $jadwal);
        if ($dt === false) {
            $errorMessage = "Format tanggal dan jam tidak valid.";
        } else {
            $tanggal_kunjungan = $dt->format('Y-m-d');
            $jam_kunjungan = $dt->format('H:i:s');

            $result = $conn->query("SELECT MAX(id) as max_id FROM tamu");
            if ($result) {
                $row = $result->fetch_assoc();
                $new_id = ($row['max_id'] ?? 0) + 1;

                $stmt1 = $conn->prepare("INSERT INTO tamu (id, nama_tamu, no_identitas, tujuan, no_hp, jenis_kelamin, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt1) {
                    $stmt1->bind_param("issssss", $new_id, $nama_tamu, $no_identitas, $tujuan, $no_hp, $jenis_kelamin_enum, $alamat);
                    if ($stmt1->execute()) {
                        $id_tamu = $new_id;

                        $stmt2 = $conn->prepare("INSERT INTO izin_kunjungan_engineering (id_user, id_tamu, tujuan, tanggal_kunjungan, jam_kunjungan, keperluan, status) VALUES (?, ?, ?, ?, ?, ?, 'menunggu')");
                        if ($stmt2) {
                            $stmt2->bind_param("iissss", $id_user, $id_tamu, $tujuan, $tanggal_kunjungan, $jam_kunjungan, $keperluan);
                            if ($stmt2->execute()) {
                                $qrData = "TamuID:$id_tamu|Nama:$nama_tamu|NoIdentitas:$no_identitas|Tujuan:$tujuan|Tanggal:$tanggal_kunjungan|Jam:$jam_kunjungan|Keperluan:$keperluan";
                                $qrSvg = generateSimpleQrSvg($qrData);
                                $successMessage = "Izin berhasil dibuat!";
                                $nama_tamu_label = $nama_tamu;
                                $no_identitas_label = $no_identitas;
                            } else {
                                $errorMessage = "Gagal menyimpan izin kunjungan: " . htmlspecialchars($stmt2->error);
                            }
                            $stmt2->close();
                        } else {
                            $errorMessage = "Gagal prepare izin_kunjungan_engineering: " . htmlspecialchars($conn->error);
                        }
                    } else {
                        $errorMessage = "Gagal menyimpan data tamu: " . htmlspecialchars($stmt1->error);
                    }
                    $stmt1->close();
                } else {
                    $errorMessage = "Gagal prepare tamu: " . htmlspecialchars($conn->error);
                }
            } else {
                $errorMessage = "Gagal generate ID tamu: " . htmlspecialchars($conn->error);
            }
        }
    } else {
        $errorMessage = "Lengkapi semua data yang wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Izin Kunjungan - Engineering</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #3d7b65; min-height: 100vh; padding: 20px; color: white; width: 220px; }
        .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; border-radius: 6px; }
        .sidebar a:hover, .sidebar .active { background-color: #2e5e4d; }
        .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
        .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
        .sidebar-toggler { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px 0; background-color: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; }
            .overlay.show { display: block; }
        }
        .qr-container { text-align: center; margin-top: 20px; border: 1px dashed #ccc; padding: 20px; max-width: 300px; }
        .qr-container svg { max-width: 100%; height: auto; }
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; top: 0; left: 0; width: 100%; text-align: center; }
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
        <div><strong>Engineering:</strong> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Guest'); ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="daftar_kunjungan.php">Daftar Kunjungan</a>
        <a href="form_izin_tamu.php" class="active">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="laporan_teknisi.php">Laporan Teknisi</a>
        <a href="setting.php">Pengaturan</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4>Form Izin Kunjungan Vendor / Teknisi</h4>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="mt-3">
            <div class="mb-3">
                <label for="nama_tamu" class="form-label">Nama Tamu / Vendor</label>
                <input type="text" id="nama_tamu" name="nama_tamu" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="no_identitas" class="form-label">No Identitas</label>
                <input type="text" id="no_identitas" name="no_identitas" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">No HP</label>
                <input type="text" id="no_hp" name="no_hp" class="form-control" />
            </div>
            <div class="mb-3">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                <select id="jenis_kelamin" name="jenis_kelamin" class="form-control">
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea id="alamat" name="alamat" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label for="tujuan" class="form-label">Unit Tujuan</label>
                <input type="text" id="tujuan" name="tujuan" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="jadwal" class="form-label">Tanggal & Jam Kunjungan</label>
                <input type="datetime-local" id="jadwal" name="jadwal" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="keperluan" class="form-label">Keperluan</label>
                <textarea id="keperluan" name="keperluan" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Izin & Buat QR</button>
        </form>

        <?php if ($qrSvg): ?>
            <div class="print-area">
                <div class="qr-container mt-4">
                    <h5>QR Code Izin Kunjungan</h5>
                    <div><?php echo $qrSvg; ?></div>
                    <div><strong><?php echo htmlspecialchars($nama_tamu_label); ?></strong></div>
                    <div>No. Identitas: <?php echo htmlspecialchars($no_identitas_label); ?></div>
                </div>
                <button class="btn btn-secondary mt-3" onclick="window.print()">🖨️ Cetak QR & Info</button>
            </div>
        <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>