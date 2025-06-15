<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
checkRole(['admin']);

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("DELETE FROM notifikasi_template WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: notifikasi_template.php');
exit;