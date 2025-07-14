<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "ID tidak valid.";
    exit;
}

// Ambil data jadwal sesuai ID
$stmt = mysqli_prepare($conn, "SELECT * FROM jadwal_hari_ini WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}

// Proses update progres & status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $progres = intval($_POST['progres']);

    // Pastikan progres berada di rentang 0–100
    if ($progres < 0) $progres = 0;
    if ($progres > 100) $progres = 100;

    $updStmt = mysqli_prepare($conn, "
        UPDATE jadwal_hari_ini
        SET status = ?, progres = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($updStmt, "sii", $status, $progres, $id);
    $ok = mysqli_stmt_execute($updStmt);
    mysqli_stmt_close($updStmt);

    if ($ok) {
        header("Location: dashboard.php?msg=progres_updated");
        exit;
    } else {
        $error = "Gagal memperbarui progres: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Update Progres</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h4>Update Progres — ID <?= htmlspecialchars($data['id']) ?></h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Unit</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($data['unit']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Jam</label>
            <input type="time" class="form-control" value="<?= htmlspecialchars($data['jam']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Tujuan</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($data['tujuan']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select" required>
                <option value="Belum" <?= $data['status'] === 'Belum' ? 'selected' : '' ?>>Belum</option>
                <option value="Sedang Dikerjakan" <?= $data['status'] === 'Sedang Dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                <option value="Selesai" <?= $data['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="progres" class="form-label">Progres (%)</label>
            <input type="number" id="progres" name="progres" class="form-control"
                   value="<?= intval($data['progres']) ?>"
                   min="0" max="100" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>