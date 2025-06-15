<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

// Ambil data akun pengguna dari semua role
$akun_pengguna = [];

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$role_tables = [
    'admin' => ['table' => 'admin_users', 'id_column' => 'id'],
    'fo' => ['table' => 'fo_users', 'id_column' => 'id'],
    'penghuni' => ['table' => 'penghuni_users', 'id_column' => 'id'],
    'engineering' => ['table' => 'engineering_users', 'id_column' => 'id'],
    'satpam' => ['table' => 'satpam_users', 'id_column' => 'id'],
    'tamu' => ['table' => 'tamu_users', 'id_column' => 'id']
];

// Ambil semua user dari tabel users
$sql = "SELECT id, role, id_role_user FROM users ORDER BY id ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error query users: " . mysqli_error($conn));
}

while ($user = mysqli_fetch_assoc($result)) {
    $role = $user['role'];
    $id_role_user = (int)$user['id_role_user'];

    // Default data untuk ditampilkan
    $userData = [
        'id' => $user['id'],
        'role' => $role,
        'username' => '-',
        'email' => '-',
        'status' => 'Aktif',  // Jika ada kolom status di tabel role, sesuaikan
        'detail' => [
            'nama_lengkap' => '-',
            'no_hp' => '-'
        ]
    ];

    // Ambil data dari tabel role terkait
    if (array_key_exists($role, $role_tables)) {
        $table = $role_tables[$role]['table'];
        $id_col = $role_tables[$role]['id_column'];

        // Asumsikan kolom nama_lengkap dan no_hp ada di tabel role terkait, jika tidak ada sesuaikan
        $stmt = mysqli_prepare($conn, "SELECT username, email, nama_lengkap, no_hp FROM $table WHERE $id_col = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_role_user);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $userData['username'] = $row['username'] ?? '-';
                $userData['email'] = $row['email'] ?? '-';
                $userData['detail']['nama_lengkap'] = $row['nama_lengkap'] ?? '-';
                $userData['detail']['no_hp'] = $row['no_hp'] ?? '-';
            }
            mysqli_stmt_close($stmt);
        }
    }

    $akun_pengguna[] = $userData;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Akun Pengguna</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
    <a href="akun_pengguna.php" class="active">Akun Pengguna</a>
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
    <h4 class="mb-4">Akun Pengguna</h4>

    <a href="tambah_user.php" class="btn btn-primary btn-sm mb-3">+ Tambah Pengguna</a>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-success">
          <tr>
            <th>Username</th>
            <th>Nama Lengkap</th>
            <th>No. HP</th>
            <th>Role</th>
            <th>Email</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($akun_pengguna)): ?>
            <?php foreach ($akun_pengguna as $akun): ?>
              <tr>
                <td><?= htmlspecialchars($akun['username']) ?></td>
                <td><?= htmlspecialchars($akun['detail']['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($akun['detail']['no_hp']) ?></td>
                <td><?= htmlspecialchars(ucfirst($akun['role'])) ?></td>
                <td><?= htmlspecialchars($akun['email']) ?></td>
                <td><?= htmlspecialchars($akun['status']) ?></td>
                <td class="text-center">
                  <a href="edit_user.php?id=<?= $akun['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                  <a href="hapus_user.php?id=<?= $akun['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">Hapus</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center">Tidak ada data akun pengguna.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  sidebar.classList.toggle("show");
  overlay.classList.toggle("show");
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>