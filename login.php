<?php
session_start();
require_once "config/db.php";

$remember_username = $_COOKIE['remember_username'] ?? '';
$remember_password = $_COOKIE['remember_password'] ?? '';
$remember_checked = isset($_COOKIE['remember_username']) ? 'checked' : '';

if (isset($_POST['login'])) {
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($role) || empty($username) || empty($password)) {
        $error = "Semua field wajib diisi!";
    } else {
        $tables = [
            'admin' => 'admin_users',
            'fo' => 'fo_users',
            'penghuni' => 'penghuni_users',
            'engineering' => 'engineering_users',
            'satpam' => 'satpam_users',
            'tamu' => 'tamu_users'
        ];

        if (!isset($tables[$role])) {
            $error = "Role tidak valid!";
        } else {
            $table = $tables[$role];
            $hashed = md5($password);

            $stmt = $conn->prepare("SELECT id, nama_lengkap FROM $table WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $hashed);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['login'] = true;
                $_SESSION['role'] = $role;
                $_SESSION['id_user'] = $user['id'];
                $_SESSION['nama'] = $user['nama_lengkap'];

                if (isset($_POST['remember_me'])) {
                    setcookie('remember_username', $username, time() + 3600 * 24 * 30);
                    setcookie('remember_password', $password, time() + 3600 * 24 * 30);
                } else {
                    setcookie('remember_username', '', time() - 3600);
                    setcookie('remember_password', '', time() - 3600);
                }

                header("Location: index.php");
                exit;
            } else {
                $error = "Username atau password salah!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - AEM Visitor</title>
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
        <h1>Selamat Datang<br>di AEM Visitor</h1>
      </div>
    </div>

    <!-- Form login -->
    <div class="col-md-6 d-flex justify-content-center align-items-center bg-light">
      <form method="POST" class="form-container">
        <img src="assets/images/logo aem.jpeg" alt="Logo AEM" class="d-block mx-auto mb-3" style="width: 80px;">
        <h4 class="text-center fw-bold mb-4">Login AEM Visitor</h4>

        <?php if (isset($error)): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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
          <input type="text" name="username" class="form-control" placeholder="Username" value="<?= htmlspecialchars($remember_username) ?>" required>
        </div>

        <div class="mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" value="<?= htmlspecialchars($remember_password) ?>" required>
        </div>

        <div class="mb-3 form-check">
          <input type="checkbox" name="remember_me" class="form-check-input" id="rememberMe" <?= $remember_checked ?>>
          <label class="form-check-label" for="rememberMe">Remember Me</label>
        </div>

        <button name="login" class="btn btn-success w-100 fw-bold" type="submit">Login</button>

        <div class="text-center mt-3">
          <a href="register.php" class="text-success">Buat Akun</a> |
          <a href="forgot_password.php" class="text-success">Lupa Password?</a>
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