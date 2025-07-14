<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$izin = $_POST['izin_penghuni'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
    exit;
}

// Tentukan apakah ID milik tamu biasa atau kurir
$isKurir = false;
$checkKurir = $conn->prepare("SELECT id FROM tamu_kilat WHERE id = ?");
$checkKurir->bind_param('i', $id);
$checkKurir->execute();
$checkKurir->store_result();
if ($checkKurir->num_rows > 0) {
    $isKurir = true;
}
$checkKurir->close();

if ($isKurir) {
    // Hanya update status untuk kurir
    if ($status) {
        $stmt = $conn->prepare("UPDATE tamu_kilat SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada status untuk diupdate']);
    }
} else {
    // Tamu biasa
    $success = false;
    if ($status) {
        $stmt = $conn->prepare("UPDATE tamu SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();
    }
    if ($izin) {
        $stmt = $conn->prepare("UPDATE tamu SET izin_penghuni = ? WHERE id = ?");
        $stmt->bind_param("si", $izin, $id);
        $success = $stmt->execute() || $success;
        $stmt->close();
    }
    echo json_encode(['success' => $success]);
}
?>