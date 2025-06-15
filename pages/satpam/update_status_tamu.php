<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request salah']);
    exit;
}

$id     = $_POST['id']     ?? null;
$status = $_POST['status'] ?? '';

if (!$id || !ctype_digit($id)) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

// mapping label dan slug → slug
$normalize = [
    // label → slug
    'check in'            => 'checkedin',
    'check-in'            => 'checkedin',
    'check out'           => 'checkedout',
    'check-out'           => 'checkedout',
    'checkedin'           => 'checkedin',
    'checkedout'          => 'checkedout',
    'pending'             => 'pending',
    'menunggu di lobby'   => 'menunggu',
    'menunggu'            => 'menunggu',
    'ditolak'             => 'ditolak',
    'diizinkan'           => 'diizinkan',
    'blacklist'           => 'blacklist',
    'kurir'               => 'kurir',      // kalau mau handle kurir juga
];

// normalize input: lowercase + trim
$key = strtolower(trim($status));

// normalize hyphens/spaces
$key = str_replace('-', ' ', $key);

// cek validasi
if (!isset($normalize[$key])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak valid: status "' . htmlspecialchars($status) . '" tidak dikenal'
    ]);
    exit;
}

$realStatus = $normalize[$key];

// sekarang update
$stmt = $conn->prepare("UPDATE tamu SET status = ? WHERE id = ?");
$stmt->bind_param('si', $realStatus, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal update database: ' . $stmt->error
    ]);
}

$stmt->close();