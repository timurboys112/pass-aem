<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('ID user tidak valid.');
}

// Pertama ambil data role dan id_role_user dari users
$sql = "SELECT role, id_role_user FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res) == 0) {
    die('User tidak ditemukan.');
}
$user = mysqli_fetch_assoc($res);
$role = $user['role'];
$id_role_user = $user['id_role_user'];

// Mapping tabel role
$role_tables = [
    'admin' => 'admin_users',
    'fo' => 'fo_users',
    'penghuni' => 'penghuni_users',
    'engineering' => 'engineering_users',
    'satpam' => 'satpam_users',
    'tamu' => 'tamu_users'
];

$table_role = $role_tables[$role] ?? null;
if (!$table_role) {
    die('Role user tidak valid.');
}

// Ambil data detail user
$sql2 = "SELECT * FROM $table_role WHERE id = ?";
$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, "i", $id_role_user);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$data = mysqli_fetch_assoc($res2);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $shift = trim($_POST['shift'] ?? '');

    if (!$username) {
        $errors[] = 'Username wajib diisi.';
    }

    if (empty($errors)) {
        // Update data di tabel role
        $update_sql = "UPDATE $table_role SET username = ?, nama_lengkap = ?, email = ?, no_hp = ?";
        $params = [$username, $nama_lengkap, $email, $no_hp];
        $types = "ssss";

        if (in_array($role, ['penghuni', 'engineering'])) {
            $update_sql .= ", unit = ?";
            $params[] = $unit;
            $types .= "s";
        }
        if ($role == 'satpam') {
            $update_sql .= ", shift = ?";
            $params[] = $shift;
            $types .= "s";
        }

        // Jika password diisi, hash dan update
        if (!empty($password)) {
            $update_sql .= ", password = ?";
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $params[] = $hashed_password;
            $types .= "s";
        }

        $update_sql .= " WHERE id = ?";
        $params[] = $id_role_user;
        $types .= "i";

        $stmt3 = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt3, $types, ...$params);
        if (mysqli_stmt_execute($stmt3)) {
            $success = "Data pengguna berhasil diperbarui.";
        } else {
            $errors[] = "Gagal memperbarui data.";
        }
        mysqli_stmt_close($stmt3);
    }
}
?> 

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Edit Pengguna - Role: <?=htmlspecialchars($role)?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5 mb-5" style="max-width: 700px;">
  <h2 class="mb-4">Edit Pengguna (Role: <?=htmlspecialchars(ucfirst($role))?>)</h2>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?=htmlspecialchars($error)?></li>
      <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <form method="POST" class="needs-validation" novalidate>
    <div class="mb-3">
      <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="username" name="username" value="<?=htmlspecialchars($data['username'])?>" required>
      <div class="invalid-feedback">Username wajib diisi.</div>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
      <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
    </div>

    <div class="mb-3">
      <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
      <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?=htmlspecialchars($data['nama_lengkap'])?>">
    </div>

    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email" value="<?=htmlspecialchars($data['email'])?>">
    </div>

    <div class="mb-3">
      <label for="no_hp" class="form-label">No HP</label>
      <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?=htmlspecialchars($data['no_hp'])?>">
    </div>

    <?php if (in_array($role, ['penghuni', 'engineering'])): ?>
    <div class="mb-3">
      <label for="unit" class="form-label">Unit</label>
      <input type="text" class="form-control" id="unit" name="unit" value="<?=htmlspecialchars($data['unit'])?>">
    </div>
    <?php endif; ?>

    <?php if ($role == 'satpam'): ?>
    <div class="mb-3">
      <label for="shift" class="form-label">Shift</label>
      <input type="text" class="form-control" id="shift" name="shift" value="<?=htmlspecialchars($data['shift'])?>">
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="akun_pengguna.php" class="btn btn-secondary ms-2">Batal</a>
  </form>
</div>

<script>
// Bootstrap 5 form validation
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>