<?php
require_once __DIR__ . '/../config/db.php';

$notifications = [];

$role = $_SESSION['user']['role'] ?? null;

if ($role) {
    $stmt = $conn->prepare("SELECT * FROM notifikasi WHERE status = 'unread' AND (role = ? OR role = 'semua') ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
}