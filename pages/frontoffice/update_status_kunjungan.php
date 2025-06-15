<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

$id     = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$allowed = ['menunggu','disetujui','ditolak','selesai'];

if (!$id || !in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Cari jenis_izin di tabel kunjungan
$qt = $conn->prepare("SELECT jenis_izin FROM kunjungan WHERE id = ?");
$qt->bind_param("i", $id);
$qt->execute();
$row = $qt->get_result()->fetch_assoc();
$qt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Kunjungan tidak ditemukan']);
    exit;
}

$jenis = $row['jenis_izin'];
if ($jenis === 'penghuni') {
    $q = $conn->prepare("UPDATE izin_kunjungan_penghuni SET status = ? WHERE id = ?");
} else if ($jenis === 'engineering') {
    $q = $conn->prepare("UPDATE izin_kunjungan_engineering SET status = ? WHERE id = ?");
} else {
    echo json_encode(['success' => false, 'message' => 'Jenis izin tidak didukung']);
    exit;
}

// id yang disimpan di ‘kunjungan.id_izin’ = primary key di masing-masing tabel izin
// Cari id_izin dulu:
$q2 = $conn->prepare("SELECT id_izin FROM kunjungan WHERE id = ?");
$q2->bind_param("i", $id);
$q2->execute();
$id_izin = $q2->get_result()->fetch_assoc()['id_izin'];
$q2->close();

$q->bind_param("si", $status, $id_izin);
$ok = $q->execute();
$q->close();

echo json_encode(['success' => $ok]);
?>