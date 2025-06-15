<?php
session_start();
require_once "config/db.php";

$role_tables = [
    'admin' => 'admin_users',
    'fo' => 'fo_users',
    'penghuni' => 'penghuni_users',
    'engineering' => 'engineering_users',
    'satpam' => 'satpam_users',
    'tamu' => 'tamu_users'
];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username      = trim($_POST['username'] ?? '');
    $password      = $_POST['password'] ?? '';
    $role          = $_POST['role'] ?? '';
    $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telepon       = trim($_POST['telepon'] ?? '');
    $unit          = trim($_POST['unit'] ?? null);
    $blok          = trim($_POST['blok'] ?? null);
    $nama_penghuni = trim($_POST['nama_penghuni'] ?? null);
    $tujuan        = trim($_POST['tujuan'] ?? null);

    if (empty($role) || empty($username) || empty($password) || empty($nama_lengkap) || empty($email) || empty($telepon)) {
        $error = "Semua field wajib diisi.";
    } elseif (!isset($role_tables[$role])) {
        $error = "Role tidak valid!";
    } else {
        $table = $role_tables[$role];
        $check = $conn->prepare("SELECT id FROM $table WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $hashed = md5($password);

            if ($role === 'penghuni') {
                $stmt = $conn->prepare("INSERT INTO $table (username, password, nama_lengkap, email, no_hp, unit, blok) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $username, $hashed, $nama_lengkap, $email, $telepon, $unit, $blok);
            } elseif ($role === 'tamu') {
                $stmt = $conn->prepare("INSERT INTO $table (username, password, nama_lengkap, email, no_hp, nama_penghuni, tujuan) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $username, $hashed, $nama_lengkap, $email, $telepon, $nama_penghuni, $tujuan);
            } else {
                $stmt = $conn->prepare("INSERT INTO $table (username, password, nama_lengkap, email, no_hp) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $hashed, $nama_lengkap, $email, $telepon);
            }

            if ($stmt->execute()) {
                $role_id = $stmt->insert_id;
                $user_stmt = $conn->prepare("INSERT INTO users (role, id_role_user) VALUES (?, ?)");
                $user_stmt->bind_param("si", $role, $role_id);
                $user_stmt->execute();
                $success = "Registrasi berhasil. Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi - AEM Visitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    .bg-image {
      background: url('assets/images/aem.png') center center / cover no-repeat;
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .bg-overlay {
      background-color: rgba(0, 0, 0, 0.5);
      padding: 2rem;
      border-radius: 0.75rem;
      text-align: center;
    }
    .form-container {
      max-width: 480px;
      width: 100%;
      padding: 2rem;
    }
    @media (max-width: 767.98px) {
      .bg-image {
        height: 40vh;
      }
    }
  </style>
</head>
<body>

<div class="container-fluid p-0">
  <div class="row g-0 flex-column flex-md-row">
    <div class="col-md-6 bg-image">
      <div class="bg-overlay w-100">
        <h1>Registrasi<br>AEM Visitor</h1>
      </div>
    </div>

    <div class="col-md-6 d-flex justify-content-center align-items-center bg-light">
      <form method="POST" class="form-container">
        <img src="assets/images/logo%20aem.jpeg" alt="Logo AEM" class="d-block mx-auto mb-3" style="width: 80px;" />
        <h4 class="text-center fw-bold mb-4">Form Registrasi</h4>

        <?php if ($error): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
          <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
        <div class="mb-3"><input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap" required></div>
        <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
        <div class="mb-3"><input type="text" name="telepon" class="form-control" placeholder="No HP" required></div>

        <!-- Custom Role Dropdown -->
        <div class="mb-3">
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="dropdownRoleButton" data-bs-toggle="dropdown" aria-expanded="false">
              <span id="selectedRoleText"><i class="fas fa-user-tag me-2"></i>Pilih Role</span>
            </button>
            <ul class="dropdown-menu w-100" aria-labelledby="dropdownRoleButton">
              <li><a class="dropdown-item" href="#" onclick="selectRole('admin', 'Admin', 'fa-user-shield')"><i class="fas fa-user-shield me-2"></i>Admin</a></li>
              <li><a class="dropdown-item" href="#" onclick="selectRole('fo', 'Front Office', 'fa-concierge-bell')"><i class="fas fa-concierge-bell me-2"></i>Front Office</a></li>
              <li><a class="dropdown-item" href="#" onclick="selectRole('penghuni', 'Penghuni', 'fa-user')"><i class="fas fa-user me-2"></i>Penghuni</a></li>
              <li><a class="dropdown-item" href="#" onclick="selectRole('engineering', 'Engineering', 'fa-tools')"><i class="fas fa-tools me-2"></i>Engineering</a></li>
              <li><a class="dropdown-item" href="#" onclick="selectRole('satpam', 'Satpam', 'fa-shield-halved')"><i class="fas fa-shield-halved me-2"></i>Satpam</a></li>
              <li><a class="dropdown-item" href="#" onclick="selectRole('tamu', 'Tamu', 'fa-user-friends')"><i class="fas fa-user-friends me-2"></i>Tamu</a></li>
            </ul>
          </div>
          <input type="hidden" name="role" id="role" required>
        </div>

        <!-- Field khusus penghuni -->
        <div id="penghuniFields" style="display: none;">
          <div class="mb-3"><input type="text" name="unit" class="form-control" placeholder="Unit"></div>
          <div class="mb-3"><input type="text" name="blok" class="form-control" placeholder="Blok"></div>
        </div>

        <!-- Field khusus tamu -->
        <div id="tamuFields" style="display: none;">
          <div class="mb-3"><input type="text" name="nama_penghuni" class="form-control" placeholder="Nama Penghuni"></div>
          <div class="mb-3"><input type="text" name="tujuan" class="form-control" placeholder="Tujuan"></div>
        </div>

        <button type="submit" class="btn btn-success w-100 fw-bold">Daftar</button>
        <div class="text-center mt-3">
          <a href="login.php" class="text-success">Sudah punya akun? Login</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function selectRole(value, label, icon) {
  document.getElementById('role').value = value;
  document.getElementById('selectedRoleText').innerHTML = `<i class="fas ${icon} me-2"></i>${label}`;
  toggleFields();
}

function toggleFields() {
  const role = document.getElementById('role').value;
  document.getElementById('penghuniFields').style.display = (role === 'penghuni') ? 'block' : 'none';
  document.getElementById('tamuFields').style.display = (role === 'tamu') ? 'block' : 'none';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>