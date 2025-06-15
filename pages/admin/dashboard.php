<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

// Ambil filter dari GET
$tgl_dari = $_GET['tgl_dari'] ?? '';
$tgl_sampai = $_GET['tgl_sampai'] ?? '';
$tujuan_filter = $_GET['tujuan'] ?? '';

// Siapkan filter
$where = [];
$params = [];

if ($tgl_dari) {
    $where[] = "k.waktu_masuk >= ?";
    $params[] = $tgl_dari . " 00:00:00";
}
if ($tgl_sampai) {
    $where[] = "k.waktu_masuk <= ?";
    $params[] = $tgl_sampai . " 23:59:59";
}
if ($tujuan_filter) {
    $where[] = "(
        COALESCE(iz_p.tujuan, iz_e.tujuan, tk.tujuan_unit) LIKE ?
    )";
    $params[] = "%$tujuan_filter%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Query utama dengan filter
$sql = "
    SELECT 
      k.id,
      k.jenis_izin,
      CASE 
        WHEN k.jenis_izin IN ('penghuni', 'engineering') THEN t.nama_tamu
        WHEN k.jenis_izin = 'kilat' THEN tk.nama_pengantar
        ELSE 'Tidak diketahui'
      END AS nama_tamu,
      k.waktu_masuk,
      CASE 
        WHEN k.jenis_izin = 'penghuni' THEN iz_p.tujuan
        WHEN k.jenis_izin = 'engineering' THEN iz_e.tujuan
        WHEN k.jenis_izin = 'kilat' THEN tk.tujuan_unit
        ELSE '-'
      END AS tujuan,
      CASE 
        WHEN k.status = 'masuk' THEN 'Masuk'
        WHEN k.status = 'keluar' THEN 'Keluar'
        ELSE 'Pending'
      END AS status
    FROM kunjungan k
    LEFT JOIN izin_kunjungan_penghuni iz_p 
      ON iz_p.id = k.id_izin AND k.jenis_izin = 'penghuni'
    LEFT JOIN izin_kunjungan_engineering iz_e 
      ON iz_e.id = k.id_izin AND k.jenis_izin = 'engineering'
    LEFT JOIN tamu t 
      ON (
        (k.jenis_izin = 'penghuni' AND t.id = iz_p.id_tamu) OR 
        (k.jenis_izin = 'engineering' AND t.id = iz_e.id_tamu)
      )
    LEFT JOIN tamu_kilat tk 
      ON k.jenis_izin = 'kilat' 
         AND DATE_FORMAT(tk.waktu_masuk, '%Y-%m-%d %H:%i') = DATE_FORMAT(k.waktu_masuk, '%Y-%m-%d %H:%i')
    $whereSql
    ORDER BY k.waktu_masuk DESC
";

$result = null;
if ($tgl_dari || $tgl_sampai || $tujuan_filter) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
}

// Kunjungan terbaru
$latest_visits = [];
$sql_latest = "
    SELECT 
      k.id,
      k.jenis_izin,
      CASE 
        WHEN k.jenis_izin IN ('penghuni', 'engineering') THEN t.nama_tamu
        WHEN k.jenis_izin = 'kilat' THEN tk.nama_pengantar
        ELSE 'Tidak diketahui'
      END AS nama_tamu,
      k.waktu_masuk,
      CASE 
        WHEN k.jenis_izin = 'penghuni' THEN iz_p.tujuan
        WHEN k.jenis_izin = 'engineering' THEN iz_e.tujuan
        WHEN k.jenis_izin = 'kilat' THEN tk.tujuan_unit
        ELSE '-'
      END AS tujuan,
      CASE 
        WHEN k.status = 'masuk' THEN 'Masuk'
        WHEN k.status = 'keluar' THEN 'Keluar'
        ELSE 'Pending'
      END AS status
    FROM kunjungan k
    LEFT JOIN izin_kunjungan_penghuni iz_p 
      ON iz_p.id = k.id_izin AND k.jenis_izin = 'penghuni'
    LEFT JOIN izin_kunjungan_engineering iz_e 
      ON iz_e.id = k.id_izin AND k.jenis_izin = 'engineering'
    LEFT JOIN tamu t 
      ON (
        (k.jenis_izin = 'penghuni' AND t.id = iz_p.id_tamu) OR 
        (k.jenis_izin = 'engineering' AND t.id = iz_e.id_tamu)
      )
    LEFT JOIN tamu_kilat tk 
      ON k.jenis_izin = 'kilat' 
         AND DATE_FORMAT(tk.waktu_masuk, '%Y-%m-%d %H:%i') = DATE_FORMAT(k.waktu_masuk, '%Y-%m-%d %H:%i')
    ORDER BY k.waktu_masuk DESC
    LIMIT 10
";

$res_latest = $conn->query($sql_latest);
if ($res_latest) {
    while ($row = $res_latest->fetch_assoc()) {
        $latest_visits[] = $row;
    }
}

// Notifikasi dengan pengecekan fetch_assoc
$notif_izin_baru = 0;
$res1 = $conn->query("SELECT COUNT(*) AS cnt FROM izin_kunjungan_penghuni WHERE status = 'pending'");
if ($res1 && $row = $res1->fetch_assoc()) {
    $notif_izin_baru = $row['cnt'];
}

$notif_blacklist = 0;
$res2 = $conn->query("SELECT COUNT(*) AS cnt FROM blacklist WHERE aktif = 1");
if ($res2 && $row = $res2->fetch_assoc()) {
    $notif_blacklist = $row['cnt'];
}

