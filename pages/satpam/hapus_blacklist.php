<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
checkRole(['admin', 'satpam']);

$id = $_GET['id'] ?? 0;
$id = (int) $id;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM blacklist WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header("Location: daftar_blacklist.php?success=1");
        exit;
    } else {
        header("Location: daftar_blacklist.php?error=gagal_hapus");
        exit;
    }
} else {
    header("Location: daftar_blacklist.php?error=id_tidak_valid");
    exit;
}