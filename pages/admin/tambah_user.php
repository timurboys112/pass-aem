<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $shift = trim($_POST['shift'] ?? '');

    // Validasi sederhana
    if (!$role || !$username || !$password) {
        $errors[] = "Role, username, dan password wajib diisi.";
    }

    if (empty($errors)) {
        // Insert ke tabel role spesifik dulu, sesuai role
        switch ($role) {
            case 'admin':
                $sql = "INSERT INTO admin_users (username, password, nama_lengkap, email, no_hp) VALUES (?, ?, ?, ?, ?)";
                break;
            case 'fo':
                $sql = "INSERT INTO fo_users (username, password, nama_lengkap, email, no_hp) VALUES (?, ?, ?, ?, ?)";
                break;
            case 'penghuni':
                $sql = "INSERT INTO penghuni_users (username, password, nama_lengkap, email, no_hp, unit) VALUES (?, ?, ?, ?, ?, ?)";
                break;
            case 'engineering':
                $sql = "INSERT INTO engineering_users (username, password, nama_lengkap, email, no_hp, unit) VALUES (?, ?, ?, ?, ?, ?)";
                break;
            case 'satpam':
                $sql = "INSERT INTO satpam_users (username, password, nama_lengkap, shift, email, no_hp) VALUES (?, ?, ?, ?, ?, ?)";
                break;
            case 'tamu':
                $sql = "INSERT INTO tamu_users (username, password, nama_lengkap, email, no_hp) VALUES (?, ?, ?, ?, ?)";
                break;
            default:
                $errors[] = "Role tidak valid.";
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            if (in_array($role, ['penghuni', 'engineering'])) {
                mysqli_stmt_bind_param($stmt, "ssssss", $username, $hashed_password, $nama_lengkap, $email, $no_hp, $unit);
            } elseif ($role == 'satpam') {
                mysqli_stmt_bind_param($stmt, "ssssss", $username, $hashed_password, $nama_lengkap, $shift, $email, $no_hp);
            } else {
                mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $nama_lengkap, $email, $no_hp);
            }

            if (mysqli_stmt_execute($stmt)) {
                $id_role_user = mysqli_insert_id($conn);

                // Insert ke tabel users
                $stmt2 = mysqli_prepare($conn, "INSERT INTO users (role, id_role_user) VALUES (?, ?)");
                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, "si", $role, $id_role_user);
                    if (mysqli_stmt_execute($stmt2)) {
                        $success = "Pengguna berhasil ditambahkan.";
                    } else {
                        $errors[] = "Gagal menambahkan ke tabel users.";
                    }
                    mysqli_stmt_close($stmt2);
                }
            } else {
                $errors[] = "Gagal menyimpan data pengguna.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan query.";
        }
    }
}
?> 

<!-- Tambahkan link Bootstrap CSS di head halaman kamu jika belum ada -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">
    <h2 class="mb-4">Tambah Pengguna</h2>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger" role="alert">
            <?=htmlspecialchars($error)?>
        </div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <?=htmlspecialchars($success)?>
        </div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="role" class="form-label">Role:</label>
            <select class="form-select" id="role" name="role" required onchange="toggleFields(this.value)">
                <option value="" selected>-- Pilih Role --</option>
                <option value="admin">Admin</option>
                <option value="fo">Front Office</option>
                <option value="penghuni">Penghuni</option>
                <option value="engineering">Engineering</option>
                <option value="satpam">Satpam</option>
                <option value="tamu">Tamu</option>
            </select>
            <div class="invalid-feedback">
                Harap pilih role pengguna.
            </div>
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback">
                Username wajib diisi.
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="invalid-feedback">
                Password wajib diisi.
            </div>
        </div>

        <div class="mb-3">
            <label for="nama_lengkap" class="form-label">Nama Lengkap:</label>
            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email">
        </div>

        <div class="mb-3">
            <label for="no_hp" class="form-label">No HP:</label>
            <input type="text" class="form-control" id="no_hp" name="no_hp">
        </div>

        <div class="mb-3" id="unitField" style="display:none;">
            <label for="unit" class="form-label">Unit:</label>
            <input type="text" class="form-control" id="unit" name="unit">
        </div>

        <div class="mb-3" id="shiftField" style="display:none;">
            <label for="shift" class="form-label">Shift:</label>
            <input type="text" class="form-control" id="shift" name="shift">
        </div>

        <button type="submit" class="btn btn-primary">Tambah</button>
        <a href="akun_pengguna.php" class="btn btn-secondary ms-2">Batal</a>
    </form>
</div>

<script>
// Bootstrap 5 validation example
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()

// Toggle unit/shift fields berdasarkan role
function toggleFields(role) {
    document.getElementById('unitField').style.display = (role === 'penghuni' || role === 'engineering') ? 'block' : 'none';
    document.getElementById('shiftField').style.display = (role === 'satpam') ? 'block' : 'none';
}
</script>