$notif_pesan = 0;
$res3 = $conn->query("SELECT COUNT(*) AS cnt FROM messages WHERE dibaca = 0 AND tujuan = 'admin'");
if ($res3 && $row = $res3->fetch_assoc()) {
    $notif_pesan = $row['cnt'];
}

$notif_panic = 0;
$res4 = $conn->query("SELECT COUNT(*) AS cnt FROM panic_logs WHERE status = 'pending'");
if ($res4 && $row = $res4->fetch_assoc()) {
    $notif_panic = $row['cnt'];
}

$total_notif = $notif_izin_baru + $notif_blacklist + $notif_pesan + $notif_panic;
?>

<!-- HTML lanjutannya (sidebar + table + notifikasi) tetap pakai template kamu sebelumnya -->
<!-- Silakan paste bagian HTML-nya dari file yang sudah kamu punya -->

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      background-color: #3d7b65;
      min-height: 100vh;
      color: white;
      padding: 20px;
      width: 220px;
    }
    .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; }
    .sidebar a:hover, .sidebar .active { background-color: #2e5e4d; border-radius: 6px; }
    .badge-notif {
      background-color: red;
      color: white;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 10px;
      position: absolute;
      top: 5px;
      right: 0;
    }
    .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
    .sidebar-toggler { display: none; }
    @media (max-width: 768px) {
      .sidebar { position: fixed; left: -250px; transition: left 0.3s ease; z-index: 999; }
      .sidebar.show { left: 0; }
      .overlay { display: none; position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; }
      .overlay.show { display: block; }
      .sidebar-toggler { display: block; background-color: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; }
    }
  </style>
</head>
<body>
<div class="d-flex">
  <div class="sidebar" id="sidebar">
    <div class="logo d-flex align-items-center mb-4">
      <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo"> Visitor Pass
    </div>
    <div><strong>Admin:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?></div>
    <hr class="border-light">
    <a href="dashboard.php" class="active">Dashboard<?php if ($total_notif): ?><span class="badge-notif"><?= $total_notif ?></span><?php endif; ?></a>
    <a href="data_blacklist.php">Data Blacklist</a>
    <a href="data_penghuni.php">Data Penghuni</a>
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

  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

  <div class="flex-grow-1 p-4">
    <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
    <h4 class="mb-4">Dashboard Admin</h4>

    <!-- Ringkasan Notifikasi -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card text-bg-warning shadow-sm">
          <div class="card-body">
            <h6 class="card-title">Izin Pending</h6>
            <p class="card-text fs-4 fw-bold"><?= $notif_izin_baru ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-bg-danger shadow-sm">
          <div class="card-body">
            <h6 class="card-title">Blacklist Aktif</h6>
            <p class="card-text fs-4 fw-bold"><?= $notif_blacklist ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-bg-primary shadow-sm">
          <div class="card-body">
            <h6 class="card-title">Pesan Belum Dibaca</h6>
            <p class="card-text fs-4 fw-bold"><?= $notif_pesan ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-bg-danger shadow">
          <div class="card-body">
            <h6 class="card-title">Panic Button</h6>
            <p class="card-text fs-4 fw-bold"><?= $notif_panic ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <form method="get" class="row g-3 mb-3 align-items-end">
      <div class="col-md-3">
        <label for="tgl_dari" class="form-label">Tanggal Dari</label>
        <input type="date" id="tgl_dari" name="tgl_dari" class="form-control" value="<?= htmlspecialchars($tgl_dari) ?>" />
      </div>
      <div class="col-md-3">
        <label for="tgl_sampai" class="form-label">Tanggal Sampai</label>
        <input type="date" id="tgl_sampai" name="tgl_sampai" class="form-control" value="<?= htmlspecialchars($tgl_sampai) ?>" />
      </div>
      <div class="col-md-3">
        <label for="tujuan" class="form-label">Tujuan</label>
        <input type="text" id="tujuan" name="tujuan" class="form-control" value="<?= htmlspecialchars($tujuan_filter) ?>" />
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-success w-100">Filter</button>
      </div>
    </form>

    <!-- Hasil Filter -->
    <?php if ($result): ?>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-success">
          <tr><th>ID</th><th>Jenis Izin</th><th>Nama Tamu</th><th>Waktu Masuk</th><th>Tujuan</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= ucfirst($row['jenis_izin']) ?></td>
            <td><?= $row['nama_tamu'] ?></td>
            <td><?= $row['waktu_masuk'] ?></td>
            <td><?= $row['tujuan'] ?></td>
            <td>
              <?php if ($row['status'] == 'masuk'): ?>
                <span class="badge bg-success">Masuk</span>
              <?php elseif ($row['status'] == 'keluar'): ?>
                <span class="badge bg-secondary">Keluar</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <div class="alert alert-info">Gunakan filter di atas untuk mencari data kunjungan.</div>
    <?php endif; ?>

    <h5>Daftar Kunjungan Terbaru</h5>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
          <tr><th>ID</th><th>Jenis Izin</th><th>Nama Tamu</th><th>Waktu Masuk</th><th>Tujuan</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($latest_visits as $visit): ?>
          <tr>
            <td><?= $visit['id'] ?></td>
            <td><?= ucfirst($visit['jenis_izin']) ?></td>
            <td><?= $visit['nama_tamu'] ?></td>
            <td><?= $visit['waktu_masuk'] ?></td>
            <td><?= $visit['tujuan'] ?></td>
            <td>
              <?php if ($visit['status'] == 'masuk'): ?>
                <span class="badge bg-success">Masuk</span>
              <?php elseif ($visit['status'] == 'keluar'): ?>
                <span class="badge bg-secondary">Keluar</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
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
</body>
</html>