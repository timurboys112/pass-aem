<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

$today = date('Y-m-d');

// Statistik Kunjungan
$totalKunjungan = $conn->query("SELECT COUNT(*) AS total FROM kunjungan WHERE DATE(waktu_masuk) = '$today'")
                    ->fetch_assoc()['total'] ?? 0;

$pengunjungDiLokasi = $conn->query("SELECT COUNT(*) AS total FROM kunjungan WHERE waktu_keluar IS NULL")
                        ->fetch_assoc()['total'] ?? 0;

// Panic logs terbaru tanpa join ke tabel lain
$latest_panic_logs = [];
$sql_panic = "
  SELECT * FROM panic_logs
  WHERE status IN ('pending', 'diterima')
  ORDER BY time DESC
  LIMIT 10
";
$res_panic = $conn->query($sql_panic);
if ($res_panic) {
    $latest_panic_logs = $res_panic->fetch_all(MYSQLI_ASSOC);
} else {
    echo "SQL Error: " . $conn->error;
}

$notifikasi = $latest_panic_logs;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Front Office</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
    .sidebar { background-color: #3d7b65; min-height: 100vh; padding: 20px; color: #e0f0e6; width: 220px; position: fixed; top: 0; left: 0; overflow-y: auto; z-index: 1000; transition: left 0.3s ease; }
    .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; border-radius: 6px; }
    .sidebar a:hover, .sidebar a.active { background-color: #2e5e4d; }
    .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
    .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
    .sidebar-toggler { display: none; margin-bottom: 10px; background-color: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }
    .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
    .overlay.show { display: block; }
    .sidebar.show { left: 0; }
    main { margin-left: 220px; padding: 20px; }
    @media (max-width: 768px) {
      .sidebar { left: -250px; }
      .sidebar-toggler { display: block; }
      main { margin-left: 0; }
    }
    #map { height: 300px; border-radius: 10px; margin-top: 15px; }
  </style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <div class="logo">
    <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
    Visitor Pass FO
  </div>
  <div><strong>User:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
  <hr />
  <a href="dashboard.php" class="active">Dashboard</a>
  <a href="daftar_tamu.php">Daftar Tamu</a>
  <a href="form_checkin.php">Form Check-in</a>
  <a href="validasi_qr.php">Validasi QR</a>
  <a href="cek_blacklist.php">Cek Blacklist</a>
  <a href="/pass-aem/logout.php" class="text-danger">Logout</a>
</div>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<main>
  <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
  <h4 class="mb-4">Dashboard Front Office</h4>

  <!-- Statistik -->
  <div class="row mb-4">
    <div class="col-md-6 mb-3">
      <div class="card border-0 shadow-sm" style="background-color: #e6f4ef;">
        <div class="card-body">
          <h6 class="text-muted mb-1">Total Kunjungan Hari Ini</h6>
          <h4 class="fw-bold"><?= $totalKunjungan ?></h4>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card border-0 shadow-sm" style="background-color: #e6f4ef;">
        <div class="card-body">
          <h6 class="text-muted mb-1">Pengunjung Masih di Lokasi</h6>
          <h4 class="fw-bold"><?= $pengunjungDiLokasi ?></h4>
        </div>
      </div>
    </div>
  </div>

  <!-- Panic Logs -->
    <h5>Notifikasi Panic Button Terbaru</h5>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-danger">
          <tr>
            <th>User ID</th>
            <th>Lokasi</th>
            <th>Waktu</th>
            <th>Status</th>
            <th>Deskripsi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($latest_panic_logs) > 0): ?>
            <?php foreach ($latest_panic_logs as $log): ?>
              <tr>
                <td><?= htmlspecialchars($log['user_id']) ?></td>
                <td><?= htmlspecialchars($log['lokasi'] ?? '-') ?></td>
                <td><?= date('d-m-Y H:i', strtotime($log['time'])) ?></td>
                <td>
                  <?php if ($log['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($log['status'] === 'diterima'): ?>
                    <span class="badge bg-info text-dark">Diterima</span>
                  <?php else: ?>
                    <span class="badge bg-success">Selesai</span>
                  <?php endif; ?>
                </td>
                <td>
                <?= htmlspecialchars($log['description'] ?? '-') ?>
                <?php if ($log['status'] === 'pending'): ?>
                <form action="panic_action.php" method="POST" class="d-inline">
                  <input type="hidden" name="id" value="<?= $log['id'] ?>">
                  <input type="hidden" name="action" value="accept">
                  <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Terima laporan ini?')">Terima</button>
                </form>
                <form action="panic_action.php" method="POST" class="d-inline ms-1">
                  <input type="hidden" name="id" value="<?= $log['id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tolak laporan ini?')">Tolak</button>
                </form>
              <?php elseif ($log['status'] === 'diterima'): ?>
                <form action="panic_resolve.php" method="POST" class="mt-2">
                  <input type="hidden" name="id" value="<?= $log['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Tandai sebagai selesai?')">Selesai</button>
                </form>
              <?php endif; ?>
              </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted">Belum ada data panic button terbaru.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

  <!-- Peta -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title">Peta Lokasi Gedung</h5>
      <div id="map"></div>
    </div>
  </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
  }

  const map = L.map('map').setView([-6.1938, 106.8237], 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  L.marker([-6.1938, 106.8237]).addTo(map)
    .bindPopup("<b>AEM Building</b><br>Jl. Pegangsaan Barat No.Kav.6 -12, Menteng, Jakarta Pusat")
    .openPopup();
</script>
</body>
</html>