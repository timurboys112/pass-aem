<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $nama_pengantar = trim($_POST['nama_pengantar'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $layanan = trim($_POST['layanan'] ?? '');
    $tujuan_unit = trim($_POST['tujuan_unit'] ?? '');
    $waktu_masuk = date('Y-m-d H:i:s');

    if (!$nama_pengantar || !$nik || !$layanan || !$tujuan_unit) {
        // Redirect kembali dengan error jika data kurang lengkap
        header("Location: form_kurir.php?error=Data tidak lengkap");
        exit();
    }

    // Cek upload foto KTP
    if (!isset($_FILES['foto_ktp']) || $_FILES['foto_ktp']['error'] !== UPLOAD_ERR_OK) {
        header("Location: form_kurir.php?error=Upload foto KTP gagal");
        exit();
    }

    // Validasi ekstensi file foto KTP
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $foto_ktp_name = $_FILES['foto_ktp']['name'];
    $foto_ktp_tmp = $_FILES['foto_ktp']['tmp_name'];
    $foto_ktp_ext = strtolower(pathinfo($foto_ktp_name, PATHINFO_EXTENSION));

    if (!in_array($foto_ktp_ext, $allowed_ext)) {
        header("Location: form_kurir.php?error=Format file foto KTP tidak didukung");
        exit();
    }

    // Buat folder jika belum ada
    $upload_dir = '../../uploads/foto_ktp/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate nama file baru untuk menghindari bentrok
    $foto_ktp_newname = 'ktp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $foto_ktp_ext;
    $foto_ktp_path = $upload_dir . $foto_ktp_newname;

    // Pindahkan file upload
    if (!move_uploaded_file($foto_ktp_tmp, $foto_ktp_path)) {
        header("Location: form_kurir.php?error=Gagal menyimpan file foto KTP");
        exit();
    }

    // Generate QR code data (sederhana)
    $qr_code_data = base64_encode($nama_pengantar . '|' . $waktu_masuk);

    // Simpan ke DB
    $stmt = $conn->prepare("INSERT INTO tamu_kilat (nama_pengantar, nik, layanan, tujuan_unit, waktu_masuk, foto_ktp, qr_code_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        header("Location: form_kurir.php?error=Kesalahan database: " . urlencode($conn->error));
        exit();
    }
    $stmt->bind_param("sssssss", $nama_pengantar, $nik, $layanan, $tujuan_unit, $waktu_masuk, $foto_ktp_newname, $qr_code_data);

    if ($stmt->execute()) {
        header("Location: form_kurir.php?success=1&nama=" . urlencode($nama_pengantar));
        exit();
    } else {
        header("Location: form_kurir.php?error=Gagal menyimpan data");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: form_kurir.php");
    exit();
}