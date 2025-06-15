<?php
require_once '../../includes/auth.php';
checkRole(['satpam']);
require_once '../../config/db.php';

// Inisialisasi variabel
$info_tamu = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input dengan trim dan basic sanitasi
    $nama = trim($_POST['nama'] ?? '');
    $no_identitas = trim($_POST['no_ktp'] ?? ''); // sesuaikan nama input form tetap no_ktp
    $tujuan = trim($_POST['tujuan'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $waktu_checkin = date('Y-m-d H:i:s');
    $status = 'checkedin'; // default status, bisa disesuaikan

    // Validasi sederhana
    if (!$nama || !$no_identitas || !$tujuan || !$jenis_kelamin || !$no_hp || !$alamat) {
        $message = "Semua field harus diisi!";
    } elseif (!isset($_FILES['foto_ktp']) || $_FILES['foto_ktp']['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload foto KTP gagal atau belum dipilih!";
    } else {
        // Proses upload foto KTP
        $foto_ktp_name = $_FILES['foto_ktp']['name'];
        $foto_ktp_tmp = $_FILES['foto_ktp']['tmp_name'];
        $foto_ktp_ext = strtolower(pathinfo($foto_ktp_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg','jpeg','png','gif'];

        if (!in_array($foto_ktp_ext, $allowed_ext)) {
            $message = "Format foto KTP tidak diperbolehkan. Gunakan JPG, PNG, atau GIF.";
        } else {
            if (!is_dir('../../uploads/foto_ktp')) {
                mkdir('../../uploads/foto_ktp', 0777, true);
            }
            $foto_ktp_newname = 'ktp_' . time() . '_' . rand(1000,9999) . '.' . $foto_ktp_ext;
            $foto_ktp_path = '../../uploads/foto_ktp/' . $foto_ktp_newname;

            if (move_uploaded_file($foto_ktp_tmp, $foto_ktp_path)) {
                // Simpan data ke DB (tamu)
                $stmt = $conn->prepare("INSERT INTO tamu (nama_tamu, no_identitas, tujuan, jenis_kelamin, no_hp, alamat, foto_ktp, waktu_checkin, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if (!$stmt) {
                    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                }

                $stmt->bind_param("sssssssss", $nama, $no_identitas, $tujuan, $jenis_kelamin, $no_hp, $alamat, $foto_ktp_newname, $waktu_checkin, $status);

                if ($stmt->execute()) {
                    // Ambil ID tamu terakhir yang baru masuk (gunakan $conn->insert_id)
                    $last_id = $conn->insert_id;
                    $stmt->close();

                    $query = $conn->prepare("SELECT nama_tamu, tujuan, jenis_kelamin, no_hp, alamat, waktu_checkin FROM tamu WHERE id = ?");
                    if (!$query) {
                        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                    }
                    $query->bind_param("i", $last_id);
                    $query->execute();
                    $result = $query->get_result();
                    $info_tamu = $result->fetch_assoc();
                    $query->close();

                    $message = "Check-in manual berhasil!";
                } else {
                    $message = "Gagal menyimpan data tamu ke database.";
                }
            } else {
                $message = "Gagal mengupload file foto KTP.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Validasi Manual Satpam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
          body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #2e7d32;
            min-height: 100vh;
            padding: 20px;
            color: white;
            width: 220px;
        }
        .sidebar a {
            color: #c8e6c9;
            text-decoration: none;
            display: block;
            padding: 10px 0;
            font-weight: 500;
        }
        .sidebar a:hover {
            background-color: #1b5e20;
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
                background-color: #2e7d32;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 5px;
                font-weight: bold;
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
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass
        </div>
        <div><strong>Satpam:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php">Dashboard</a>
        <a href="scan.php">Scan QR</a>
        <a href="validasi_manual.php">Validasi Manual</a>
        <a href="form_kurir.php">Check-In Kurir</a>
        <a href="daftar_masuk.php">Daftar Masuk</a>
        <a href="daftar_keluar.php">Daftar Keluar</a>
        <a href="tamu_di_lokasi.php">Tamu di Lokasi</a>
        <a href="laporan_harian.php">Laporan Harian</a>
        <a href="riwayat_scan.php">Riwayat Scan</a>
        <a href="catatan_shift.php">Catatan Shift</a>
        <a href="daftar_blacklist.php">Daftar Blacklist</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay untuk sidebar mobile -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Konten utama -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

        <h4 class="mb-4">Validasi Manual Satpam</h4>

        <?php if ($message): ?>
          <div class="alert <?= $info_tamu ? 'alert-success' : 'alert-danger' ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <?php if ($info_tamu): ?>
          <div class="info-tamu mb-4">
            <h5>Check-in Berhasil!</h5>
            <p><strong>Nama:</strong> <?= htmlspecialchars($info_tamu['nama_tamu']) ?></p>
            <p><strong>Tujuan Kunjungan:</strong> <?= htmlspecialchars($info_tamu['tujuan']) ?></p>
            <p><strong>Jenis Kelamin:</strong> <?= htmlspecialchars($info_tamu['jenis_kelamin']) ?></p>
            <p><strong>No. Telepon:</strong> <?= htmlspecialchars($info_tamu['no_hp']) ?></p>
            <p><strong>Alamat:</strong> <?= htmlspecialchars($info_tamu['alamat']) ?></p>
            <p><strong>Waktu Check-in:</strong> <?= htmlspecialchars($info_tamu['waktu_checkin']) ?></p>
          </div>
        <?php endif; ?>

        <div class="form-checkin">
            <form action="" method="POST" enctype="multipart/form-data" novalidate>
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Tamu</label>
                    <input type="text" class="form-control" id="nama" name="nama" required />
                </div>
                <div class="mb-3">
                    <label for="no_ktp" class="form-label">Nomor KTP</label>
                    <input type="text" class="form-control" id="no_ktp" name="no_ktp" required />
                </div>
                <div class="mb-3">
                    <label for="tujuan" class="form-label">Tujuan Kunjungan</label>
                    <input type="text" class="form-control" id="tujuan" name="tujuan" required />
                </div>
                <div class="mb-3">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="" selected disabled>Pilih jenis kelamin</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="no_hp" class="form-label">Nomor Telepon</label>
                    <input type="tel" class="form-control" id="no_hp" name="no_hp" required pattern="[0-9+\-\s]+" />
                </div>
                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="foto_ktp" class="form-label">Foto KTP</label>
                    <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept="image/*" required />
                </div>
                <button type="submit" class="btn btn-primary">Check-In Manual</button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('overlay').classList.toggle('show');
    }
</script>
</body>
</html>