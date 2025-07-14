<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role'])) {
    header("Location: /pass-aem/login.php");
    exit;
}

// Contoh: batasi akses hanya untuk role tertentu
function checkRole($allowedRoles = []) {
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        echo "<script>alert('Akses ditolak!');window.location.href='/pass-aem/index.php';</script>";
        exit;
    }
}