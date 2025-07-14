<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';

$message = '';
if (isset($_POST['submit'])) {
    // Proses upload foto jika ada
    $fotoNamaFile = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/laporan_teknisi/'; // folder upload, sesuaikan jika perlu
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = basename($_FILES['foto']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        // Buat nama file unik
        $fotoNamaFile = uniqid('laporan_') . '.' . $fileExt;
        $destPath = $uploadDir . $fotoNamaFile;

        // Validasi tipe file (misal hanya jpg/png)
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowedExts)) {
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $message = "<div class='alert alert-danger'>Gagal mengupload foto.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Format foto tidak didukung. Gunakan JPG, PNG, atau GIF.</div>";
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO laporan_teknisi (id_user, tanggal, isi_laporan, foto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $id_user, $tanggal, $isi_laporan, $fotoNamaFile);

        $id_user = $_SESSION['userid']; // dari session login
        $tanggal = date('Y-m-d');
        $isi_laporan = $_POST['uraian'];

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Laporan berhasil disimpan.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menyimpan laporan: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Laporan Teknisi - Engineering</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* tetap sama seperti sebelumnya */
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
        /* Untuk tampilan gambar di tabel */
        .foto-laporan {
            max-width: 100px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 5px;
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
        <a href="laporan_teknisi.php" class="active">Laporan Teknisi</a>
        <a href="setting.php">Pengaturan</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

        <h4>Laporan Pekerjaan Teknisi</h4>

        <?= $message ?>

        <!-- Tambahkan enctype untuk upload -->
        <form method="POST" enctype="multipart/form-data" class="mb-5">
            <div class="mb-3">
                <label for="uraian" class="form-label">Uraian Pekerjaan</label>
                <textarea name="uraian" id="uraian" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Upload Foto (opsional)</label>
                <input type="file" name="foto" id="foto" class="form-control" accept="image/*" />
            </div>
            <button name="submit" class="btn btn-success">Simpan Laporan</button>
        </form>

        <h5>Riwayat Laporan</h5>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nama Teknisi</th>
                    <th>Uraian</th>
                    <th>Tanggal</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT laporan_teknisi.*, engineering_users.nama_lengkap 
                                     FROM laporan_teknisi 
                                     JOIN engineering_users ON laporan_teknisi.id_user = engineering_users.id 
                                     ORDER BY laporan_teknisi.created_at DESC");

                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['isi_laporan']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['tanggal']) . "</td>";

                        // Tampilkan foto jika ada
                        if (!empty($row['foto']) && file_exists('../../uploads/laporan_teknisi/' . $row['foto'])) {
                            $fotoUrl = '/uploads/laporan_teknisi/' . $row['foto'];
                            echo "<td><img src='" . htmlspecialchars($fotoUrl) . "' alt='Foto Laporan' class='foto-laporan'></td>";
                        } else {
                            echo "<td><em>Tidak ada foto</em></td>";
                        }

                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Gagal mengambil data: " . htmlspecialchars($conn->error) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
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