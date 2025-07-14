<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

// Ambil data tamu biasa
$sqlTamu = "SELECT id, nama_tamu AS nama, status, izin_penghuni, waktu_checkin AS waktu_masuk, jenis_kelamin, no_hp, alamat, tujuan AS unit FROM tamu";

// Ambil data tamu kurir
$sqlKurir = "SELECT id, nama_pengantar AS nama, 'Kurir' AS status, NULL AS izin_penghuni, waktu_masuk, NULL AS jenis_kelamin, NULL AS no_hp, NULL AS alamat, tujuan_unit AS unit FROM tamu_kilat";

// Gabungkan data
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

$statusLabelMap = [
    'checkedin' => 'Check-In',
    'checkedout' => 'Check-Out',
    'pending' => 'Pending',
    'menunggu' => 'Menunggu di Lobby',
    'ditolak' => 'Ditolak',
    'diizinkan' => 'Diizinkan',
    'blacklist' => 'Blacklist',
    'Kurir' => 'Kurir'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Front Office</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
    .sidebar {
      background-color: #3d7b65;
      min-height: 100vh;
      padding: 20px;
      color: #e0f0e6;
      width: 220px;
      position: fixed;
      top: 0; left: 0;
      overflow-y: auto;
      z-index: 1000;
      transition: left 0.3s ease;
    }
    .sidebar a {
      color: #e0f0e6;
      text-decoration: none;
      display: block;
      padding: 10px 0;
      border-radius: 6px;
    }
    .sidebar a:hover, .sidebar a.active { background-color: #2e5e4d; }
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
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
    .overlay.show { display: block; }
    .sidebar.show { left: 0; }
    main { margin-left: 220px; padding: 20px; }
    @media (max-width: 768px) {
      .sidebar { left: -250px; }
      .sidebar-toggler { display: block; }
      main { margin-left: 0; }
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
  <hr />
  <a href="dashboard.php">Dashboard</a>
  <a href="daftar_tamu.php" class="active">Daftar Tamu</a>
  <a href="form_checkin.php">Form Check-in</a>
  <a href="validasi_qr.php">Validasi QR</a>
  <a href="cek_blacklist.php">Cek Blacklist</a>
  <a href="/pass-aem/logout.php" class="text-danger">Logout</a>
</div>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<main>
  <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
  <h4 class="mb-4">Daftar Tamu</h4>

  <a href="form_checkin.php" class="btn btn-primary btn-sm mb-3">+ Check-in Tamu</a>
  <button onclick="window.print()" class="btn btn-secondary btn-sm mb-3 ms-2">Print Daftar Tamu</button>
  <button onclick="exportExcel()" class="btn btn-success btn-sm mb-3 ms-2">Export ke Excel</button>

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
          <th>Izin Penghuni</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tamuList as $tamu): ?>
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
          <tr>
            <td><?= htmlspecialchars($tamu['nama']) ?></td>
            <td><?= htmlspecialchars($tamu['unit']) ?></td>
            <td><?= htmlspecialchars($tamu['jenis_kelamin']) ?></td>
            <td><?= htmlspecialchars($tamu['no_hp']) ?></td>
            <td><?= htmlspecialchars($tamu['alamat']) ?></td>
            <td class="status-cell"><span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span></td>
            <td>
              <?php if ($tamu['izin_penghuni'] === null): ?>
                <em class="text-muted">-</em>
              <?php else: ?>
                <select class="form-select form-select-sm izin-select" data-id="<?= $tamu['id'] ?>">
                  <option value="ya" <?= $tamu['izin_penghuni'] === 'ya' ? 'selected' : '' ?>>Diizinkan naik</option>
                  <option value="tidak" <?= $tamu['izin_penghuni'] === 'tidak' ? 'selected' : '' ?>>Hanya lobby</option>
                </select>
              <?php endif; ?>
            </td>
            <td>
              <select class="form-select form-select-sm status-select" data-id="<?= $tamu['id'] ?>">
                <?php
                  $statusOptions = ['Check-In','Check-Out','Pending','Menunggu di Lobby','Ditolak','Diizinkan','Blacklist'];
                  foreach ($statusOptions as $opt) {
                    $selected = ($opt === $statusLabel) ? 'selected' : '';
                    echo "<option value=\"$opt\" $selected>$opt</option>";
                  }
                ?>
              </select>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</main>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
  }

  document.getElementById('searchInput').addEventListener('input', filterTable);
  document.getElementById('statusFilter').addEventListener('change', filterTable);

  function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#tamuTable tbody tr');
    rows.forEach(row => {
      const nama = row.cells[0].textContent.toLowerCase();
      const stat = row.cells[5].textContent.toLowerCase();
      row.style.display = nama.includes(search) && (status === "" || stat.includes(status)) ? "" : "none";
    });
  }

  document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
      const id = this.dataset.id;
      const status = this.value;
      fetch('update_status_tamu.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&status=${status}`
      }).then(r => r.json()).then(res => {
        if (res.success) {
          const badge = this.closest('tr').querySelector('.status-cell .badge');
          badge.textContent = status;
        } else alert('Gagal update status');
      });
    });
  });

  document.querySelectorAll('.izin-select').forEach(select => {
    select.addEventListener('change', function() {
      const id = this.dataset.id;
      const izin = this.value;
      fetch('update_status_tamu.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&izin_penghuni=${izin}`
      }).then(r => r.json()).then(res => {
        if (!res.success) alert('Gagal update izin');
      });
    });
  });

  function exportExcel() {
    const rows = [['Nama','Unit Tujuan','Jenis Kelamin','No HP','Alamat','Status','Izin Penghuni']];
    document.querySelectorAll('#tamuTable tbody tr').forEach(row => {
      if (row.style.display === 'none') return;
      const data = Array.from(row.cells).slice(0, 7).map(td => td.textContent.trim());
      rows.push(data);
    });
    let csv = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
    const link = document.createElement("a");
    link.setAttribute("href", encodeURI(csv));
    link.setAttribute("download", "daftar_tamu.csv");
    document.body.appendChild(link);
    link.click();
    link.remove();
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>