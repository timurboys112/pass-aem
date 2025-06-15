<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id) || $id == 0) {
    header("Location: data_penghuni.php");
    exit;
}

// Ambil data penghuni berdasarkan ID
$sql = "SELECT * FROM penghuni_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$penghuni = $result->fetch_assoc();

if (!$penghuni) {
    echo "Data penghuni tidak ditemukan.";
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $unit = $_POST['unit'];
    $no_hp = $_POST['no_hp'];

    $updateSql = "UPDATE penghuni_users SET nama_lengkap = ?, unit = ?, no_hp = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sssi", $nama_lengkap, $unit, $no_hp, $id);
    if ($stmt->execute()) {
        header("Location: data_penghuni.php?success=update");
        exit;
    } else {
        $error = "Gagal mengupdate data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Penghuni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h3>Edit Penghuni</h3>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($penghuni['nama_lengkap']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="unit" class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($penghuni['unit']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="no_hp" class="form-label">No HP</label>
            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($penghuni['no_hp']) ?>" required>
        </div>
        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="data_penghuni.php" class="btn btn-secondary">Batal</a>
    </form>
</body>
</html>