<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "ID tidak valid.";
    exit;
}

// Ambil data jadwal
$query = mysqli_query($conn, "SELECT * FROM jadwal_hari_ini WHERE id = $id");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}

// Update data jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
    $unit   = mysqli_real_escape_string($conn, $_POST['unit']);
    $jam    = mysqli_real_escape_string($conn, $_POST['jam']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update = mysqli_query($conn, "UPDATE jadwal_hari_ini SET 
        nama = '$nama',
        unit = '$unit',
        jam = '$jam',
        tujuan = '$tujuan',
        status = '$status'
        WHERE id = $id
    ");

    if ($update) {
        header("Location: dashboard.php?msg=sukses_edit");
        exit;
    } else {
        $error = "Gagal memperbarui data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Jadwal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h4>Edit Jadwal Kunjungan</h4>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-3">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" id="nama" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="unit" class="form-label">Unit</label>
                <input type="text" id="unit" name="unit" class="form-control" value="<?= htmlspecialchars($data['unit']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="jam" class="form-label">Jam</label>
                <input type="time" id="jam" name="jam" class="form-control" value="<?= htmlspecialchars($data['jam']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="tujuan" class="form-label">Tujuan</label>
                <input type="text" id="tujuan" name="tujuan" class="form-control" value="<?= htmlspecialchars($data['tujuan']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="Menunggu" <?= $data['status'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="Diterima" <?= $data['status'] == 'Diterima' ? 'selected' : '' ?>>Diterima</option>
                    <option value="Ditolak" <?= $data['status'] == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="dashboard.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>