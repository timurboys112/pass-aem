<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'tamu') {
    require_once "config/db.php";

    $userId = $_SESSION['userid'];
    $timestamp = date('Y-m-d H:i:s');

    // Ambil data tamu
    $stmt = $conn->prepare("SELECT nama_lengkap, email FROM tamu_users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $nama = $user['nama_lengkap'];
        $email = $user['email'];
    } else {
        // User tidak ditemukan, logout dan redirect
        session_destroy();
        header("Location: login.php");
        exit;
    }
    $stmt->close();

    // Simpan ke tabel panic_logs
    $stmt = $conn->prepare("INSERT INTO panic_logs (user_id, time) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $timestamp);
    $stmt->execute();
    $stmt->close();

    // Kirim notifikasi email ke pihak terkait
    $to = "admin@example.com,satpam@example.com,engineering@example.com,frontoffice@example.com"; // Ganti dengan email sebenarnya
    $subject = "🚨 Panic Alert dari $nama";
    $message = "Panic button telah ditekan oleh tamu:\n\n"
             . "Nama: $nama\n"
             . "Email: $email\n"
             . "Waktu: $timestamp\n\n"
             . "Mohon segera ditindaklanjuti.";

    $headers = "From: panic@visitorpass.local\r\n"
             . "Reply-To: noreply@visitorpass.local\r\n"
             . "X-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $headers);

    // Redirect ke halaman sukses (bisa kamu buat konfirmasi pengiriman)
    header("Location: pages/tamu/panic_success.php");
    exit;

} else {
    header("Location: login.php");
    exit;
}