<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID tidak valid.');
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM satpam_users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: data_satpam.php?msg=hapus_sukses");
    exit;
} else {
    echo "Gagal menghapus: " . $conn->error;
}
?>