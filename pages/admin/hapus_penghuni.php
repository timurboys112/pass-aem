<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id) || $id == 0) {
    header("Location: data_penghuni.php");
    exit;
}

$sql = "DELETE FROM penghuni_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: data_penghuni.php?success=hapus");
    exit;
} else {
    echo "<p>Gagal menghapus data. Silakan coba lagi.</p>";
    echo "<p><a href='data_penghuni.php'>Kembali</a></p>";
}