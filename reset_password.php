<?php
require_once "config/db.php";

if (!isset($_GET['token'])) {
    die("Token tidak valid.");
}

$token = $_GET['token'];

// Cek token pada tabel password_resets
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if (!$user = $result->fetch_assoc()) {
    die("Token tidak ditemukan atau sudah kadaluarsa.");
}

$email = $user['email'];

// Tabel pengguna berdasarkan role
$role_tables = [
    'admin' => 'admin_users',
    'fo' => 'fo_users',
    'penghuni' => 'penghuni_users',
    'engineering' => 'engineering_users',
    'satpam' => 'satpam_users',
    'tamu' => 'tamu_users'
];

// Fungsi update password ke tabel yang sesuai
function updateUserPassword($conn, $email, $hashed_password, $role_tables) {
    foreach ($role_tables as $table) {
        $stmt = $conn->prepare("SELECT username FROM $table WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->fetch_assoc()) {
            $stmt_update = $conn->prepare("UPDATE $table SET password = ? WHERE username = ?");
            $stmt_update->bind_param("ss", $hashed_password, $email);
            return $stmt_update->execute();
        }
    }
    return false;
}

// Proses reset password
if (isset($_POST['reset'])) {
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "Password tidak cocok.";
    } elseif (strlen($new_pass) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $hashed = md5($new_pass); // Bisa ganti ke password_hash untuk keamanan lebih

        if (updateUserPassword($conn, $email, $hashed, $role_tables)) {
            // Hapus token setelah sukses
            $stmt_del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt_del->bind_param("s", $token);
            $stmt_del->execute();

            $success = "Password berhasil direset. <a href='login.php'>Klik di sini untuk login</a>.";
        } else {
            $error = "User tidak ditemukan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Reset Password - AEM Visitor Pass</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #A8E6CF, #81C784);
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        .btn-success {
            border-radius: 10px;
            font-weight: 600;
        }
        .alert {
            font-size: 14px;
        }
        h4 {
            font-weight: 700;
            margin-bottom: 20px;
        }
        a {
            color: #388E3C;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="card">
    <h4 class="text-success text-center">Atur Ulang Password</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!isset($success)): ?>
    <form method="POST" novalidate>
        <div class="mb-3">
            <label for="password" class="form-label">Password Baru</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="6" />
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Ulangi Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" />
        </div>
        <button type="submit" name="reset" class="btn btn-success w-100">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>