<?php
session_start();
require_once "config/db.php";

$step = 'input';
$role_tables = [
    'admin' => 'admin_users',
    'fo' => 'fo_users',
    'penghuni' => 'penghuni_users',
    'engineering' => 'engineering_users',
    'satpam' => 'satpam_users',
    'tamu' => 'tamu_users'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 'input';

    if ($step === 'input') {
        $username = $_POST['username'] ?? '';
        $role = $_POST['role'] ?? '';

        if (empty($role)) {
            $error = "Silakan pilih role terlebih dahulu!";
        } elseif (empty($username)) {
            $error = "Username tidak boleh kosong!";
        } elseif (!isset($role_tables[$role])) {
            $error = "Role tidak valid!";
        } else {
            $table = $role_tables[$role];
            $stmt = $conn->prepare("SELECT id FROM $table WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $_SESSION['reset_username'] = $username;
                $_SESSION['reset_role'] = $role;
                $step = 'reset';
            } else {
                $error = "Username tidak ditemukan.";
            }
        }
    } elseif ($step === 'reset') {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $username = $_SESSION['reset_username'] ?? '';
        $role = $_SESSION['reset_role'] ?? '';

        if (empty($password) || empty($confirm)) {
            $error = "Password tidak boleh kosong.";
            $step = 'reset';
        } elseif ($password !== $confirm) {
            $error = "Konfirmasi password tidak cocok.";
            $step = 'reset';
        } elseif (!isset($role_tables[$role])) {
            $error = "Role tidak valid!";
            $step = 'reset';
        } else {
            $table = $role_tables[$role];
            $hashed = md5($password);

            $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $hashed, $username);
            if ($stmt->execute()) {
                $success = "Password berhasil direset. Silakan login.";
                unset($_SESSION['reset_username'], $_SESSION['reset_role']);
                $step = 'input';
            } else {
                $error = "Gagal menyimpan password.";
                $step = 'reset';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password - AEM Visitor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
      max-width: 450px;
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
    <!-- Gambar -->
    <div class="col-md-6 bg-image">
      <div class="bg-overlay w-100">
        <h1>Reset Password<br>AEM Visitor</h1>
      </div>
    </div>

    <!-- Form -->
    <div class="col-md-6 d-flex justify-content-center align-items-center bg-light">
      <form method="POST" class="form-container">
        <input type="hidden" name="step" value="<?= htmlspecialchars($step) ?>" />
        <img src="assets/images/logo aem.jpeg" alt="Logo AEM" class="d-block mx-auto mb-3" style="width: 80px;" />
        <h4 class="text-center fw-bold mb-4">Reset Password</h4>

        <?php if (isset($error)): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php elseif (isset($success)): ?>
          <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($step === 'input'): ?>
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

          <div class="mb-3">
            <input type="text" name="username" class="form-control" placeholder="Username" required />
          </div>
          <button class="btn btn-success w-100 fw-bold" type="submit">Lanjut</button>

        <?php elseif ($step === 'reset'): ?>
          <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password Baru" required />
          </div>
          <div class="mb-3">
            <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi Password" required />
          </div>
          <button class="btn btn-success w-100 fw-bold" type="submit">Simpan Password</button>
        <?php endif; ?>

        <div class="text-center mt-3">
          <a href="login.php" class="text-success">Kembali ke Login</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function selectRole(value, label, icon) {
  document.getElementById('role').value = value;
  document.getElementById('selectedRoleText').innerHTML = `<i class="fas ${icon} me-2"></i>${label}`;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>