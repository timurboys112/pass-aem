<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Visitor Pass Digital</title>
    <link href="/aem-visitor/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/aem-visitor/index.php">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" height="30" class="me-2">
            Visitor Pass
        </a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">
                <?= ucfirst($_SESSION['role']) ?>: <?= $_SESSION['username'] ?? 'Tamu' ?>
            </span>
            <a href="/aem-visitor/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-4">