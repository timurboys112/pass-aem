<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE panic_logs SET status = 'resolved' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit;