<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';
include '../../includes/get_notifications.php';

// Ambil data tamu biasa (dari tabel tamu)
$sqlTamu = "SELECT 
    id,
    nama_tamu AS nama,
    status,
    waktu_checkin AS waktu_masuk,
    jenis_kelamin,
    no_hp,
    alamat,
    tujuan AS unit -- Ganti kalau ada kolom unit asli
FROM tamu";

// Ambil data tamu kurir dari tabel tamu_kilat
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

// Eksekusi query tamu biasa
$tamuList = [];
$resultTamu = $conn->query($sqlTamu);
if ($resultTamu && $resultTamu->num_rows > 0) {
    while ($row = $resultTamu->fetch_assoc()) {
        $tamuList[] = $row;
    }
}

// Eksekusi query tamu kurir
$resultKurir = $conn->query($sqlKurir);
if ($resultKurir && $resultKurir->num_rows > 0) {
    while ($row = $resultKurir->fetch_assoc()) {
        $tamuList[] = $row;
    }
}

// Urutkan gabungan data berdasarkan waktu_masuk DESC
usort($tamuList, function($a, $b) {
    return strtotime($b['waktu_masuk']) <=> strtotime($a['waktu_masuk']);
});

// Penentuan shift (berdasarkan jam)
date_default_timezone_set('Asia/Jakarta');
$jamSekarang = (int) date('H');
$shift = 'Malam';
if ($jamSekarang >= 6 && $jamSekarang < 11) {
    $shift = 'Pagi';
} elseif ($jamSekarang >= 11 && $jamSekarang < 17) {
    $shift = 'Siang';
} elseif ($jamSekarang >= 17 && $jamSekarang < 24) {
    $shift = 'Malam';
};

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
    <title>Dashboard Satpam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #2e7d32;
            min-height: 100vh;
            padding: 20px;
            color: white;
            width: 220px;
        }
        .sidebar a {
            color: #c8e6c9;
            text-decoration: none;
            display: block;
            padding: 10px 0;
            font-weight: 500;
        }
        .sidebar a:hover {
            background-color: #1b5e20;
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
                width: 220px;
                transition: left 0.3s ease;
                z-index: 999;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggler {
                display: block;
                margin: 10px;
                background-color: #2e7d32;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
                font-weight: bold;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }
            .overlay.show {
                display: block;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass
        </div>
        <div><strong>Satpam:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="scan.php">Scan QR</a>
        <a href="validasi_manual.php">Validasi Manual</a>
        <a href="form_kurir.php">Check-In Kurir</a>
        <a href="daftar_masuk.php">Daftar Masuk</a>
        <a href="daftar_keluar.php">Daftar Keluar</a>
        <a href="tamu_di_lokasi.php">Tamu di Lokasi</a>
        <a href="laporan_harian.php">Laporan Harian</a>
        <a href="riwayat_scan.php">Riwayat Scan</a>
        <a href="catatan_shift.php">Catatan Shift</a>
        <a href="daftar_blacklist.php">Daftar Blacklist</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </nav>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

        <h4 class="mb-4">Dashboard Satpam</h4>

        <!-- Pengingat Shift -->
        <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong>Shift Saat Ini:</strong> <?= $shift ?> (<?= date('H:i') ?> WIB)
            </div>
            <div>
                <small class="text-muted">Tanggal: <?= date('d M Y') ?></small>
            </div>
        </div>
        
        <!-- Filter (Optional) -->
<form class="mb-3 row g-2">
    <div class="col-md-6">
      <input type="text" id="searchInput" class="form-control" placeholder="Cari nama tamu...">
    </div>
    <div class="col-md-4">
      <select id="statusFilter" class="form-select">
        <option value="">Semua Status</option>
        <option>Check-In</option>
        <option>Check-Out</option>
        <option>Pending</option>
        <option>Menunggu di Lobby</option>
        <option>Ditolak</option>
        <option>Diizinkan</option>
        <option>Blacklist</option>
        <option>Kurir</option>
      </select>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle" id="tamuTable">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Unit Tujuan</th>
          <th>Jenis Kelamin</th>
          <th>No HP</th>
          <th>Alamat</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($tamuList) > 0): ?>
          <?php foreach ($tamuList as $index => $tamu): ?>
            <?php
              $statusValue = $tamu['status'];
              $statusLabel = $statusLabelMap[$statusValue] ?? $statusValue;
              $badgeClass = match($statusLabel) {
                  'Check-In' => 'bg-success',
                  'Check-Out' => 'bg-secondary',
                  'Blacklist' => 'bg-danger',
                  'Diizinkan' => 'bg-primary',
                  'Ditolak' => 'bg-warning text-dark',
                  'Pending', 'Menunggu di Lobby' => 'bg-info text-dark',
                  default => 'bg-secondary'
              };
            ?>
            <tr data-index="<?= $index ?>">
              <td><?= htmlspecialchars($tamu['nama']) ?></td>
              <td><?= htmlspecialchars($tamu['unit']) ?></td>
              <td><?= htmlspecialchars($tamu['jenis_kelamin']) ?></td>
              <td><?= htmlspecialchars($tamu['no_hp']) ?></td>
              <td><?= htmlspecialchars($tamu['alamat']) ?></td>
              <td class="status-cell">
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
              </td>
              <td>
                <select class="form-select form-select-sm status-select" data-id="<?= $tamu['id'] ?>">
                  <?php
                    $statusLabels = ['Check-In', 'Check-Out', 'Pending', 'Menunggu di Lobby', 'Ditolak', 'Diizinkan', 'Blacklist'];
                    foreach ($statusLabels as $label) {
                      $selected = ($label === $statusLabel) ? 'selected' : '';
                      echo "<option value=\"$label\" $selected>$label</option>";
                    }
                  ?>
                </select>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center">Tidak ada data tamu.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
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
</main>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  }

  document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function () {
      const id = this.getAttribute('data-id');
      const newStatus = this.value;
      const selectElem = this;
      fetch('update_status_tamu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(newStatus)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const statusCell = selectElem.closest('tr').querySelector('.status-cell span');
          statusCell.textContent = newStatus;
          const badgeClasses = ['bg-success', 'bg-secondary', 'bg-danger', 'bg-primary', 'bg-warning', 'bg-info', 'text-dark'];
          badgeClasses.forEach(cls => statusCell.classList.remove(cls));
          switch (newStatus.toLowerCase()) {
            case 'checkedin': statusCell.classList.add('bg-success'); break;
            case 'checkedout': statusCell.classList.add('bg-secondary'); break;
            case 'blacklist': statusCell.classList.add('bg-danger'); break;
            case 'diizinkan': statusCell.classList.add('bg-primary'); break;
            case 'ditolak': statusCell.classList.add('bg-warning', 'text-dark'); break;
            case 'pending': statusCell.classList.add('bg-info', 'text-dark'); break;
            case 'menunggu': statusCell.classList.add('bg-info', 'text-dark'); break;
            default: statusCell.classList.add('bg-secondary');
          }
        } else {
          alert('Gagal update status tamu: ' + data.message);
        }
      })
      .catch(err => {
        alert('Error: ' + err);
      });
    });
  });

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
</script>

</body>
</html>