<?php
require_once '../../includes/auth.php';
checkRole(['penghuni']);
require_once '../../config/db.php';

$userId = $_SESSION['user']['id'] ?? null;
$userData = [];

// Ambil data user
if ($userId) {
    $stmt = $conn->prepare("SELECT username, email, nama_lengkap FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $userData = $result->fetch_assoc();
    } else {
        $error = "Pengguna tidak ditemukan.";
    }
}

// Tangani POST
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $password = $_POST['password'] ?? '';

    if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, nama_lengkap = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $email, $nama_lengkap, $hashed, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, nama_lengkap = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $nama_lengkap, $userId);
    }

    if ($stmt->execute()) {
        $success = "Pengaturan akun berhasil diperbarui.";
        $userData = ['username' => $username, 'email' => $email, 'nama_lengkap' => $nama_lengkap];
    } else {
        $error = "Gagal memperbarui: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Pengaturan Akun</title>
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
        .sidebar a:hover {
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
        }
        .sidebar-toggler {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 220px;
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
                background: rgba(0, 0, 0, 0.5);
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
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo"
                 style="height:38px; width:auto; border-radius:6px; background:#fff; padding:3px;" />
            Visitor Pass
        </div>
        <div><strong>Penghuni:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="buat_izin.php">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="data_tamu.php">Data Tamu</a>
        <a href="riwayat_kunjungan.php">Riwayat Kunjungan</a>
        <a href="laporan_penghuni.php">Laporan Penghuni</a>
        <a href="blacklist_laporan.php">Blacklist & Masalah</a>
        <a href="pengaturan_akun.php">Pengaturan Akun</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay untuk sidebar mobile -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Konten -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-4">Pengaturan Akun</h4>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($userData['nama_lengkap'] ?? '') ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru (Opsional)</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin ganti password">
            </div>
            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
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