<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
checkRole(['admin']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: data_blacklist.php?error=id_tidak_valid");
    exit;
}

$id = intval($_GET['id']);

// Cek apakah ID ada di database
$check = $conn->prepare("SELECT id FROM blacklist WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    header("Location: data_blacklist.php?error=id_tidak_valid");
    exit;
}

// Lanjutkan hapus
$stmt = $conn->prepare("DELETE FROM blacklist WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: data_blacklist.php?success=1");
} else {
    header("Location: data_blacklist.php?error=gagal_hapus");
}
exit;