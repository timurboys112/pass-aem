<?php
require_once '../../includes/auth.php';
checkRole(['admin']);
require_once '../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die('ID user tidak valid.');
}

// Ambil role dan id_role_user dari users
$sql = "SELECT role, id_role_user FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res) == 0) {
    die('User tidak ditemukan.');
}
$user = mysqli_fetch_assoc($res);
$role = $user['role'];
$id_role_user = $user['id_role_user'];

$role_tables = [
    'admin' => 'admin_users',
    'fo' => 'fo_users',
    'penghuni' => 'penghuni_users',
    'engineering' => 'engineering_users',
    'satpam' => 'satpam_users',
    'tamu' => 'tamu_users'
];

$table_role = $role_tables[$role] ?? null;
if (!$table_role) {
    die('Role user tidak valid.');
}

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
    // Hapus data dari tabel role
    $stmt1 = mysqli_prepare($conn, "DELETE FROM $table_role WHERE id = ?");
    mysqli_stmt_bind_param($stmt1, "i", $id_role_user);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // Hapus data dari tabel users
    $stmt2 = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    mysqli_commit($conn);
    header("Location: akun_pengguna.php?msg=delete_success");
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Gagal menghapus pengguna: " . $e->getMessage());
}