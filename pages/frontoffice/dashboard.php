<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = null;
if ($end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) $end_date = null;

$today = date('Y-m-d');

// Ambil 10 Kunjungan Terbaru, filter tanggal jika ada
$where_sql = "";
$params    = [];
$types     = "";

if ($start_date && $end_date) {
    $where_sql = " AND DATE(k.waktu_masuk) BETWEEN ? AND ?";
    $params = [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date];
    $types = "ssssss";
}

$sql = "
(
  SELECT 
    k.id, 
    t.nama_tamu, 
    k.waktu_masuk, 
    ikp.tujuan, 
    ikp.status,
    'penghuni' AS jenis
  FROM kunjungan k
  JOIN izin_kunjungan_penghuni ikp ON k.id_izin = ikp.id
  JOIN tamu t ON ikp.id_tamu = t.id
  WHERE k.jenis_izin = 'penghuni'
    {$where_sql}
)
UNION
(
  SELECT 
    k.id, 
    t.nama_tamu, 
    k.waktu_masuk, 
    ike.tujuan, 
    ike.status,
    'engineering' AS jenis
  FROM kunjungan k
  JOIN izin_kunjungan_engineering ike ON k.id_izin = ike.id
  JOIN tamu t ON ike.id_tamu = t.id
  WHERE k.jenis_izin = 'engineering'
    {$where_sql}
)
UNION
(
  SELECT 
    k.id, 
    tk.nama_pengantar AS nama_tamu, 
    k.waktu_masuk, 
    tk.tujuan_unit AS tujuan, 
    tk.status AS status,
    'kilat' AS jenis
  FROM kunjungan k
  JOIN tamu_kilat tk ON k.id_izin = tk.id
  WHERE k.jenis_izin = 'kilat'
    {$where_sql}
)
ORDER BY waktu_masuk DESC
LIMIT 10
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$kunjunganTerbaru = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Front Office</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    crossorigin=""
  />
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
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
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
    #map {
      height: 300px;
      border-radius: 10px;
      margin-top: 15px;
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

  <!-- Filter tanggal -->
  <form method="GET" class="mb-4">
    <div class="row g-2">
      <div class="col-md-3 col-sm-6">
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date ?? '') ?>" required />
      </div>
      <div class="col-md-3 col-sm-6">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date ?? '') ?>" required />
      </div>
      <div class="col-md-3 col-sm-12">
        <button type="submit" class="btn btn-success w-100">Filter</button>
      </div>
      <div class="col-md-3 col-sm-12">
        <button type="button" class="btn btn-secondary w-100" onclick="clearFilter()">Reset Filter</button>
      </div>
    </div>
  </form>

  <!-- Daftar Kunjungan Terbaru -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title">Kunjungan Terbaru</h5>
      <div class="table-responsive">
        <table id="tabel-kunjungan" class="table table-striped align-middle" style="width:100%">
          <thead>
            <tr>
              <th>Nama Tamu</th>
              <th>Waktu Masuk</th>
              <th>Tujuan</th>
              <th>Status</th>
              <th>Jenis</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kunjunganTerbaru as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['nama_tamu']) ?></td>
              <td><?= htmlspecialchars(date('d M Y H:i', strtotime($row['waktu_masuk']))) ?></td>
              <td><?= htmlspecialchars($row['tujuan']) ?></td>
              <td>
                <?php
                  $status = $row['status'];
                  $badgeClass = 'secondary';
                  if (in_array($status, ['selesai', 'disetujui'])) $badgeClass = 'success';
                  elseif ($status == 'menunggu') $badgeClass = 'warning';
                  elseif ($status == 'ditolak') $badgeClass = 'danger';
                ?>
                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
              </td>
              <td><?= ucfirst($row['jenis']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Peta Lokasi Gedung -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title">Peta Lokasi Gedung</h5>
      <div id="map"></div>
    </div>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  crossorigin=""
></script>

<script>
  $(document).ready(function () {
    $('#tabel-kunjungan').DataTable({
      order: [[1, 'desc']],
      pageLength: 10,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/Indonesian.json'
      }
    });
  });

  // Sidebar toggle for small devices
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  }

  function clearFilter() {
    window.location.href = 'dashboard.php';
  }

  // Filter tabel berdasarkan status kartu diklik
  function filterTable(status) {
    let table = $('#tabel-kunjungan').DataTable();
    if (status === 'all') {
      table.search('').draw();
    } else {
      table.columns(3).search(status, true, false).draw();
    }
  }

  // Inisialisasi peta Leaflet
  const map = L.map('map').setView([-6.1938, 106.8237], 16);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Marker lokasi gedung dengan alamat terbaru
  const marker = L.marker([-6.1938, 106.8237]).addTo(map);
  marker.bindPopup("<b>AEM Building</b><br>Jl. Pegangsaan Barat No.Kav.6 -12, RT.16/RW.5, Menteng, Kec. Menteng, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10320").openPopup();
</script>

</body>
</html>