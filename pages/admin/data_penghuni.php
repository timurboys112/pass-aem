<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

// Ambil data penghuni dari database
$sql = "SELECT id, nama_lengkap, unit, no_hp FROM penghuni_users ORDER BY nama_lengkap ASC";
$result = $conn->query($sql);

$penghuni = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $penghuni[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Penghuni</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }
    .wrapper {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 220px;
      background-color: #3d7b65;
      color: white;
      padding: 20px;
      flex-shrink: 0;
    }
    .sidebar a {
      color: #e0f0e6;
      text-decoration: none;
      display: block;
      padding: 8px 0;
    }
    .sidebar a:hover,
    .sidebar .active {
      background-color: #2e5e4d;
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
    .content {
      flex-grow: 1;
      padding: 20px;
    }

    /* Mobile Sidebar */
    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        height: 100%;
        z-index: 1000;
        transition: left 0.3s;
      }
      .sidebar.show {
        left: 0;
      }
      .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
      }
      .overlay.show {
        display: block;
      }
      .sidebar-toggler {
        display: block;
        background-color: #3d7b65;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        margin-bottom: 10px;
      }
    }
    @media (min-width: 769px) {
      .sidebar-toggler, .overlay {
        display: none;
      }
    }
  </style>
</head>
<body>

<div class="wrapper">
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="logo">
      <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
      Visitor Pass
    </div>
    <div><strong>Admin:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?></div>
    <hr class="border-light" />
    <a href="dashboard.php">Dashboard</a>
    <a href="data_blacklist.php">Data Blacklist</a>
    <a href="data_penghuni.php" class="active">Data Penghuni</a>
    <a href="data_tamu.php">Data Tamu</a>
    <a href="data_satpam.php">Data Satpam</a>
    <a href="akun_pengguna.php">Akun Pengguna</a>
    <a href="log_aktivitas.php">Log Aktivitas</a>
    <a href="notifikasi_template.php">Template Notifikasi</a>
    <a href="laporan.php">Laporan Kunjungan</a>
    <a href="statistik.php">Statistik</a>
    <a href="setting.php">Setting</a>
    <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
  </div>

  <!-- Overlay untuk mobile -->
  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

  <!-- Main content -->
  <div class="content">
    <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
    <h4 class="mb-4">Data Penghuni</h4>
    <a href="tambah_penghuni.php" class="btn btn-primary btn-sm mb-3">+ Tambah Penghuni</a>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-success">
          <tr>
            <th>Nama</th>
            <th>Unit</th>
            <th>No HP</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($penghuni): ?>
          <?php foreach ($penghuni as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($p['unit']) ?></td>
              <td><?= htmlspecialchars($p['no_hp']) ?></td>
              <td class="text-center">
                <a href="edit_penghuni.php?id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="hapus_penghuni.php?id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus penghuni ini?');">Hapus</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="text-center">Data penghuni belum ada.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>