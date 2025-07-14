<?php
require_once '../../includes/auth.php';
checkRole(['engineering']);
require_once '../../config/db.php';
include '../../includes/get_notifications.php';

$filterTanggal = $_GET['filter_tanggal'] ?? '';
$whereFilter = '';
if (!empty($filterTanggal)) {
    $whereFilter = "WHERE DATE(jam) = '" . mysqli_real_escape_string($conn, $filterTanggal) . "'";
}

$jadwalQuery = "SELECT * FROM jadwal_hari_ini $whereFilter ORDER BY jam ASC";
$jadwal = mysqli_query($conn, $jadwalQuery) or die("Gagal ambil jadwal: " . mysqli_error($conn));

// Query gabungan laporan_masalah dan laporan_teknisi dengan kolom sumber
$whereLaporanFilter = '';
if (!empty($filterTanggal)) {
    $safeDate = mysqli_real_escape_string($conn, $filterTanggal);
    $whereLaporanFilter = "WHERE DATE(tanggal) = '$safeDate'";
}

$laporanQuery = "
    SELECT tanggal, waktu, laporan, tanggal_tindak_lanjut, 'masalah' AS sumber 
    FROM laporan_masalah
    $whereLaporanFilter

    UNION ALL

    SELECT tanggal, waktu, isi_laporan AS laporan, tindak_lanjut AS tanggal_tindak_lanjut, 'teknisi' AS sumber 
    FROM laporan_teknisi
    $whereLaporanFilter

    ORDER BY tanggal DESC, waktu DESC
";

$laporan = mysqli_query($conn, $laporanQuery);
if (!$laporan) {
    die('Gagal ambil laporan gabungan: ' . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Engineering</title>
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
                height: 100vh;
                overflow-y: auto;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggler {
                display: block;
                margin: 10px 0;
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
    <nav class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/aem-visitor/assets/images/logo aem.jpeg" alt="Logo" />
            Visitor Pass
        </div>
        <div><strong>Engineering:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Guest') ?></div>
        <hr class="border-light" />
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="daftar_kunjungan.php">Daftar Kunjungan</a>
        <a href="form_izin_tamu.php">Buat Izin Tamu</a>
        <a href="jadwal_kunjungan.php">Jadwal Kunjungan</a>
        <a href="laporan_teknisi.php">Laporan Teknisi</a>
        <a href="setting.php">Pengaturan</a>
        <a class="text-danger" href="/pass-aem/logout.php">Logout</a>
    </nav>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <main class="flex-grow-1 p-4">
        <button class="sidebar-toggler mb-3" onclick="toggleSidebar()">☰ Menu</button>
        <h4 class="mb-3">Dashboard Engineering</h4>

        <!-- Filter Tanggal -->
        <form method="get" class="mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="filter_tanggal" class="col-form-label">Filter Tanggal:</label>
                </div>
                <div class="col-auto">
                    <input type="date" id="filter_tanggal" name="filter_tanggal" class="form-control" value="<?= htmlspecialchars($filterTanggal) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                    <a href="dashboard.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <!-- Jadwal Hari Ini -->
        <section class="mb-5">
            <h5>Jadwal Hari Ini</h5>
            <table class="table table-bordered table-sm">
                <thead class="table-success">
                    <tr>
                        <th>Nama</th>
                        <th>Unit</th>
                        <th>Jam</th>
                        <th>Tujuan</th>
                        <th>Status</th>
                        <th>Progres</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($jadwal)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= htmlspecialchars($row['jam']) ?></td>
                            <td><?= htmlspecialchars($row['tujuan']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php
                                    $p = intval($row['progres']);
                                    if ($row['status'] === 'Belum') $p = 0;
                                    elseif ($row['status'] === 'Selesai') $p = 100;
                                ?>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar
                                        <?php
                                            if ($row['status'] === 'Selesai') echo 'bg-success';
                                            elseif ($row['status'] === 'Sedang Dikerjakan') echo 'bg-info progress-bar-striped progress-bar-animated';
                                            else echo 'bg-secondary';
                                        ?>"
                                        role="progressbar"
                                        style="width: <?= $p ?>%;"
                                        aria-valuenow="<?= $p ?>"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <?= $p ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Pilih
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="edit_jadwal.php?id=<?= $row['id'] ?>">Edit</a></li>
                                        <li><a class="dropdown-item" href="edit_progres.php?id=<?= $row['id'] ?>">Update Progres</a></li>
                                        <li><a class="dropdown-item text-danger" href="hapus_jadwal.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin hapus?')">Hapus</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

<!-- Laporan Masalah -->
<section class="mb-5">
    <h5>Laporan Masalah</h5>
    <table class="table table-bordered table-sm">
        <thead class="table-danger">
            <tr>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Deskripsi Laporan</th>
                <th>Tanggal Tindak Lanjut</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($laporan)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['waktu']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['laporan'])) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_tindak_lanjut'] ?? '-') ?></td>
                    <td>
                        <a href="tindak_lanjut.php?tanggal=<?= urlencode($row['tanggal']) ?>&waktu=<?= urlencode($row['waktu']) ?>" 
                           class="btn btn-sm <?= empty($row['tanggal_tindak_lanjut']) ? 'btn-primary' : 'btn-warning' ?>">
                            <?= empty($row['tanggal_tindak_lanjut']) ? 'Tindak Lanjut' : 'Edit' ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>