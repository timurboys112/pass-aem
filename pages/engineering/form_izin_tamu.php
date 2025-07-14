<?php
session_start();
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';
require_once '../../includes/qrlib.php'; // phpqrcode

$successMessage = '';
$errorMessage = '';
$qrImagePath = '';
$nama_tamu_label = '';
$no_identitas_label = '';

$id_user = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_tamu = trim($_POST['nama_tamu'] ?? '');
    $no_identitas = trim($_POST['no_identitas'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin_enum = trim($_POST['jenis_kelamin'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $keperluan = trim($_POST['keperluan'] ?? '');
    $jadwal = trim($_POST['jadwal'] ?? '');
    $foto_ktp = $_FILES['foto_ktp'] ?? null;
    $nama_foto_ktp = null;

    // Validasi identitas: minimal isi salah satu
    if (empty($no_identitas) && (!$foto_ktp || $foto_ktp['error'] == 4)) {
        $errorMessage = "Isi minimal salah satu: nomor identitas atau unggah foto KTP.";
    } elseif ($nama_tamu && $tujuan && $jadwal && $keperluan) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $jadwal);
        if (!$dt) {
            $errorMessage = "Format tanggal tidak valid.";
        } else {
            $tanggal_kunjungan = $dt->format('Y-m-d');
            $jam_kunjungan = $dt->format('H:i:s');

            // Upload foto KTP jika ada
            if ($foto_ktp && $foto_ktp['error'] === 0) {
                $ext = pathinfo($foto_ktp['name'], PATHINFO_EXTENSION);
                $nama_foto_ktp = uniqid('ktp_') . '.' . $ext;
                $target = '../../uploads/' . $nama_foto_ktp;
                if (!is_dir('../../uploads')) mkdir('../../uploads', 0755, true);
                move_uploaded_file($foto_ktp['tmp_name'], $target);
            }

            // Generate ID tamu baru
            $result = $conn->query("SELECT MAX(id) as max_id FROM tamu");
            $new_id = 1;
            if ($result) {
                $row = $result->fetch_assoc();
                $new_id = ($row['max_id'] ?? 0) + 1;
            }

            $stmt1 = $conn->prepare("INSERT INTO tamu (id, nama_tamu, no_identitas, tujuan, no_hp, jenis_kelamin, alamat, foto_ktp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt1->bind_param("isssssss", $new_id, $nama_tamu, $no_identitas, $tujuan, $no_hp, $jenis_kelamin_enum, $alamat, $nama_foto_ktp);
            if ($stmt1->execute()) {
                $stmt2 = $conn->prepare("INSERT INTO izin_kunjungan_engineering (id_user, id_tamu, tujuan, tanggal_kunjungan, jam_kunjungan, keperluan, status) VALUES (?, ?, ?, ?, ?, ?, 'menunggu')");
                $stmt2->bind_param("iissss", $id_user, $new_id, $tujuan, $tanggal_kunjungan, $jam_kunjungan, $keperluan);
                if ($stmt2->execute()) {
                    // Generate QR
                    $qrData = "$new_id";
                    $qrFileName = "qr_images/qr_tamu_$new_id.png";
                    if (!file_exists('qr_images')) mkdir('qr_images', 0755, true);
                    QRcode::png($qrData, $qrFileName, QR_ECLEVEL_L, 6, 2);
                    $qrImagePath = $qrFileName;

                    $successMessage = "Izin berhasil dibuat!";
                    $nama_tamu_label = $nama_tamu;
                    $no_identitas_label = $no_identitas ?: '-';
                } else {
                    $errorMessage = "Gagal menyimpan izin kunjungan.";
                }
                $stmt2->close();
            } else {
                $errorMessage = "Gagal menyimpan data tamu.";
            }
            $stmt1->close();
        }
    } else {
        $errorMessage = "Isi semua data wajib.";
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
    .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; border-radius: 6px; }
    .sidebar a:hover, .sidebar .active { background-color: #2e5e4d; }
    .logo img { width: 30px; margin-right: 8px; background: white; padding: 2px; border-radius: 6px; }
    .sidebar-toggler { display: none; }
    @media (max-width: 768px) {
      .sidebar { position: fixed; left: -250px; transition: left 0.3s; z-index: 1000; }
      .sidebar.show { left: 0; }
      .sidebar-toggler { display: block; margin: 10px 0; background-color: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; }
      .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; }
      .overlay.show { display: block; }
    }
    .qr-container { text-align: center; margin-top: 10px; }
    .qr-container img { max-width: 200px; height: auto; margin-bottom: 10px; }
    @media print {
      body * { visibility: hidden !important; }
      .print-area, .print-area * { visibility: visible !important; }
      .print-area { position: absolute; top: 0; left: 0; width: 100%; text-align: center; }
    }
  </style>
</head>
<body>

<div class="d-flex">
  <div class="sidebar" id="sidebar">
    <div class="logo d-flex align-items-center">
      <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo"> Visitor Pass
    </div>
    <strong>Engineering:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?>
    <hr>
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

    <!-- QR muncul di atas -->
    <?php if ($qrImagePath): ?>
    <div class="print-area">
      <div class="qr-container">
        <h5>QR Izin Kunjungan</h5>
        <img src="<?= $qrImagePath ?>" alt="QR Code">
        <div><strong><?= htmlspecialchars($nama_tamu_label) ?></strong></div>
        <div>No Identitas: <?= htmlspecialchars($no_identitas_label) ?></div>
        <a href="<?= $qrImagePath ?>" download class="btn btn-success btn-sm mt-2">⬇️ Download QR</a>
        <button onclick="window.print()" class="btn btn-secondary btn-sm mt-2">🖨️ Cetak</button>
      </div>
      <hr>
    </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
      <div class="alert alert-danger"><?= $errorMessage ?></div>
    <?php elseif ($successMessage): ?>
      <div class="alert alert-success"><?= $successMessage ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Nama Tamu</label>
        <input type="text" name="nama_tamu" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">No Identitas (boleh dikosongkan jika unggah KTP)</label>
        <input type="text" name="no_identitas" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Foto KTP (boleh kosong jika isi identitas)</label>
        <input type="file" name="foto_ktp" accept="image/*" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">No HP</label>
        <input type="text" name="no_hp" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-control">
          <option value="L">Laki-laki</option>
          <option value="P">Perempuan</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Unit Tujuan</label>
        <input type="text" name="tujuan" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Tanggal & Jam Kunjungan</label>
        <input type="datetime-local" name="jadwal" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Keperluan</label>
        <textarea name="keperluan" class="form-control" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Kirim & Buat QR</button>
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