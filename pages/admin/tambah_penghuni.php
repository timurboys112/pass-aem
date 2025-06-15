<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php'; // koneksi database

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $unit = trim($_POST['unit'] ?? '');

    // Validasi sederhana
    if (!$username) $errors[] = "Username wajib diisi";
    if (!$password) $errors[] = "Password wajib diisi";
    if (!$nama_lengkap) $errors[] = "Nama lengkap wajib diisi";
    if (!$unit) $errors[] = "Unit wajib diisi";

    if (empty($errors)) {
        // Cek username sudah ada?
        $stmt = $conn->prepare("SELECT id FROM penghuni_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->fetch()) {
            $errors[] = "Username sudah digunakan";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO penghuni_users (username, password, nama_lengkap, email, no_hp, unit) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $password_hash, $nama_lengkap, $email, $no_hp, $unit);
            $success = $stmt->execute();

            if ($success) {
                header("Location: data_penghuni.php?msg=success");
                exit;
            } else {
                $errors[] = "Gagal menyimpan data penghuni";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Tambah Penghuni</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light p-4">
  <div class="container" style="max-width: 600px;">
    <h3>Tambah Penghuni</h3>
    <a href="data_penghuni.php" class="btn btn-secondary mb-3">&larr; Kembali</a>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?=htmlspecialchars($err)?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="username" class="form-label">Username *</label>
        <input type="text" id="username" name="username" class="form-control" value="<?=htmlspecialchars($_POST['username'] ?? '')?>" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password *</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?=htmlspecialchars($_POST['nama_lengkap'] ?? '')?>" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
      </div>

      <div class="mb-3">
        <label for="no_hp" class="form-label">No HP</label>
        <input type="text" id="no_hp" name="no_hp" class="form-control" value="<?=htmlspecialchars($_POST['no_hp'] ?? '')?>">
      </div>

      <div class="mb-3">
        <label for="unit" class="form-label">Unit *</label>
        <input type="text" id="unit" name="unit" class="form-control" value="<?=htmlspecialchars($_POST['unit'] ?? '')?>" required>
      </div>

      <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
  </div>
</body>
</html>