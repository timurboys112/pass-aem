<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

// Ambil data input dengan sanitasi sederhana
$nama = trim($_POST['nama'] ?? '');
$no_identitas = trim($_POST['no_ktp'] ?? '');
$tujuan = trim($_POST['tujuan'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$foto_ktp = $_FILES['foto_ktp'] ?? null;

// Validasi input wajib
if (
    empty($nama) ||
    empty($no_identitas) ||
    empty($tujuan) ||
    empty($jenis_kelamin) ||
    empty($no_hp) ||
    empty($alamat) ||
    !$foto_ktp ||
    $foto_ktp['error'] !== UPLOAD_ERR_OK
) {
    die("Data tidak lengkap atau file foto bermasalah. Harap isi semua field dengan benar.");
}

// Cek apakah tamu sudah check-in hari ini
$tanggal_hari_ini = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) FROM tamu WHERE no_identitas = ? AND DATE(waktu_checkin) = ?");
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("ss", $no_identitas, $tanggal_hari_ini);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    die("Tamu ini sudah check-in hari ini.");
}

// Upload foto KTP
$target_dir = "../../uploads/ktp/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}
// Buat nama file unik dan aman
$ext = pathinfo($foto_ktp["name"], PATHINFO_EXTENSION);
$foto_name = time() . "_" . preg_replace("/[^a-zA-Z0-9_-]/", "_", basename($foto_ktp["name"], ".$ext")) . "." . $ext;
$target_file = $target_dir . $foto_name;

if (!move_uploaded_file($foto_ktp["tmp_name"], $target_file)) {
    die("Gagal mengupload foto KTP.");
}

// Simpan data ke database
$status = 'pending';
$waktu_checkin = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO tamu (nama_tamu, no_identitas, tujuan, jenis_kelamin, no_hp, alamat, foto_ktp, status, waktu_checkin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("sssssssss", $nama, $no_identitas, $tujuan, $jenis_kelamin, $no_hp, $alamat, $foto_name, $status, $waktu_checkin);

if ($stmt->execute()) {
    header("Location: form_checkin.php?success=1&nama=" . urlencode($nama));
    exit;
} else {
    die("Gagal menyimpan data: " . $stmt->error);
}

$stmt->close();
$conn->close();