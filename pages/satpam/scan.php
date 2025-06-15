<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

$messageStatus = null;

// Proses Check-In
if (isset($_POST['konfirmasi'])) {
    $id_kunjungan = $_POST['id_kunjungan'];
    $stmt = $conn->prepare("UPDATE kunjungan SET status='Check-In' WHERE id=?");
    $stmt->bind_param("s", $id_kunjungan);
    $stmt->execute();
    $messageStatus = "<div class='alert alert-success mt-3'>✅ Check-In berhasil dikonfirmasi.</div>";
}

// Ambil data kunjungan
$kunjunganData = null;
$blacklistAlert = null;
if (isset($_GET['id'])) {
    $id = htmlspecialchars($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM kunjungan WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $kunjunganData = $result->fetch_assoc();

        $stmt2 = $conn->prepare("SELECT * FROM blacklist WHERE nama = ?");
        $stmt2->bind_param("s", $kunjunganData['nama']);
        $stmt2->execute();
        $blacklist = $stmt2->get_result();

        if ($blacklist->num_rows > 0) {
            $blacklistAlert = "<div class='alert alert-danger'>⚠️ TAMU INI MASUK DAFTAR BLACKLIST!</div>";
        }
    } else {
        $messageStatus = "<div class='alert alert-warning'>⚠️ Data tidak ditemukan.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Scan QR Tamu</title>
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
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 998;
      }
      .overlay.show {
        display: block;
      }
    }
    video {
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 480px;
      display: block;
      margin: 0 auto;
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
    <h4 class="mb-4">Scan QR Tamu</h4>

    <?= $messageStatus ?? '' ?>
    <?= $blacklistAlert ?? '' ?>

    <video id="video" autoplay></video>
    <div id="output" class="mt-3 text-center text-muted">🔍 Arahkan QR ke kamera</div>

    <hr>
    <h5>Atau Input Manual ID Kunjungan</h5>
    <form method="GET" class="mb-4">
      <div class="input-group">
        <input type="text" name="id" class="form-control" placeholder="ID Kunjungan dari QR Code" required value="<?= isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '' ?>" />
        <button class="btn btn-primary">Cek</button>
      </div>
    </form>

    <?php if ($kunjunganData): ?>
      <div class="card p-3">
        <h5>Detail Kunjungan</h5>
        <p><strong>Nama:</strong> <?= htmlspecialchars($kunjunganData['nama']) ?></p>
        <p><strong>Unit Tujuan:</strong> <?= htmlspecialchars($kunjunganData['unit']) ?></p>
        <p><strong>Tujuan:</strong> <?= htmlspecialchars($kunjunganData['tujuan']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($kunjunganData['status']) ?></p>

        <?php if ($kunjunganData['status'] !== 'Check-In'): ?>
          <form method="POST" class="mt-3">
            <input type="hidden" name="id_kunjungan" value="<?= htmlspecialchars($kunjunganData['id']) ?>">
            <button class="btn btn-success" name="konfirmasi">Konfirmasi Check-In</button>
          </form>
        <?php else: ?>
          <div class="alert alert-info mt-3">✅ Tamu sudah Check-In.</div>
        <?php endif; ?>
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

const video = document.getElementById('video');
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
  .then(stream => video.srcObject = stream)
  .catch(err => {
    document.getElementById('output').innerHTML = '<div class="alert alert-danger">Gagal membuka kamera</div>';
  });
</script>
</body>
</html>