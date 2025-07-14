<?php
require_once '../../includes/auth.php';
checkRole(['fo']);
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $allowed_actions = ['accept' => 'diterima', 'reject' => 'ditolak'];

    if ($id > 0 && array_key_exists($action, $allowed_actions)) {
        $new_status = $allowed_actions[$action];

        $stmt = $conn->prepare("UPDATE panic_logs SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit;