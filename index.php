<?php
session_start();

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Redirect berdasarkan role
switch ($_SESSION['role']) {
    case 'admin':
        header("Location: pages/admin/dashboard.php");
        break;
    case 'fo':
        header("Location: pages/frontoffice/dashboard.php");
        break;
    case 'satpam':
        header("Location: pages/satpam/dashboard.php");
        break;
    case 'penghuni':
        header("Location: pages/penghuni/dashboard.php");
        break;
    case 'engineering':
        header("Location: pages/engineering/dashboard.php");
        break;
    case 'tamu':
        header("Location: pages/tamu/dashboard.php");
        break;
    default:
        echo "Role tidak dikenal.";
}
exit;
?>