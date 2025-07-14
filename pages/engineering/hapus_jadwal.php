<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $hapus = mysqli_query($conn, "DELETE FROM jadwal_hari_ini WHERE id = $id");

    if ($hapus) {
        header("Location: dashboard.php?msg=hapus_sukses");
        exit;
    } else {
        header("Location: dashboard.php?msg=hapus_gagal");
        exit;
    }
} else {
    header("Location: dashboard.php?msg=id_tidak_valid");
    exit;
}