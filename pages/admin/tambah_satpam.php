<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama = trim($_POST['nama']);
    $shift = $_POST['shift'];
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);

    // Validasi sederhana
    if (!$username || !$nama || !$shift || !$email || !$no_hp || !$_POST['password']) {
        $errors[] = "Semua kolom wajib diisi.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO satpam_users (username, password, nama_lengkap, shift, email, no_hp) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $password, $nama, $shift, $email, $no_hp);
        if ($stmt->execute()) {
            header("Location: data_satpam.php?msg=berhasil");
            exit;
        } else {
            $errors[] = "Gagal menyimpan data: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Satpam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <h4 class="mb-4">Tambah Satpam Baru</h4>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?= implode("<br>", $errors) ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-2">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Shift</label>
                <select name="shift" class="form-select" required>
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi">Pagi</option>
                    <option value="Siang">Siang</option>
                    <option value="Malam">Malam</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>No HP</label>
                <input type="text" name="no_hp" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Simpan</button>
            <a href="data_satpam.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>