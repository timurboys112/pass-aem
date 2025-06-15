<?php
require_once '../../includes/auth.php';
checkRole(['admin']);

// --- Contoh data dummy (ganti dengan query DB kamu) ---

// Statistik ringkasan
$stats = [
    'kunjungan_masuk' => 45,
    'kunjungan_keluar' => 30,
    'tamu_aktif' => 120,
    'tamu_blacklist' => 7,
    'penghuni_aktif' => 55,
    'kunjungan_pending' => 5,
    'scan_qr_hari_ini' => 60,
    'server_uptime' => '12 hari 5 jam',
    'error_logs_today' => 2,
];

// Data grafik mingguan (kunjungan per hari, Senin - Minggu)
$grafik_mingguan = [
    'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    'masuk' => [10, 8, 12, 14, 15, 7, 9],
    'keluar' => [8, 7, 11, 10, 12, 5, 7],
];

// Grafik jenis tamu
$jenis_tamu = [
    'labels' => ['Visitor', 'VIP', 'Biasa'],
    'data' => [80, 20, 100],
];

// Grafik blacklist vs tamu biasa
$blacklist_vs_biasa = [
    'labels' => ['Blacklist', 'Tamu Biasa'],
    'data' => [7, 193],
];

// Grafik jam sibuk (kunjungan per jam)
$jam_sibuk = [
    'labels' => ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
    'data' => [5, 10, 15, 20, 25, 18, 12, 10, 8, 5],
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Statistik Kunjungan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
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
    .sidebar-toggler {
      display: none;
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
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 998;
      }
      .overlay.show {
        display: block;
      }
    }

    /* Grafik agar responsif dan lebih kecil */
    canvas {
      max-width: 100% !important;
      height: 180px !important;
    }

    /* Tambahan agar card-body tidak terlalu sempit di mobile */
    .card-body {
      padding: 1rem;
      overflow-x: auto;
    }

    /* Tambahan style untuk print */
    @media print {
      body {
        background-color: white !important;
      }
      .sidebar, .sidebar-toggler, .overlay, button {
        display: none !important;
      }
      .flex-grow-1 {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
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
    <a href="data_satpam.php">Data Satpam</a>
    <a href="akun_pengguna.php">Akun Pengguna</a>
    <a href="log_aktivitas.php">Log Aktivitas</a>
    <a href="notifikasi_template.php">Template Notifikasi</a>
    <a href="laporan.php">Laporan Kunjungan</a>
    <a href="statistik.php" class="active">Statistik</a>
    <a href="setting.php">Setting</a>
    <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
  </div>

  <!-- Overlay -->
  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

  <!-- Main Content -->
  <div class="flex-grow-1 p-4">
    <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
    
    <!-- Tombol Cetak -->
    <button class="btn btn-outline-primary mb-3" onclick="window.print()">
      🖨️ Cetak Statistik
    </button>

    <h4 class="mb-4">Statistik & Ringkasan Cepat</h4>

    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
          <div class="card-body">
            <h5 class="card-title">Kunjungan Masuk Hari Ini</h5>
            <p class="card-text fs-3"><?= $stats['kunjungan_masuk'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-secondary">
          <div class="card-body">
            <h5 class="card-title">Kunjungan Keluar Hari Ini</h5>
            <p class="card-text fs-3"><?= $stats['kunjungan_keluar'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
          <div class="card-body">
            <h5 class="card-title">Tamu Terdaftar Aktif</h5>
            <p class="card-text fs-3"><?= $stats['tamu_aktif'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-danger">
          <div class="card-body">
            <h5 class="card-title">Tamu Blacklist</h5>
            <p class="card-text fs-3"><?= $stats['tamu_blacklist'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
          <div class="card-body">
            <h5 class="card-title">Penghuni Aktif</h5>
            <p class="card-text fs-3"><?= $stats['penghuni_aktif'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
          <div class="card-body">
            <h5 class="card-title">Kunjungan Menunggu Persetujuan</h5>
            <p class="card-text fs-3"><?= $stats['kunjungan_pending'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-secondary">
          <div class="card-body">
            <h5 class="card-title">Scan QR Hari Ini</h5>
            <p class="card-text fs-3"><?= $stats['scan_qr_hari_ini'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-white bg-dark">
          <div class="card-body">
            <h5 class="card-title">Server Uptime</h5>
            <p class="card-text fs-3"><?= $stats['server_uptime'] ?></p>
          </div>
        </div>
      </div>
    </div>

    <h4 class="mb-4">Grafik Statistik Kunjungan</h4>

    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <canvas id="chartMingguan" style="max-width: 100%; height: 180px;"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <canvas id="chartJenisTamu" style="max-width: 100%; height: 180px;"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <canvas id="chartBlacklist" style="max-width: 100%; height: 180px;"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <canvas id="chartJamSibuk" style="max-width: 100%; height: 180px;"></canvas>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  // Sidebar toggle for mobile
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
  }

  // Chart Mingguan
  const ctxMingguan = document.getElementById('chartMingguan').getContext('2d');
  const chartMingguan = new Chart(ctxMingguan, {
    type: 'line',
    data: {
      labels: <?= json_encode($grafik_mingguan['labels']) ?>,
      datasets: [
        {
          label: 'Kunjungan Masuk',
          data: <?= json_encode($grafik_mingguan['masuk']) ?>,
          borderColor: '#28a745',
          backgroundColor: 'rgba(40, 167, 69, 0.3)',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 6,
        },
        {
          label: 'Kunjungan Keluar',
          data: <?= json_encode($grafik_mingguan['keluar']) ?>,
          borderColor: '#dc3545',
          backgroundColor: 'rgba(220, 53, 69, 0.3)',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 6,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true }
      },
      plugins: {
        legend: { position: 'top' }
      }
    }
  });

  // Chart Jenis Tamu (Pie)
  const ctxJenisTamu = document.getElementById('chartJenisTamu').getContext('2d');
  const chartJenisTamu = new Chart(ctxJenisTamu, {
    type: 'pie',
    data: {
      labels: <?= json_encode($jenis_tamu['labels']) ?>,
      datasets: [{
        data: <?= json_encode($jenis_tamu['data']) ?>,
        backgroundColor: ['#007bff', '#ffc107', '#6c757d'],
        borderColor: '#fff',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });

  // Chart Blacklist vs Tamu Biasa (Doughnut)
  const ctxBlacklist = document.getElementById('chartBlacklist').getContext('2d');
  const chartBlacklist = new Chart(ctxBlacklist, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($blacklist_vs_biasa['labels']) ?>,
      datasets: [{
        data: <?= json_encode($blacklist_vs_biasa['data']) ?>,
        backgroundColor: ['#dc3545', '#28a745'],
        borderColor: '#fff',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });

  // Chart Jam Sibuk (Bar)
  const ctxJamSibuk = document.getElementById('chartJamSibuk').getContext('2d');
  const chartJamSibuk = new Chart(ctxJamSibuk, {
    type: 'bar',
    data: {
      labels: <?= json_encode($jam_sibuk['labels']) ?>,
      datasets: [{
        label: 'Jumlah Kunjungan',
        data: <?= json_encode($jam_sibuk['data']) ?>,
        backgroundColor: '#17a2b8'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
</script>
</body>
</html>