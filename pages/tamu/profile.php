<?php
session_start();
require_once "../../config/db.php";

// Cek login dan role tamu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../../login.php");
    exit;
}

$userid = $_SESSION['userid'];

// Ambil data user tamu
$stmt = $conn->prepare("SELECT username, nama_lengkap, email, no_hp, password FROM tamu_users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

if (!$user = $result->fetch_assoc()) {
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

$error = $success = "";

// Proses update profil
if (isset($_POST['update_profile'])) {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);

    if (empty($nama_lengkap) || empty($email) || empty($no_hp)) {
        $error = "Semua field profil wajib diisi!";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        $update_stmt = $conn->prepare("UPDATE tamu_users SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $nama_lengkap, $email, $no_hp, $userid);
        if ($update_stmt->execute()) {
            $success = "Profil berhasil diperbarui.";
            $user['nama_lengkap'] = $nama_lengkap;
            $user['email'] = $email;
            $user['no_hp'] = $no_hp;
        } else {
            $error = "Gagal memperbarui profil: " . $conn->error;
        }
    }
}

// Proses ganti password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Semua field password wajib diisi!";
    } else if (md5($current_password) !== $user['password']) {
        $error = "Password saat ini salah!";
    } else if ($new_password !== $confirm_password) {
        $error = "Password baru dan konfirmasi tidak cocok!";
    } else {
        $new_password_hashed = md5($new_password);
        $update_pass_stmt = $conn->prepare("UPDATE tamu_users SET password = ? WHERE id = ?");
        $update_pass_stmt->bind_param("si", $new_password_hashed, $userid);
        if ($update_pass_stmt->execute()) {
            $success = "Password berhasil diubah.";
            $user['password'] = $new_password_hashed;
        } else {
            $error = "Gagal mengubah password: " . $conn->error;
        }
    }
}
$nama = htmlspecialchars($user['nama_lengkap']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - Tamu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            border-radius: 6px;
        }

        .sidebar a:hover {
            background-color: #2e5e4d;
            color: #ffffff;
        }

        .logo {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo img {
            width: 30px;
            border-radius: 6px;
            background: #fff;
            padding: 3px;
        }

        .sidebar-toggler {
            display: none;
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
                margin: 10px 0 20px 0;
                background-color: #3d7b65;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
            }

            .overlay.show {
                display: block;
            }
        }

        .content {
            flex-grow: 1;
            padding: 2rem;
        }

        .user-greeting {
            color: #3d7b65;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../../assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass - Tamu
        </div>
        <div><strong>Tamu:</strong> <?= $nama ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="self_checkin.php">Self Check-In</a>
        <a href="lokasi.php">Lihat Lokasi</a>
        <a href="profile.php">Profil Saya</a>
        <hr class="border-light" />
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay untuk sidebar mobile -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Konten Utama -->
    <div class="content">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

        <h2 class="user-greeting">Profil Saya</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Form Update Profil -->
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" readonly class="form-control-plaintext" value="<?= htmlspecialchars($user['username']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" required value="<?= htmlspecialchars($user['nama_lengkap']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" class="form-control" name="no_hp" required value="<?= htmlspecialchars($user['no_hp']) ?>">
            </div>
            <button type="submit" name="update_profile" class="btn btn-success">Update Profil</button>
        </form>

        <hr class="my-4">

        <!-- Form Ganti Password -->
        <h4>Ganti Password</h4>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Password Saat Ini</label>
                <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" class="form-control" name="new_password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-warning">Ganti Password</button>
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

</body>
</html>