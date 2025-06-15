<?php
session_start();
require_once "../../config/db.php";

// Cek login dan role tamu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tamu') {
  header("Location: ../../login.php");
  exit;
}

// Ambil data tamu berdasarkan user id
$userid = $_SESSION['userid'];

// Query ambil data tamu
$stmt = $conn->prepare("SELECT * FROM tamu_users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
  $nama = htmlspecialchars($user['nama_lengkap']);
  $email = htmlspecialchars($user['email']);
  $no_hp = htmlspecialchars($user['no_hp']);
} else {
  session_destroy();
  header("Location: ../../login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <title>Dashboard Tamu - Visitor Pass</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }

    .sidebar {
      background-color: #3d7b65;
      min-height: 100vh;
      padding: 20px;
      color: white;
      width: 220px;
    }

    .sidebar a {
      color: #e0f0e6;
      text-decoration: none;
      display: block;
      padding: 10px 0;
      border-radius: 6px;
    }

    .sidebar a:hover {
      background-color: #2e5e4d;
      color: #ffffff;
    }

    .logo {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logo img {
      width: 30px;
      border-radius: 6px;
      background: #fff;
      padding: 3px;
    }

    .sidebar-toggler {
      display: none;
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

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        top: 0;
        left: -250px;
        transition: left 0.3s ease;
        z-index: 999;
      }

      .sidebar.show {
        left: 0;
      }

      .sidebar-toggler {
        display: block;
        margin: 10px 0 20px 0;
        background-color: #3d7b65;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
      }

      .overlay.show {
        display: block;
      }
    }

    .content {
      flex-grow: 1;
      padding: 2rem;
    }

    .user-greeting {
      color: #3d7b65;
      font-weight: 600;
      margin-bottom: 1rem;
    }
  </style>
</head>

<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="logo">
        <img src="../../assets/images/logo aem.jpeg" alt="Logo" />
        Visitor Pass - Tamu
      </div>
      <div><strong>Tamu:</strong> <?= $nama ?></div>
      <hr class="border-light" />
      <a href="dashboard.php">Dashboard</a>
      <a href="self_checkin.php">Self Check-In</a>
      <a href="lokasi.php">Lihat Lokasi</a>
      <a href="profile.php">Profil Saya</a>
      <hr class="border-light" />
      <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay (untuk mobile) -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="content">
      <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
      <h2 class="user-greeting">Selamat datang, <?= $nama ?>!</h2>

      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card text-white bg-success mb-3">
            <div class="card-body">
              <h5 class="card-title">Status Check-In</h5>
              <p class="card-text">Belum Check-In</p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card text-white bg-primary mb-3">
            <div class="card-body">
              <h5 class="card-title">QR Code Terbaru</h5>
              <p class="card-text">Aktif sampai: 18 Mei 2025</p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card text-white bg-info mb-3">
            <div class="card-body">
              <h5 class="card-title">Tujuan Kunjungan</h5>
              <p class="card-text">Unit B-12 - Ibu Andini</p>
            </div>
          </div>
        </div>
      </div>

      <h5>🔗 Akses Cepat</h5>
      <div class="row mb-3">
        <div class="col-md-3">
          <a href="self_checkin.php" class="btn btn-outline-primary w-100">📲 Self Check-In</a>
        </div>
        <div class="col-md-3">
          <a href="riwayat_kunjungan.php" class="btn btn-outline-secondary w-100">📜 Riwayat</a>
        </div>
        <div class="col-md-3">
          <a href="profile.php" class="btn btn-outline-dark w-100">👤 Profil</a>
        </div>
      </div>

      <div class="alert alert-warning" role="alert">
        📌 <strong>Tips:</strong> Silakan lakukan <em>Generate QR Code</em> sebelum kunjungan agar proses check-in lebih cepat!
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <?php if ($_SESSION['role'] === 'tamu') : ?>
    <div class="position-fixed bottom-0 end-0 m-4" style="z-index: 9999;">
      <form action="../../panic_handler.php" method="post">
        <button type="submit" class="btn btn-danger btn-lg shadow-lg">
          🚨 Panic Button
        </button>
      </form>
    </div>
  <?php endif; ?>
</body>

</html>