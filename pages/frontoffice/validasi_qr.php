<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

$messageStatus = null;
$kunjunganData = null;
$blacklistAlert = null;

// Handle Check-in
if (isset($_POST['checkin_id'])) {
    $id = intval($_POST['checkin_id']);
    $stmt = $conn->prepare("UPDATE tamu SET status = 'checkedin' WHERE id = ?");
    if (!$stmt) {
        die("❌ Query gagal: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $messageStatus = "<div class='alert alert-success'>✅ Tamu berhasil Check-in.</div>";
    } else {
        $messageStatus = "<div class='alert alert-warning'>⚠️ Tidak ada perubahan status.</div>";
    }
}

// Ambil data tamu
if (isset($_GET['id'])) {
    $id = htmlspecialchars($_GET['id']);

    $stmt = $conn->prepare("SELECT * FROM tamu WHERE id = ?");
    if (!$stmt) {
        die("❌ Prepare gagal: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $kunjunganData = $result->fetch_assoc();

        // Cek blacklist
        $stmt2 = $conn->prepare("SELECT * FROM blacklist WHERE nama = ?");
        if ($stmt2) {
            $stmt2->bind_param("s", $kunjunganData['nama_tamu']);
            $stmt2->execute();
            $blacklist = $stmt2->get_result();

            if ($blacklist->num_rows > 0) {
                $blacklistAlert = "<div class='alert alert-danger'>⚠️ TAMU INI MASUK DAFTAR BLACKLIST!</div>";
            }
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
  <title>Validasi QR Tamu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://unpkg.com/html5-qrcode"></script>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
    .sidebar {
      background-color: #3d7b65; min-height: 100vh; padding: 20px; color: #e0f0e6;
      width: 220px; position: fixed; top: 0; left: 0; overflow-y: auto; z-index: 1000; transition: left 0.3s ease;
    }
    .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; border-radius: 6px; }
    .sidebar a:hover, .sidebar a.active { background-color: #2e5e4d; color: white; }
    .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
    .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
    .sidebar-toggler {
      display: none; margin-bottom: 10px; background-color: #3d7b65; color: white;
      border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer;
    }
    .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5); z-index: 999;
    }
    .overlay.show { display: block; }
    .sidebar.show { left: 0; }
    @media (max-width: 768px) {
      .sidebar { left: -250px; position: fixed; z-index: 1001; }
      .sidebar-toggler { display: block; }
    }
    main { margin-left: 220px; padding: 20px; }
    @media (max-width: 768px) { main { margin-left: 0; } }
    #reader { max-width: 480px; margin: auto; border-radius: 10px; }
  </style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <div class="logo">
    <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
    Visitor Pass FO
  </div>
  <div><strong>User:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
  <hr class="border-light" />
  <a href="dashboard.php">Dashboard</a>
  <a href="daftar_tamu.php">Daftar Tamu</a>
  <a href="form_checkin.php">Form Check-in</a>
  <a href="validasi_qr.php" class="active">Validasi QR</a>
  <a href="cek_blacklist.php">Cek Blacklist</a>
  <a href="/pass-aem/logout.php" class="text-danger">Logout</a>
</div>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<main>
  <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
  <h4 class="mb-4">Validasi QR Tamu</h4>

  <?= $messageStatus ?? '' ?>
  <?= $blacklistAlert ?? '' ?>

  <div id="reader"></div>
  <div id="output" class="mt-3 text-center text-muted">🔍 Arahkan QR ke kamera</div>

  <hr>
  <h5>Atau Input Manual ID Tamu</h5>
  <form method="GET" class="mb-4">
    <div class="input-group">
      <input type="text" name="id" class="form-control" placeholder="ID Tamu dari QR Code" required
        value="<?= isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '' ?>" />
      <button class="btn btn-primary">Cek</button>
    </div>
  </form>

  <?php if ($kunjunganData): ?>
    <div class="card p-3">
      <h5>Detail Tamu</h5>
      <p><strong>Nama:</strong> <?= htmlspecialchars($kunjunganData['nama_tamu']) ?></p>
      <p><strong>Jenis Kelamin:</strong>
        <?= $kunjunganData['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($kunjunganData['jenis_kelamin'] === 'P' ? 'Perempuan' : 'Lainnya') ?>
      </p>
      <p><strong>Tujuan:</strong> <?= htmlspecialchars($kunjunganData['tujuan']) ?></p>
      <p><strong>Alamat:</strong> <?= htmlspecialchars($kunjunganData['alamat']) ?></p>
      <p><strong>No HP:</strong> <?= htmlspecialchars($kunjunganData['no_hp']) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($kunjunganData['status']) ?></p>
      <p><strong>Waktu Check-in:</strong> <?= htmlspecialchars($kunjunganData['waktu_checkin']) ?></p>
      <p><strong>Izin Penghuni:</strong> <?= htmlspecialchars($kunjunganData['izin_penghuni'] ?: '-') ?></p>

      <?php if (!empty($kunjunganData['foto_ktp'])): ?>
        <p><strong>Foto KTP:</strong><br>
          <img src="/aem-visitor/uploads/<?= htmlspecialchars($kunjunganData['foto_ktp']) ?>" alt="Foto KTP" style="max-width:200px; border-radius:10px;" />
        </p>
      <?php endif; ?>

      <?php if ($kunjunganData['status'] !== 'checkedin'): ?>
        <form method="POST" class="mt-3">
          <input type="hidden" name="checkin_id" value="<?= htmlspecialchars($kunjunganData['id']) ?>">
          <button type="submit" class="btn btn-success">✅ Konfirmasi Check-In</button>
        </form>
      <?php else: ?>
        <div class="alert alert-info mt-3">🟢 Tamu sudah Check-in.</div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('show');
  document.getElementById('overlay').classList.toggle('show');
}

// QR Scanner
function onScanSuccess(decodedText, decodedResult) {
  if (!isNaN(decodedText)) {
    window.location.href = "validasi_qr.php?id=" + decodedText;
  } else {
    document.getElementById('output').innerHTML = '<div class="alert alert-warning">❗ QR tidak valid</div>';
  }
}

let html5QrcodeScanner = new Html5QrcodeScanner("reader", {
    fps: 10,
    qrbox: 250
});
html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>