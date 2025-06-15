<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

// Ambil data dari database
$stmt = $conn->prepare("SELECT id, username, nama_lengkap, shift, email, no_hp FROM satpam_users ORDER BY nama_lengkap ASC");
if (!$stmt) {
    die("Prepare gagal: (" . $conn->errno . ") " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Satpam</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
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
    }
    .sidebar a:hover, .sidebar .active {
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
    .sidebar-toggler { display: none; }
    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        top: 0; left: -250px;
        transition: left 0.3s ease;
        z-index: 999;
      }
      .sidebar.show { left: 0; }
      .sidebar-toggler {
        display: block;
        margin: 10px;
        background-color: #3d7b65;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
      }
      .overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 998;
      }
      .overlay.show { display: block; }
    }
  </style>
</head>
<body>

<div class="d-flex">
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
    <a href="data_penghuni.php">Data Penghuni</a>
    <a href="data_tamu.php">Data Tamu</a>
    <a href="data_satpam.php" class="active">Data Satpam</a>
    <a href="akun_pengguna.php">Akun Pengguna</a>
    <a href="log_aktivitas.php">Log Aktivitas</a>
    <a href="notifikasi_template.php">Template Notifikasi</a>
    <a href="laporan.php">Laporan Kunjungan</a>
    <a href="statistik.php">Statistik</a>
    <a href="setting.php">Setting</a>
    <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
  </div>

  <!-- Overlay -->
  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

  <!-- Main Content -->
  <div class="flex-grow-1 p-4">
    <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
    <h4 class="mb-4">Data Satpam</h4>

    <a href="tambah_satpam.php" class="btn btn-primary btn-sm mb-3">+ Tambah Satpam</a>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-success">
          <tr>
            <th>Username</th>
            <th>Nama</th>
            <th>Shift</th>
            <th>No HP</th>
            <th>Email</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($row['shift']) ?></td>
              <td><?= htmlspecialchars($row['no_hp']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td class="text-center">
                <a href="edit_satpam.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="hapus_satpam.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin hapus satpam ini?')" class="btn btn-sm btn-danger">Hapus</a>
              </td>
            </tr>
          <?php endwhile; ?>
          <?php
            $stmt->close();
            $conn->close();
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("show");
  document.getElementById("overlay").classList.toggle("show");
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>