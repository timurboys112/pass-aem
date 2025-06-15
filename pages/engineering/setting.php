<?php
session_start();
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

// Ambil data user saat ini dari DB
$userId = $_SESSION['user']['id'] ?? 0;
$user = [];

if ($userId) {
    $result = $conn->query("SELECT nama, username FROM users WHERE id = $userId");
    if ($result) {
        $user = $result->fetch_assoc() ?: [];
    }
}

$msg = '';
$msgClass = '';

if (isset($_POST['submit'])) {
    $nama = trim($_POST['nama']);
    $password = $_POST['password'];

    if ($nama === '') {
        $msg = "Nama tidak boleh kosong.";
        $msgClass = 'danger';
    } else {
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET nama = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nama, $passwordHash, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama = ? WHERE id = ?");
            $stmt->bind_param("si", $nama, $userId);
        }

        if ($stmt->execute()) {
            $msg = "Pengaturan berhasil disimpan.";
            $msgClass = 'success';
            $user['nama'] = $nama;
        } else {
            $msg = "Gagal menyimpan pengaturan: " . $stmt->error;
            $msgClass = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Pengaturan Akun - Engineering</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #3d7b65;
            min-height: 100vh;
            padding: 20px;
            color: white;
            width: 220px;
        }
        .sidebar a {
            color: #e0f0e6;
            text-decoration: none;
            display: block;
            padding: 10px 0;
        }
        .sidebar a:hover,
        .sidebar .active {
            background-color: #2e5e4d;
            border-radius: 6px;
        }
        .logo {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }
        .logo img {
            width: 30px;
            margin-right: 8px;
            border-radius: 6px;
            background: #fff;
            padding: 3px;
        }
        .sidebar-toggler {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                transition: left 0.3s ease;
                z-index: 999;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggler {
                display: block;
                margin: 10px;
                background-color: #3d7b65;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 998;
            }
            .overlay.show {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass
        </div>
        <div><strong>Engineering:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="daftar_kunjungan.php">Daftar Kunjungan</a>
        <a href="form_izin_tamu.php">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="laporan_teknisi.php">Laporan Teknisi</a>
        <a href="setting.php">Pengaturan</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

        <h4>Pengaturan Akun</h4>

        <?php if($msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msgClass) ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" class="mb-5">
            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password Baru (kosongkan jika tidak diganti)</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>
            <button name="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");
        sidebar.classList.toggle("show");
        overlay.classList.toggle("show");
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>