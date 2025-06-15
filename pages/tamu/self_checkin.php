<?php
session_start();
require_once "../../config/db.php";

// Cek login dan role tamu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../../login.php");
    exit;
}

$userid = $_SESSION['userid'];
$error = '';
$success = '';
$qrSvg = '';
$foto_ktp_nama = '';

// Ambil data user tamu
$stmt = $conn->prepare("SELECT * FROM tamu_users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Default nilai form
$form = [
    'nama_tamu' => $user['nama_lengkap'] ?? '',
    'no_identitas' => '',
    'jenis_kelamin' => '',
    'no_hp' => $user['no_hp'] ?? '',
    'alamat' => '',
];

// Fungsi QR generator sederhana
function generateSimpleQrSvg($data) {
    $hash = hash('sha512', $data);
    $size = 21;
    $svgSize = $size * 10;
    $bits = '';

    for ($i = 0; $i < $size * $size; $i++) {
        $hexIndex = (int)floor($i / 4);
        $hexDigit = hexdec($hash[$hexIndex]);
        $bitPosition = 3 - ($i % 4);
        $bit = ($hexDigit >> $bitPosition) & 1;
        $bits .= $bit;
    }

    $svg = "<svg width='{$svgSize}' height='{$svgSize}' xmlns='http://www.w3.org/2000/svg' style='background:#fff'>";
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $i = $y * $size + $x;
            if ($bits[$i] === '1') {
                $px = $x * 10;
                $py = $y * 10;
                $svg .= "<rect x='{$px}' y='{$py}' width='10' height='10' fill='#000'/>";
            }
        }
    }
    $svg .= "</svg>";
    return $svg;
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_tamu'] = trim($_POST['nama_tamu'] ?? '');
    $form['no_identitas'] = trim($_POST['no_identitas'] ?? '');
    $form['jenis_kelamin'] = $_POST['jenis_kelamin'] ?? '';
    $form['no_hp'] = trim($_POST['no_hp'] ?? '');
    $form['alamat'] = trim($_POST['alamat'] ?? '');

    // Validasi
    if (
        empty($form['nama_tamu']) || empty($form['no_identitas']) || empty($form['jenis_kelamin']) ||
        empty($form['no_hp']) || empty($form['alamat'])
    ) {
        $error = "Semua field harus diisi!";
    }

    // Validasi & Upload Foto KTP
    if (!$error && isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] === UPLOAD_ERR_OK) {
        $fotoTmp = $_FILES['foto_ktp']['tmp_name'];
        $fotoNama = basename($_FILES['foto_ktp']['name']);
        $ext = strtolower(pathinfo($fotoNama, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $error = "Format foto KTP harus JPG, JPEG, atau PNG.";
        } else {
            $foto_ktp_nama = uniqid('ktp_') . '.' . $ext;
            $uploadPath = __DIR__ . '/../../uploads/ktp/' . $foto_ktp_nama;
            if (!move_uploaded_file($fotoTmp, $uploadPath)) {
                $error = "Gagal mengunggah foto KTP.";
            }
        }
    } else if (!$error) {
        $error = "Foto KTP wajib diunggah.";
    }

    // Simpan ke database
    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO tamu (nama_tamu, no_identitas, jenis_kelamin, no_hp, alamat, foto_ktp, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssss", $form['nama_tamu'], $form['no_identitas'], $form['jenis_kelamin'], $form['no_hp'], $form['alamat'], $foto_ktp_nama);

        if ($stmt->execute()) {
            $last_id = $conn->insert_id;
            $success = "Data berhasil disimpan. Ini adalah QR Code kamu:";
            $qrSvg = generateSimpleQrSvg((string)$last_id);
        } else {
            $error = "Terjadi kesalahan saat menyimpan data.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Self Check-In - Visitor Pass</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #3d7b65; min-height: 100vh; padding: 20px; color: white; width: 220px; }
        .sidebar a { color: #e0f0e6; text-decoration: none; display: block; padding: 10px 0; border-radius: 6px; }
        .sidebar a:hover { background-color: #2e5e4d; color: #ffffff; }
        .logo { font-size: 1.3rem; font-weight: bold; margin-bottom: 30px; display: flex; align-items: center; gap: 8px; }
        .logo img { width: 30px; border-radius: 6px; background: #fff; padding: 3px; }
        .sidebar-toggler { display: none; }
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; top: 0; left: -250px; width: 220px; transition: left 0.3s ease; z-index: 999; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: block; margin: 10px 0 20px 0; background-color: #3d7b65; color: white; border: none; padding: 8px 12px; border-radius: 5px; }
            .overlay.show { display: block; }
        }
        .content { flex-grow: 1; padding: 2rem; }
        .qr-container { margin-top: 20px; background: white; padding: 15px; border: 2px solid #3d7b65; display: inline-block; text-align: center; }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../../assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass - Tamu
        </div>
        <div><strong>Tamu:</strong> <?= htmlspecialchars($user['nama_lengkap'] ?? '') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="self_checkin.php">Self Check-In</a>
        <a href="lokasi.php">Lihat Lokasi</a>
        <a href="profile.php">Profil Saya</a>
        <hr class="border-light" />
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <div class="content">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h3>Self Check-In</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <div id="print-area" class="qr-container">
                <h5><?= htmlspecialchars($form['nama_tamu']) ?></h5>
                <p><small><?= htmlspecialchars($form['no_identitas']) ?></small></p>
                <?= $qrSvg ?>
                <br>
                <?php if ($foto_ktp_nama): ?>
                    <p class="mt-3"><strong>Foto KTP:</strong></p>
                    <img src="../../uploads/ktp/<?= htmlspecialchars($foto_ktp_nama) ?>" alt="Foto KTP" style="max-width:200px;border:1px solid #ccc;padding:5px;">
                <?php endif; ?>
                <br>
                <button onclick="printQR()" class="btn btn-primary mt-3">🖨️ Print QR</button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama_tamu" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_tamu" name="nama_tamu" value="<?= htmlspecialchars($form['nama_tamu']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="no_identitas" class="form-label">No. Identitas</label>
                <input type="text" class="form-control" id="no_identitas" name="no_identitas" value="<?= htmlspecialchars($form['no_identitas']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Jenis Kelamin</label>
                <select class="form-select" name="jenis_kelamin" required>
                    <option value="" disabled <?= $form['jenis_kelamin'] === '' ? 'selected' : '' ?>>Pilih Jenis Kelamin</option>
                    <option value="L" <?= $form['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $form['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="no_hp" class="form-label">No. Handphone</label>
                <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= htmlspecialchars($form['no_hp']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($form['alamat']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="foto_ktp" class="form-label">Upload Foto KTP</label>
                <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-success">Submit</button>
            <a href="dashboard.php" class="btn btn-secondary ms-2">Batal</a>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");
        sidebar.classList.toggle("show");
        overlay.classList.toggle("show");
    }

    function printQR() {
        const printContent = document.getElementById("print-area").innerHTML;
        const printWindow = window.open('', '', 'height=600,width=400');
        printWindow.document.write('<html><head><title>QR Code</title><style>body{font-family:Arial;text-align:center;padding:30px;} .qr-container{border:2px solid #000;padding:20px;display:inline-block;} h5{margin:10px 0 5px 0;} small{display:block;margin-bottom:10px;}</style></head><body>');
        printWindow.document.write(printContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>