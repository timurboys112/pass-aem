<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';
$printType = $_GET['print_type'] ?? '';

$tamuList = [];
$filterApplied = $fromDate && $toDate;

if ($filterApplied) {
    // NOTE: Untuk keamanan, sebaiknya gunakan prepared statements di sini
    $sqlTamu = "
        SELECT 
            id,
            nama_tamu AS nama,
            status,
            waktu_checkin AS waktu_masuk,
            jenis_kelamin,
            no_hp,
            alamat,
            tujuan AS unit
        FROM tamu
        WHERE DATE(waktu_checkin) BETWEEN '$fromDate' AND '$toDate'
    ";

    $sqlKurir = "
        SELECT 
            id,
            nama_pengantar AS nama,
            'Kurir' AS status,
            waktu_masuk,
            NULL AS jenis_kelamin,
            NULL AS no_hp,
            NULL AS alamat,
            tujuan_unit AS unit
        FROM tamu_kilat
        WHERE DATE(waktu_masuk) BETWEEN '$fromDate' AND '$toDate'
    ";

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
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Laporan Kunjungan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; }
    .sidebar { background-color: #3d7b65; min-height: 100vh; padding: 20px; color: #e0f0e6; width: 220px; position: fixed; top: 0; left: 0; transition: transform 0.3s ease; z-index: 1000; }
    .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; border-radius: 6px; }
    .sidebar a:hover, .sidebar a.active { background-color: #2e5e4d; color: white; }
    .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
    .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
    main { margin-left: 220px; padding: 20px; }

    .sidebar-toggler { background: #3d7b65; color: white; border: none; font-size: 20px; padding: 6px 12px; border-radius: 5px; margin-bottom: 15px; }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); position: fixed; }
      .sidebar.show { transform: translateX(0); }
      main { margin-left: 0; }
      .overlay { display: block; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 999; }
    }

    .overlay { display: none; }
  </style>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
    }
  </script>
</head>
<body>

<div class="d-flex">
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
    <a href="data_tamu.php">Data Tamu</a>
    <a href="data_satpam.php">Data Satpam</a>
    <a href="akun_pengguna.php">Akun Pengguna</a>
    <a href="log_aktivitas.php">Log Aktivitas</a>
    <a href="notifikasi_template.php">Template Notifikasi</a>
    <a href="laporan.php" class="active">Laporan Kunjungan</a>
    <a href="statistik.php">Statistik</a>
    <a href="setting.php">Setting</a>
    <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
  </div>

  <!-- Overlay untuk Mobile -->
  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

  <!-- Main Content -->
  <main class="flex-grow-1 p-4">
    <button class="sidebar-toggler d-md-none" onclick="toggleSidebar()">☰ Menu</button>
    <h4 class="mb-4">Laporan Kunjungan</h4>

    <form method="GET" class="row g-3 align-items-end mb-4">
      <div class="col-md-3">
        <label for="fromDate" class="form-label">Tanggal Dari</label>
        <input type="date" name="from" id="fromDate" class="form-control" required value="<?= htmlspecialchars($fromDate) ?>" />
      </div>
      <div class="col-md-3">
        <label for="toDate" class="form-label">Tanggal Sampai</label>
        <input type="date" name="to" id="toDate" class="form-control" required value="<?= htmlspecialchars($toDate) ?>" />
      </div>
      <div class="col-md-3">
        <label for="printType" class="form-label">Jenis Print</label>
        <select name="print_type" id="printType" class="form-select" required>
          <option value="">Pilih...</option>
          <option value="harian" <?= $printType === 'harian' ? 'selected' : '' ?>>Harian</option>
          <option value="mingguan" <?= $printType === 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
          <option value="bulanan" <?= $printType === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
          <option value="tahunan" <?= $printType === 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">Filter & Tampilkan</button>
      </div>
    </form>

    <?php if ($filterApplied): ?>
      <div class="mb-3">
        <button onclick="window.print()" class="btn btn-secondary">Print Laporan</button>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-light">
            <tr>
              <th>Nama</th>
              <th>Unit</th>
              <th>Jenis Kelamin</th>
              <th>No HP</th>
              <th>Alamat</th>
              <th>Status</th>
              <th>Tipe Tamu</th>
              <th>Waktu Masuk</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($tamuList) > 0): ?>
              <?php foreach ($tamuList as $tamu): ?>
                <tr>
                  <td><?= htmlspecialchars($tamu['nama']) ?></td>
                  <td><?= htmlspecialchars($tamu['unit']) ?></td>
                  <td><?= htmlspecialchars($tamu['jenis_kelamin'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($tamu['no_hp'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($tamu['alamat'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($tamu['status']) ?></td>
                  <td><?= htmlspecialchars($tamu['status'] === 'Kurir' ? 'Kurir' : 'Tamu Biasa') ?></td>
                  <td><?= htmlspecialchars($tamu['waktu_masuk']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center">Tidak ada data kunjungan sesuai filter</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Silakan pilih tanggal dari dan sampai serta jenis print lalu klik Filter untuk melihat laporan.</div>
    <?php endif; ?>
  </main>
</div>

</body>
</html>