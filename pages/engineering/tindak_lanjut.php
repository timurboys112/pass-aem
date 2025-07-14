<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

$tanggal = $_GET['tanggal'] ?? '';
$waktu = $_GET['waktu'] ?? '';

if (empty($tanggal) || empty($waktu)) {
    die("Parameter tidak lengkap.");
}

$laporan = null;
$sumber = '';

// Coba dari laporan_teknisi
$stmt = $conn->prepare("SELECT *, 'teknisi' AS sumber FROM laporan_teknisi WHERE tanggal = ? AND waktu = ?");
$stmt->bind_param("ss", $tanggal, $waktu);
$stmt->execute();
$result = $stmt->get_result();
$laporan = $result->fetch_assoc();

if (!$laporan) {
    $stmt = $conn->prepare("SELECT *, 'masalah' AS sumber FROM laporan_masalah WHERE tanggal = ? AND waktu = ?");
    $stmt->bind_param("ss", $tanggal, $waktu);
    $stmt->execute();
    $result = $stmt->get_result();
    $laporan = $result->fetch_assoc();
}

if (!$laporan) {
    die("Laporan tidak ditemukan.");
}

$sumber = $laporan['sumber'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['batal'])) {
        // Batalkan tindak lanjut
        if ($sumber === 'teknisi') {
            $clear = $conn->prepare("UPDATE laporan_teknisi SET tindak_lanjut = NULL WHERE tanggal = ? AND waktu = ?");
        } else {
            $clear = $conn->prepare("UPDATE laporan_masalah SET tanggal_tindak_lanjut = NULL WHERE tanggal = ? AND waktu = ?");
        }
        $clear->bind_param("ss", $tanggal, $waktu);
        $clear->execute();

        header("Location: dashboard.php?cleared=1");
        exit;
    } else {
        // Simpan tindak lanjut
        $tindak_lanjut = $_POST['tindak_lanjut'] ?? null;

        if ($tindak_lanjut) {
            if ($sumber === 'teknisi') {
                $update = $conn->prepare("UPDATE laporan_teknisi SET tindak_lanjut = ? WHERE tanggal = ? AND waktu = ?");
            } else {
                $update = $conn->prepare("UPDATE laporan_masalah SET tanggal_tindak_lanjut = ? WHERE tanggal = ? AND waktu = ?");
            }

            $update->bind_param("sss", $tindak_lanjut, $tanggal, $waktu);
            $update->execute();

            if ($update->affected_rows > 0) {
                header("Location: dashboard.php?success=1");
                exit;
            } else {
                $error = "Gagal menyimpan atau tidak ada perubahan data.";
            }
        } else {
            $error = "Tanggal tindak lanjut wajib diisi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tindak Lanjut Laporan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h3 class="mb-4">Tindak Lanjut Laporan <?= ucfirst($sumber) ?></h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="card p-4 shadow-sm">
            <div class="mb-3">
                <label class="form-label">Tanggal Laporan</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($laporan['tanggal']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Waktu</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($laporan['waktu']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Isi Laporan</label>
                <textarea class="form-control" rows="3" disabled><?= htmlspecialchars($laporan['isi_laporan'] ?? $laporan['laporan']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="tindak_lanjut" class="form-label">Tanggal Tindak Lanjut</label>
                <input type="date" name="tindak_lanjut" id="tindak_lanjut" class="form-control"
                    value="<?= htmlspecialchars($laporan['tindak_lanjut'] ?? $laporan['tanggal_tindak_lanjut'] ?? '') ?>" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success" name="simpan">Simpan</button>
                <button type="submit" class="btn btn-danger" name="batal" onclick="return confirm('Yakin ingin membatalkan tindak lanjut?')">Batalkan</button>
                <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</body>
</html>