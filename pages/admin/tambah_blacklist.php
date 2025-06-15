<?php
require_once '../../includes/auth.php';
checkRole(['admin', 'satpam']); // hanya admin & satpam yang bisa akses
require_once '../../config/db.php';

$error = '';
$success = '';

// Ambil tamu reguler
$tamuQuery = "SELECT id, nama_tamu, 'tamu' AS tamu_type FROM tamu ORDER BY nama_tamu ASC";
$tamuResult = $conn->query($tamuQuery);
$tamuList = $tamuResult ? $tamuResult->fetch_all(MYSQLI_ASSOC) : [];

// Ambil tamu kilat (kurir)
$kurirQuery = "SELECT id, nama_pengantar AS nama_tamu, 'kilat' AS tamu_type FROM tamu_kilat ORDER BY nama_pengantar ASC";
$kurirResult = $conn->query($kurirQuery);
if ($kurirResult) {
    $kurirList = $kurirResult->fetch_all(MYSQLI_ASSOC);
    $tamuList = array_merge($tamuList, $kurirList);

// Ambil tamu engineering
    $engineeringQuery = "SELECT id, id_tamu AS nama_tamu, 'engineering' AS tamu_type FROM izin_kunjungan_engineering ORDER BY id_tamu ASC";
    $engineeringResult = $conn->query($engineeringQuery);
    if ($engineeringResult) {
        $engineeringList = $engineeringResult->fetch_all(MYSQLI_ASSOC);
        $tamuList = array_merge($tamuList, $engineeringList);
    } else {
        $error = 'Gagal mengambil data engineering: ' . $conn->error;
    }
} else {
    $error = 'Gagal mengambil data kurir: ' . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tamu_raw = $_POST['id_tamu'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');

    // Debug: tampilkan input yang diterima
    // echo '<div class="alert alert-info">POST id_tamu: ' . htmlspecialchars($id_tamu_raw) . '</div>';

    if (preg_match('/^(tamu|kilat|engineering)-(\d+)$/', $id_tamu_raw, $matches)) {
        $tamu_type = $matches[1];
        $id_tamu = (int)$matches[2];

        if ($id_tamu <= 0) {
            $error = 'ID tamu tidak valid.';
        } elseif (empty($alasan)) {
            $error = 'Alasan blacklist wajib diisi.';
        } else {
            // Cek apakah tamu sudah ada di blacklist
            $stmtCheck = $conn->prepare("SELECT id FROM blacklist WHERE id_tamu = ? AND tamu_type = ?");
            $stmtCheck->bind_param('is', $id_tamu, $tamu_type);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                $error = 'Tamu ini sudah masuk daftar blacklist.';
            } else {
                $stmtInsert = $conn->prepare("INSERT INTO blacklist (id_tamu, tamu_type, alasan) VALUES (?, ?, ?)");
                $stmtInsert->bind_param('iss', $id_tamu, $tamu_type, $alasan);
                if ($stmtInsert->execute()) {
                    $success = 'Blacklist berhasil ditambahkan.';
                    $_POST = [];
                } else {
                    $error = 'Gagal menyimpan data blacklist: ' . $conn->error;
                }
                $stmtInsert->close();
            }
            $stmtCheck->close();
        }
    } else {
        $error = 'Format tamu tidak valid.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Tambah Blacklist Tamu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Select2 (optional enhancement) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            padding: 5px 12px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h3>Tambah Blacklist Tamu</h3>
    <a href="data_blacklist.php" class="btn btn-secondary mb-3">← Kembali ke Daftar Blacklist</a>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="mb-3">
            <label for="id_tamu" class="form-label">Pilih Tamu</label>
            <select id="id_tamu" name="id_tamu" class="form-select" required>
                <option value="">-- Pilih Tamu --</option>
                <?php foreach ($tamuList as $tamu): 
                    $val = $tamu['tamu_type'] . '-' . $tamu['id'];
                ?>
                    <option value="<?= $val ?>" <?= (isset($_POST['id_tamu']) && $_POST['id_tamu'] === $val) ? 'selected' : '' ?>>
                        [<?= strtoupper($tamu['tamu_type']) ?>] <?= htmlspecialchars($tamu['nama_tamu']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="alasan" class="form-label">Alasan Blacklist</label>
            <textarea id="alasan" name="alasan" rows="4" class="form-control" required><?= htmlspecialchars($_POST['alasan'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>

<!-- Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('#id_tamu').select2({ placeholder: "-- Pilih Tamu --" });
    });
</script>
</body>
</html>