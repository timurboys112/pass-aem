<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

// Jika ada parameter success=1, ambil data tamu terbaru dari DB (pakai nama untuk contoh)
$info_tamu = null;
if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['nama'])) {
    $nama = $_GET['nama'];
    $stmt = $conn->prepare("SELECT nama_tamu, tujuan, jenis_kelamin, no_hp, alamat, waktu_checkin FROM tamu WHERE nama_tamu = ? ORDER BY waktu_checkin DESC LIMIT 1");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $info_tamu = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Form Check-in Tamu</title>
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
      color: #e0f0e6;
      width: 220px;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      transition: left 0.3s ease;
      z-index: 1000;
    }
    .sidebar a {
      color: #e0f0e6;
      text-decoration: none;
      display: block;
      padding: 10px 0;
      border-radius: 6px;
    }
    .sidebar a:hover,
    .sidebar a.active {
      background-color: #2e5e4d;
      color: white;
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
      margin-bottom: 10px;
      background-color: #3d7b65;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      cursor: pointer;
    }
    .overlay {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
    .overlay.show {
      display: block;
    }
    .sidebar.show {
      left: 0;
    }
    @media (max-width: 768px) {
      .sidebar {
        left: -250px;
        position: fixed;
      }
      .sidebar-toggler {
        display: block;
      }
    }
    main {
      margin-left: 220px;
      padding: 20px;
    }
    @media (max-width: 768px) {
      main {
        margin-left: 0;
      }
    }
    .form-checkin {
      background-color: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .alert-blacklist {
      background-color: #ffcccc;
      color: #a94442;
      border: 1px solid #ebccd1;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    .info-tamu {
      background-color: #d1e7dd;
      border: 1px solid #badbcc;
      padding: 20px;
      border-radius: 10px;
      color: #0f5132;
      max-width: 600px;
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
  <a href="form_checkin.php" class="active">Form Check-in</a>
  <a href="validasi_qr.php">Validasi QR</a>
  <a href="cek_blacklist.php">Cek Blacklist</a>
  <a href="/pass-aem/logout.php" class="text-danger">Logout</a>
</div>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<main>
  <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
  <h4 class="mb-4">Formulir Check-in Tamu</h4>

  <?php if ($info_tamu): ?>
  <div class="info-tamu mb-4">
    <h5>Check-in Berhasil!</h5>
    <p><strong>Nama:</strong> <?= htmlspecialchars($info_tamu['nama_tamu']) ?></p>
    <p><strong>Tujuan Kunjungan:</strong> <?= htmlspecialchars($info_tamu['tujuan']) ?></p>
    <p><strong>Jenis Kelamin:</strong> <?= htmlspecialchars($info_tamu['jenis_kelamin']) ?></p>
    <p><strong>No. Telepon:</strong> <?= htmlspecialchars($info_tamu['no_hp']) ?></p>
    <p><strong>Alamat:</strong> <?= htmlspecialchars($info_tamu['alamat']) ?></p>
    <p><strong>Waktu Check-in:</strong> <?= htmlspecialchars($info_tamu['waktu_checkin']) ?></p>
    <!-- Jika kamu punya lokasi, bisa ditambahkan di sini -->
  </div>
  <?php endif; ?>

  <div class="form-checkin">
    <div id="blacklist-warning" class="alert-blacklist" style="display:none;"></div>

    <form action="proses_checkin.php" method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="nama" class="form-label">Nama Tamu</label>
        <input type="text" class="form-control" id="nama" name="nama" required />
      </div>
      <div class="mb-3">
        <label for="no_ktp" class="form-label">Nomor KTP</label>
        <input type="text" class="form-control" id="no_ktp" name="no_ktp" required />
      </div>
      <div class="mb-3">
        <label for="tujuan" class="form-label">Tujuan Kunjungan</label>
        <input type="text" class="form-control" id="tujuan" name="tujuan" required />
      </div>
      <div class="mb-3">
        <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
          <option value="" selected disabled>Pilih jenis kelamin</option>
          <option value="Laki-laki">Laki-laki</option>
          <option value="Perempuan">Perempuan</option>
          <option value="Lainnya">Lainnya</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="no_hp" class="form-label">Nomor Telepon</label>
        <input type="tel" class="form-control" id="no_hp" name="no_hp" required pattern="[0-9+\-\s]+" />
      </div>
      <div class="mb-3">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label for="foto_ktp" class="form-label">Upload Foto KTP</label>
        <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept="image/*" required />
      </div>
      <button type="submit" class="btn btn-success">Check-in</button>
    </form>
  </div>
</main>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  }

  // Contoh cek blacklist (bisa dikembangkan pakai AJAX)
  // Di sini hanya contoh untuk alert saja
  document.getElementById('no_ktp').addEventListener('blur', function() {
    const noKtp = this.value.trim();
    // TODO: buat ajax cek blacklist dari server
    // Jika blacklist ditemukan, tampilkan pesan:
    // document.getElementById('blacklist-warning').style.display = 'block';
    // document.getElementById('blacklist-warning').textContent = 'Tamu ini masuk blacklist!';
  });
</script>

</body>
</html>