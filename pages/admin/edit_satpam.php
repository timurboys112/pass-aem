<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID tidak valid.');
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM satpam_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Data tidak ditemukan.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama']);
    $shift = $_POST['shift'];
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $new_password = $_POST['password'];

    if (!$username || !$nama || !$shift || !$email || !$no_hp) {
        $errors[] = "Semua kolom wajib diisi.";
    }

    if (empty($errors)) {
        if ($new_password) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE satpam_users SET username=?, password=?, nama_lengkap=?, shift=?, email=?, no_hp=? WHERE id=?");
            $stmt->bind_param("ssssssi", $username, $hashed, $nama, $shift, $email, $no_hp, $id);
        } else {
            $stmt = $conn->prepare("UPDATE satpam_users SET username=?, nama_lengkap=?, shift=?, email=?, no_hp=? WHERE id=?");
            $stmt->bind_param("sssssi", $username, $nama, $shift, $email, $no_hp, $id);
        }

        if ($stmt->execute()) {
            header("Location: data_satpam.php?msg=update");
            exit;
        } else {
            $errors[] = "Gagal update data: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Satpam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <h4 class="mb-4">Edit Data Satpam</h4>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?= implode("<br>", $errors) ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-2">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($data['username']) ?>" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Password Baru (biarkan kosong jika tidak diganti)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="mb-2">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($data['nama_lengkap']) ?>" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Shift</label>
                <select name="shift" class="form-select" required>
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi" <?= $data['shift'] === 'Pagi' ? 'selected' : '' ?>>Pagi</option>
                    <option value="Siang" <?= $data['shift'] === 'Siang' ? 'selected' : '' ?>>Siang</option>
                    <option value="Malam" <?= $data['shift'] === 'Malam' ? 'selected' : '' ?>>Malam</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>No HP</label>
                <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
            <a href="data_satpam.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>