<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

$userId = $_SESSION['id_user'] ?? null;
$tanggalHariIni = date('Y-m-d');

// Proses Hapus
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM catatan_shift WHERE id = ? AND id_user = ?");
    $stmt->bind_param("ii", $deleteId, $userId);
    $stmt->execute();
    header("Location: catatan_shift.php");
    exit;
}

// Ambil data untuk Edit (jika ada)
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM catatan_shift WHERE id = ? AND id_user = ?");
    $stmt->bind_param("ii", $editId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $editData = $result->fetch_assoc();
    }
    $stmt->close();
}

// Proses Simpan / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shift = $_POST['shift'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    $editId = $_POST['edit_id'] ?? null;

    if ($shift && $catatan) {
        if ($editId) {
            $stmt = $conn->prepare("UPDATE catatan_shift SET shift = ?, catatan = ?, created_at = NOW() WHERE id = ? AND id_user = ?");
            $stmt->bind_param("ssii", $shift, $catatan, $editId, $userId);
        } else {
            $stmt = $conn->prepare("INSERT INTO catatan_shift (id_user, tanggal, shift, catatan, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $userId, $tanggalHariIni, $shift, $catatan);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: catatan_shift.php?success=1");
        exit;
    }
}

// Ambil catatan shift hari ini
$catatanHariIni = [];
$stmt = $conn->prepare("
    SELECT cs.*, su.username 
    FROM catatan_shift cs 
    JOIN satpam_users su ON cs.id_user = su.id 
    WHERE cs.tanggal = ?
    ORDER BY cs.created_at DESC
");
$stmt->bind_param("s", $tanggalHariIni);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $catatanHariIni[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Catatan Shift Satpam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #2e7d32; min-height: 100vh; padding: 20px; color: white; width: 220px; }
        .sidebar a { color: #c8e6c9; text-decoration: none; display: block; padding: 10px 0; font-weight: 500; }
        .sidebar a:hover { background-color: #1b5e20; border-radius: 6px; }
        .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; }
        .logo img { width: 30px; margin-right: 8px; border-radius: 6px; background: #fff; padding: 3px; }
        .sidebar-toggler { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; width: 220px; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px; background-color: #2e7d32; color: white; border: none; padding: 8px 12px; border-radius: 5px; font-weight: bold; }
            .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 998; }
            .overlay.show { display: block; }
        }
        @media print {
            .no-print, .sidebar, .sidebar-toggler, .overlay { display: none !important; }
            body { margin: 0; padding: 0; }
            .flex-grow-1 { width: 100%; padding: 0; }
        }
    </style>
</head>

<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass
        </div>
        <div><strong>Satpam:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="scan.php">Scan QR</a>
        <a href="validasi_manual.php">Validasi Manual</a>
        <a href="form_kurir.php">Check-In Kurir</a>
        <a href="daftar_masuk.php">Daftar Masuk</a>
        <a href="daftar_keluar.php">Daftar Keluar</a>
        <a href="tamu_di_lokasi.php">Tamu di Lokasi</a>
        <a href="laporan_harian.php">Laporan Harian</a>
        <a href="riwayat_scan.php">Riwayat Scan</a>
        <a href="catatan_shift.php">Catatan Shift</a>
        <a href="daftar_blacklist.php">Daftar Blacklist</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Konten utama -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler no-print" onclick="toggleSidebar()">☰ Menu</button>

        <div class="no-print mb-4 d-flex justify-content-between align-items-center">
            <h4>Catatan Shift - <?= date('d M Y') ?></h4>
            <button class="btn btn-primary" onclick="window.print()">🖨 Cetak</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Catatan berhasil disimpan.</div>
        <?php endif; ?>

        <!-- Form Tambah/Edit Catatan -->
        <form method="POST" class="mb-4" novalidate>
            <?php if ($editData): ?>
                <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                <div class="alert alert-warning">Sedang mengedit catatan shift.</div>
            <?php endif; ?>
            <div class="mb-3">
                <label for="shift" class="form-label">Shift</label>
                <select name="shift" id="shift" class="form-select" required>
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi" <?= ($editData['shift'] ?? '') === 'Pagi' ? 'selected' : '' ?>>Pagi</option>
                    <option value="Siang" <?= ($editData['shift'] ?? '') === 'Siang' ? 'selected' : '' ?>>Siang</option>
                    <option value="Malam" <?= ($editData['shift'] ?? '') === 'Malam' ? 'selected' : '' ?>>Malam</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="catatan" class="form-label">Catatan</label>
                <textarea name="catatan" id="catatan" class="form-control" rows="4" required><?= htmlspecialchars($editData['catatan'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Simpan Catatan</button>
            <?php if ($editData): ?>
                <a href="catatan_shift.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>

        <!-- Daftar Catatan Hari Ini -->
        <h5>Catatan Shift Hari Ini</h5>
        <?php if (count($catatanHariIni) > 0): ?>
            <ul class="list-group">
                <?php foreach ($catatanHariIni as $cat): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($cat['shift']) ?> (<?= htmlspecialchars($cat['username']) ?>):</strong><br />
                        <?= nl2br(htmlspecialchars($cat['catatan'])) ?>
                        <div class="small text-muted mt-1"><?= date('H:i', strtotime($cat['created_at'])) ?></div>
                        <?php if ($cat['id_user'] == $userId): ?>
                            <div class="mt-2">
                                <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?= $cat['id'] ?>" onclick="return confirm('Hapus catatan ini?');" class="btn btn-sm btn-danger">Hapus</a>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-muted">Belum ada catatan shift hari ini.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("show");
        document.getElementById("overlay").classList.toggle("show");
    }
</script>

</body>
</html>