<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
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

// Kirim Email Notifikasi
if (isset($_POST['kirim_notifikasi'])) {
    $id_kunjungan = $_POST['id_kunjungan'];
    $stmt = $conn->prepare("SELECT nama, email, tujuan FROM kunjungan WHERE id=?");
    $stmt->bind_param("s", $id_kunjungan);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $email = $result['email'];
    $subject = "Konfirmasi Kunjungan ke AEM";
    $message = "Halo {$result['nama']},\n\nKunjungan Anda dengan tujuan: {$result['tujuan']} telah berhasil dikonfirmasi.\n\nTerima kasih.";
    $headers = "From: admin@aem-visitor.com";

    if (mail($email, $subject, $message, $headers)) {
        $messageStatus = "<div class='alert alert-success mt-3'>📧 Email berhasil dikirim ke $email</div>";
    } else {
        $messageStatus = "<div class='alert alert-danger mt-3'>❌ Gagal mengirim email ke $email.</div>";
    }
}

// Kirim WhatsApp (via link wa.me)
if (isset($_POST['kirim_wa'])) {
    $id_kunjungan = $_POST['id_kunjungan'];
    $stmt = $conn->prepare("SELECT nama, no_hp, tujuan FROM kunjungan WHERE id=?");
    $stmt->bind_param("s", $id_kunjungan);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $no_hp = preg_replace('/[^0-9]/', '', $result['no_hp']);
    $pesan = urlencode("Halo {$result['nama']}, kunjungan Anda ke AEM dengan tujuan: {$result['tujuan']} telah berhasil dikonfirmasi.");
    $linkWA = "https://wa.me/$no_hp?text=$pesan";
    $messageStatus = "<div class='alert alert-success mt-3'>📱 <a href='$linkWA' target='_blank'>Klik di sini untuk kirim WhatsApp ke {$result['nama']}</a></div>";
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
  <title>Validasi QR Tamu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
    video {
      border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 100%;
      max-width: 480px; display: block; margin: 0 auto;
    }
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
  <a href="validasi_qr.php">Validasi QR</a>
  <a href="cek_blacklist.php">Cek Blacklist</a>
  <a href="/pass-aem/logout.php" class="text-danger">Logout</a>
</div>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<main>
  <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
  <h4 class="mb-4">Validasi QR Tamu</h4>

  <?= $messageStatus ?? '' ?>
  <?= $blacklistAlert ?? '' ?>

  <video id="video" autoplay></video>
  <div id="output" class="mt-3 text-center text-muted">🔍 Arahkan QR ke kamera</div>

  <hr>
  <h5>Atau Input Manual ID Kunjungan</h5>
  <form method="GET" class="mb-4">
    <div class="input-group">
      <input type="text" name="id" class="form-control" placeholder="ID Kunjungan dari QR Code" required
        value="<?= isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '' ?>" />
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
      <p><strong>No HP:</strong> <?= htmlspecialchars($kunjunganData['no_hp']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($kunjunganData['email']) ?></p>

      <form method="POST" class="mt-3 d-flex flex-wrap gap-2">
        <input type="hidden" name="id_kunjungan" value="<?= htmlspecialchars($kunjunganData['id']) ?>">
        <?php if ($kunjunganData['status'] !== 'Check-In'): ?>
          <button class="btn btn-success" name="konfirmasi">Konfirmasi Check-In</button>
        <?php else: ?>
          <div class="alert alert-info w-100">✅ Tamu sudah Check-In.</div>
        <?php endif; ?>
        <button class="btn btn-info" name="kirim_notifikasi">Kirim Email</button>
        <button class="btn btn-success" name="kirim_wa">Kirim WhatsApp</button>
      </form>
    </div>
  <?php endif; ?>
</main>

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('show');
  document.getElementById('overlay').classList.toggle('show');
}

// Aktifkan kamera
const video = document.getElementById('video');
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
  .then(stream => video.srcObject = stream)
  .catch(err => {
    document.getElementById('output').innerHTML = '<div class="alert alert-danger">❌ Gagal membuka kamera</div>';
  });
</script>

</body>
</html>