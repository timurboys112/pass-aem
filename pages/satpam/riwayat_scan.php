<?php
require_once '../../includes/auth.php';
checkRole(['satpam']); // hanya role satpam yang boleh akses
require_once '../../config/db.php';

// --- Tangani aksi hapus (soft delete) dan undo ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hapus_id'])) {
        $hapus_id = (int)$_POST['hapus_id'];

        // Tandai deleted_at dengan waktu sekarang (soft delete)
        $stmt = $conn->prepare("UPDATE kunjungan SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $hapus_id);
        if ($stmt->execute()) {
            // Simpan id yang dihapus ke session untuk undo
            $_SESSION['undo_delete'] = [
                'id' => $hapus_id,
                'waktu' => time()
            ];
            // Redirect untuk mencegah refresh form
            header("Location: " . $_SERVER['PHP_SELF'] . "?tanggal_awal=" . urlencode($_GET['tanggal_awal'] ?? '') . "&tanggal_akhir=" . urlencode($_GET['tanggal_akhir'] ?? '') . "&mode=" . urlencode($_GET['mode'] ?? 'semua'));
            exit;
        } else {
            $error = "Gagal menghapus data: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['undo_id'])) {
        $undo_id = (int)$_POST['undo_id'];
        // Batalkan soft delete
        $stmt = $conn->prepare("UPDATE kunjungan SET deleted_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $undo_id);
        if ($stmt->execute()) {
            unset($_SESSION['undo_delete']);
            header("Location: " . $_SERVER['PHP_SELF'] . "?tanggal_awal=" . urlencode($_GET['tanggal_awal'] ?? '') . "&tanggal_akhir=" . urlencode($_GET['tanggal_akhir'] ?? '') . "&mode=" . urlencode($_GET['mode'] ?? 'semua'));
            exit;
        } else {
            $error = "Gagal membatalkan hapus: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Inisialisasi filter dari GET
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$mode = $_GET['mode'] ?? 'semua';

// Validasi tanggal agar formatnya benar, jika tidak, set kosong
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
if (!validateDate($tanggal_awal)) $tanggal_awal = '';
if (!validateDate($tanggal_akhir)) $tanggal_akhir = '';

// Fungsi untuk tambah kondisi filter tanggal ke query
function addDateFilter($whereParts, $tanggal_awal, $tanggal_akhir, $column = 'waktu_masuk') {
    if ($tanggal_awal && $tanggal_akhir) {
        $whereParts[] = "$column BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'";
    } else if ($tanggal_awal) {
        $whereParts[] = "$column >= '$tanggal_awal 00:00:00'";
    } else if ($tanggal_akhir) {
        $whereParts[] = "$column <= '$tanggal_akhir 23:59:59'";
    }
    return $whereParts;
}

if ($mode == 'semua') {
    // Query untuk semua riwayat kunjungan yang belum dihapus (deleted_at IS NULL)
    $whereParts = ["deleted_at IS NULL"];
    $whereParts = addDateFilter($whereParts, $tanggal_awal, $tanggal_akhir, 'k.waktu_masuk');
    $whereSQL = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

    $sqlKunjungan = "
        SELECT 
            k.id AS id_kunjungan,
            t.nama_tamu,
            COALESCE(i.tujuan, ie.tujuan) AS tujuan,
            k.waktu_masuk,
            k.waktu_keluar,
            k.id_izin
        FROM kunjungan k
        LEFT JOIN izin_kunjungan_penghuni i ON k.id_izin = i.id
        LEFT JOIN izin_kunjungan_engineering ie ON k.id_izin = ie.id
        LEFT JOIN tamu t ON (i.id_tamu = t.id OR ie.id_tamu = t.id)
        $whereSQL
        ORDER BY k.waktu_masuk DESC
    ";
    $resultKunjungan = $conn->query($sqlKunjungan);
    if (!$resultKunjungan) {
        die("Query error riwayat kunjungan: " . $conn->error);
    }
    $data_kunjungan = [];
    while ($row = $resultKunjungan->fetch_assoc()) {
        $data_kunjungan[] = $row;
    }

} else {
    // Rekap mingguan atau bulanan
    $groupBy = $mode == 'mingguan' ? "YEARWEEK(k.waktu_masuk, 1)" : "DATE_FORMAT(k.waktu_masuk, '%Y-%m')";
    $labelFormat = $mode == 'mingguan' ? "%x-W%v" : "%Y-%m";

    $whereParts = ["deleted_at IS NULL"];
    $whereParts = addDateFilter($whereParts, $tanggal_awal, $tanggal_akhir, 'k.waktu_masuk');
    $whereSQL = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

    $sqlRekap = "
        SELECT 
            DATE_FORMAT(k.waktu_masuk, '$labelFormat') AS periode,
            COUNT(*) AS total,
            SUM(CASE WHEN k.waktu_keluar IS NULL THEN 1 ELSE 0 END) AS aktif,
            SUM(CASE WHEN k.waktu_keluar IS NOT NULL THEN 1 ELSE 0 END) AS selesai
        FROM kunjungan k
        $whereSQL
        GROUP BY $groupBy
        ORDER BY periode DESC
        LIMIT 12
    ";
    $resultRekap = $conn->query($sqlRekap);
    if (!$resultRekap) {
        die("Query error rekap: " . $conn->error);
    }
    $rekap = [];
    while ($row = $resultRekap->fetch_assoc()) {
        $rekap[$row['periode']] = $row;
    }
}

// --- Bagian gabungan tamu masuk ---
// (Tidak berubah, bisa tetap seperti semula)
$sqlTamu = "SELECT 
    id,
    nama_tamu AS nama,
    status,
    waktu_checkin AS waktu_masuk,
    jenis_kelamin,
    no_hp,
    alamat,
    'Unit A-01' AS unit
FROM tamu";

$sqlKurir = "SELECT 
    id,
    nama_pengantar AS nama,
    'Kurir' AS status,
    waktu_masuk,
    NULL AS jenis_kelamin,
    NULL AS no_hp,
    NULL AS alamat,
    tujuan_unit AS unit
FROM tamu_kilat";

$tamuList = [];
$resultTamu = $conn->query($sqlTamu);
if ($resultTamu && $resultTamu->num_rows > 0) {
    while ($row = $resultTamu->fetch_assoc()) {
        $tamuList[] = $row;
    }
} else if (!$resultTamu) {
    die("Query error data tamu biasa: " . $conn->error);
}

$resultKurir = $conn->query($sqlKurir);
if ($resultKurir && $resultKurir->num_rows > 0) {
    while ($row = $resultKurir->fetch_assoc()) {
        $tamuList[] = $row;
    }
} else if (!$resultKurir) {
    die("Query error data tamu kurir: " . $conn->error);
}

usort($tamuList, function($a, $b) {
    return strtotime($b['waktu_masuk']) <=> strtotime($a['waktu_masuk']);
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Riwayat Scan</title>
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
    <nav class="sidebar" id="sidebar">
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
    </nav>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>

    <main>
        <h2>Rekap Scan Tamu</h2>

        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal) ?>" />
            </div>
            <div class="col-md-3">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>" />
            </div>
            <div class="col-md-3">
                <label for="mode" class="form-label">Mode</label>
                <select name="mode" id="mode" class="form-select">
                    <option value="semua" <?= $mode == 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="mingguan" <?= $mode == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                    <option value="bulanan" <?= $mode == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($mode === 'semua') : ?>
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        <th>Nama Tamu</th>
                        <th>Tujuan</th>
                        <th>Waktu Masuk</th>
                        <th>Waktu Keluar</th>
                        <th>ID Izin</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_kunjungan as $i => $kunj) : ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($kunj['nama_tamu'] ?? 'Tidak diketahui') ?></td>
                            <td><?= htmlspecialchars($kunj['tujuan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($kunj['waktu_masuk']) ?></td>
                            <td><?= htmlspecialchars($kunj['waktu_keluar'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($kunj['id_izin'] ?? '-') ?></td>
                            <td>
                                <form method="post" action="" onsubmit="return confirm('Yakin ingin menghapus kunjungan ini?');" style="display:inline;">
                                    <input type="hidden" name="hapus_id" value="<?= (int)$kunj['id_kunjungan'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Periode</th>
                        <th>Total</th>
                        <th>Aktif</th>
                        <th>Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rekap as $periode => $rek) : ?>
                        <tr>
                            <td><?= htmlspecialchars($periode) ?></td>
                            <td><?= htmlspecialchars($rek['total']) ?></td>
                            <td><?= htmlspecialchars($rek['aktif']) ?></td>
                            <td><?= htmlspecialchars($rek['selesai']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3>Data Tamu</h3>
        <table class="table table-striped table-bordered">
            <thead class="table-info">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>Waktu Masuk</th>
                    <th>Jenis Kelamin</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tamuList as $i => $tamu) : ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($tamu['nama']) ?></td>
                        <td><?= htmlspecialchars($tamu['status']) ?></td>
                        <td><?= htmlspecialchars($tamu['waktu_masuk']) ?></td>
                        <td><?= htmlspecialchars($tamu['jenis_kelamin'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($tamu['no_hp'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($tamu['alamat'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($tamu['unit'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        // Tampilkan tombol Undo jika ada hapus baru-baru ini (<10 detik)
        if (isset($_SESSION['undo_delete'])):
            $diff = time() - $_SESSION['undo_delete']['waktu'];
            if ($diff < 10): ?>
                <form method="post" action="" class="btn-undo">
                    <input type="hidden" name="undo_id" value="<?= (int)$_SESSION['undo_delete']['id'] ?>">
                    <button type="submit" class="btn btn-warning btn-sm">Undo Hapus</button>
                </form>
                <script>
                    // Hilangkan tombol undo otomatis setelah 10 detik
                    setTimeout(() => {
                        const undoBtn = document.querySelector('.btn-undo');
                        if (undoBtn) undoBtn.style.display = 'none';
                    }, (10 - <?= $diff ?>) * 1000);
                </script>
        <?php
            else:
                unset($_SESSION['undo_delete']);
            endif;
        endif;
        ?>

    </main>
</body>

</html>