<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

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

// Notifikasi ringkasan
$notif_izin_baru = $notif_blacklist = $notif_pesan = $notif_panic = 0;

$res1 = $conn->query("SELECT COUNT(*) AS cnt FROM izin_kunjungan_penghuni WHERE status = 'pending'");
if ($res1 && $row = $res1->fetch_assoc()) $notif_izin_baru = $row['cnt'];

$res2 = $conn->query("SELECT COUNT(*) AS cnt FROM blacklist WHERE aktif = 1");
if ($res2 && $row = $res2->fetch_assoc()) $notif_blacklist = $row['cnt'];

$res3 = $conn->query("SELECT COUNT(*) AS cnt FROM messages WHERE dibaca = 0 AND tujuan = 'admin'");
if ($res3 && $row = $res3->fetch_assoc()) $notif_pesan = $row['cnt'];

$res4 = $conn->query("SELECT COUNT(*) AS cnt FROM panic_logs WHERE status = 'pending'");
if ($res4 && $row = $res4->fetch_assoc()) $notif_panic = $row['cnt'];

$total_notif = $notif_izin_baru + $notif_blacklist + $notif_pesan + $notif_panic;
?>

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

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
  }
</script>
</body>
</html>