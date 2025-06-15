<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Panic Terkirim - Visitor Pass</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
            padding: 2rem;
        }
        .panic-message {
            max-width: 500px;
            margin: 4rem auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="panic-message">
        <h1 class="text-danger mb-4">🚨 Panic Alert Terkirim!</h1>
        <p class="mb-4 fs-5">
            Tim keamanan sudah diberitahu dan akan segera merespons.<br />
            Tetap tenang dan tunggu bantuan datang.
        </p>
        <a href="dashboard.php" class="btn btn-primary mb-3">Kembali ke Dashboard</a>
    </div>

    <?php if ($_SESSION['role'] === 'tamu'): ?>
        <!-- Panic Button fixed di pojok kanan bawah -->
        <div class="position-fixed bottom-0 end-0 m-4" style="z-index: 9999;">
            <button type="button" class="btn btn-danger btn-lg shadow-lg" data-bs-toggle="modal" data-bs-target="#panicModal" onclick="playAlarm()">
                🚨 Panic Button
            </button>
        </div>

        <!-- Modal Bootstrap untuk konfirmasi -->
        <div class="modal fade" id="panicModal" tabindex="-1" aria-labelledby="panicModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="panicModalLabel">Konfirmasi Panic</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopAlarm()"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin mengirim <strong>panic alert</strong>? Tim keamanan akan segera diberitahu.
                    </div>
                    <div class="modal-footer">
                        <form action="../../panic_handler.php" method="post">
                            <button type="submit" class="btn btn-danger">Ya, Kirim Panic</button>
                        </form>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopAlarm()">Batal</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio Alarm -->
        <audio id="alarmSound" preload="auto">
            <source src="../../assets/audio/SIREN ALERT ALARM SOUND.mp3" type="audio/mpeg" />
        </audio>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function playAlarm() {
                const alarm = document.getElementById("alarmSound");
                alarm.play();
            }
            function stopAlarm() {
                const alarm = document.getElementById("alarmSound");
                alarm.pause();
                alarm.currentTime = 0;
            }
        </script>
    <?php endif; ?>

</body>
</html>