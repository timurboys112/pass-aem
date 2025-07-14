<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

// Ambil data tamu biasa
$sqlTamu = "SELECT 
    id,
    nama_tamu AS nama,
    status,
    waktu_checkin AS waktu_masuk,
    jenis_kelamin,
    no_hp,
    alamat,
    tujuan AS unit
FROM tamu";

// Ambil data tamu kurir
$sqlKurir = "SELECT 
    id,
    nama_pengantar AS nama,
    'Kurir' AS status,
    waktu_masuk,
    NULL AS jenis_kelamin,
    NULL AS no_hp,
    NULL AS alamat,
    tujuan_unit AS unit
FROM tamu_kilat";

// Gabung data
$tamuList = [];
$resultTamu = $conn->query($sqlTamu);
if ($resultTamu && $resultTamu->num_rows > 0) {
    while ($row = $resultTamu->fetch_assoc()) {
        $tamuList[] = $row;
    }
}
$resultKurir = $conn->query($sqlKurir);
if ($resultKurir && $resultKurir->num_rows > 0) {
    while ($row = $resultKurir->fetch_assoc()) {
        $tamuList[] = $row;
    }
}
usort($tamuList, function($a, $b) {
    return strtotime($b['waktu_masuk']) <=> strtotime($a['waktu_masuk']);
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Data Tamu Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
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
      transition: transform 0.3s ease-in-out;
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
    .main-content {
      margin-left: 220px;
      padding: 20px;
      transition: margin-left 0.3s ease-in-out;
    }
    .sidebar-toggler {
      display: none;
      background: #3d7b65;
      color: #fff;
      border: none;
      padding: 10px 15px;
      font-size: 18px;
      border-radius: 6px;
      margin-bottom: 15px;
    }
    .overlay {
      display: none;
    }
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        position: fixed;
        width: 220px;
      }
      .sidebar.show {
        transform: translateX(0);
      }
      .main-content {
        margin-left: 0;
      }
      .sidebar-toggler {
        display: inline-block;
      }
      .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 100vw;
        background-color: rgba(0, 0, 0, 0.3);
        z-index: 999;
      }
      .overlay.show {
        display: block;
      }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="logo">
    <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo">
    Visitor Pass
  </div>
  <div><strong>Admin:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?></div>
  <hr class="border-light">
  <a href="dashboard.php">Dashboard</a>
  <a href="data_blacklist.php">Data Blacklist</a>
  <a href="data_penghuni.php">Data Penghuni</a>
  <a href="data_tamu.php" class="active">Data Tamu</a>
  <a href="data_satpam.php">Data Satpam</a>
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
<div class="main-content">
  <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
  <h4 class="mb-4">Data Tamu</h4>

  <div class="mb-3 row g-2">
    <div class="col-md-4">
      <input type="text" id="searchInput" class="form-control" placeholder="Cari nama tamu...">
    </div>
    <div class="col-md-3">
      <select id="statusFilter" class="form-select">
        <option value="">Semua Status</option>
        <option value="Check-In">Check-In</option>
        <option value="Check-Out">Check-Out</option>
        <option value="Blacklist">Blacklist</option>
        <option value="Diizinkan">Diizinkan</option>
        <option value="Ditolak">Ditolak</option>
        <option value="Pending">Pending</option>
        <option value="Menunggu di Lobby">Menunggu di Lobby</option>
      </select>
    </div>
    <div class="col-md-5 text-end">
      <button onclick="exportExcel()" class="btn btn-success btn-sm">Export ke Excel</button>
      <button onclick="window.print()" class="btn btn-secondary btn-sm">Print PDF</button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-striped" id="tamuTable">
      <thead class="table-light">
        <tr>
          <th>Nama</th>
          <th>Unit</th>
          <th>Jenis Kelamin</th>
          <th>No HP</th>
          <th>Alamat</th>
          <th>Status</th>
          <th>Check-In</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($tamuList) > 0): ?>
          <?php foreach ($tamuList as $tamu): ?>
            <tr>
              <td><?= htmlspecialchars($tamu['nama']) ?></td>
              <td><?= htmlspecialchars($tamu['unit']) ?></td>
              <td><?= htmlspecialchars($tamu['jenis_kelamin']) ?></td>
              <td><?= htmlspecialchars($tamu['no_hp']) ?></td>
              <td><?= htmlspecialchars($tamu['alamat']) ?></td>
              <td><?= htmlspecialchars($tamu['status']) ?></td>
              <td><?= htmlspecialchars($tamu['waktu_masuk']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center">Belum ada data tamu</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const rows = document.querySelectorAll("#tamuTable tbody tr");

  function applyFilter() {
    const keyword = searchInput.value.toLowerCase();
    const statusVal = statusFilter.value.toLowerCase();
    rows.forEach(row => {
      const nama = row.cells[0].textContent.toLowerCase();
      const status = row.cells[5].textContent.toLowerCase().trim();
      const matchesNama = nama.includes(keyword);
      const matchesStatus = statusVal === "" || status === statusVal;
      row.style.display = matchesNama && matchesStatus ? "" : "none";
    });
  }

  searchInput.addEventListener('input', applyFilter);
  statusFilter.addEventListener('change', applyFilter);

  function exportExcel() {
    const table = document.getElementById("tamuTable");
    const wb = XLSX.utils.table_to_book(table, { sheet: "DataTamu" });
    XLSX.writeFile(wb, "data_tamu.xlsx");
  }

  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  }

  document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) toggleSidebar();
    });
  });
</script>

</body>
</html>