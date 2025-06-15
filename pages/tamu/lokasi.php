<?php
session_start();
require_once "../../config/db.php";

// Cek login dan role tamu
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tamu') {
    header("Location: ../../login.php");
    exit;
}

// Ambil data tamu
$userid = $_SESSION['userid'];
$stmt = $conn->prepare("SELECT * FROM tamu_users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $nama = htmlspecialchars($user['nama_lengkap']);
} else {
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

// Lokasi apartemen
$latitude_apartemen = -6.200000;
$longitude_apartemen = 106.816666;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Lokasi Tamu - Visitor Pass</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
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

        #map-tamu, #map-apartemen {
            height: 300px;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 8px;
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

    <!-- Konten utama -->
    <div class="content">
        <button class="sidebar-toggler" onclick="toggleSidebar()">☰ Menu</button>
        <h3>Lokasi Tamu</h3>

        <h5>Lokasi Saya</h5>
        <div id="map-tamu">Memuat peta lokasi tamu...</div>
        <p id="lokasi-tamu-status"></p>

        <h5>Lokasi Apartemen</h5>
        <div id="map-apartemen"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");
        if (sidebar && overlay) {
            sidebar.classList.toggle("show");
            overlay.classList.toggle("show");
        }
    }

    function hitungJarak(lat1, lon1, lat2, lon2) {
        const R = 6371000; // meter
        const rad = Math.PI / 180;
        const dLat = (lat2 - lat1) * rad;
        const dLon = (lon2 - lon1) * rad;
        const a = Math.sin(dLat / 2) ** 2 +
                  Math.cos(lat1 * rad) * Math.cos(lat2 * rad) *
                  Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function initMapApartemen() {
        const apartemen = [<?= $latitude_apartemen ?>, <?= $longitude_apartemen ?>];
        const mapApartemen = L.map('map-apartemen').setView(apartemen, 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapApartemen);

        L.marker(apartemen).addTo(mapApartemen)
            .bindPopup('Lokasi Apartemen')
            .openPopup();
    }

    function initMapTamu() {
        const status = document.getElementById('lokasi-tamu-status');
        const mapTamuContainer = document.getElementById('map-tamu');
        if (!status || !mapTamuContainer) return;

        const apartemenLat = <?= $latitude_apartemen ?>;
        const apartemenLng = <?= $longitude_apartemen ?>;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    const lokasiTamu = [lat, lng];
                    const mapTamu = L.map('map-tamu').setView(lokasiTamu, 16);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(mapTamu);

                    L.marker(lokasiTamu).addTo(mapTamu)
                        .bindPopup('Lokasi Saya')
                        .openPopup();

                    const jarak = hitungJarak(lat, lng, apartemenLat, apartemenLng);
                    const meter = Math.round(jarak);

                    status.textContent = `Lokasi Anda: Latitude ${lat.toFixed(6)}, Longitude ${lng.toFixed(6)}`;

                    let jarakInfo = document.getElementById('jarak-info');
                    if (!jarakInfo) {
                        jarakInfo = document.createElement('p');
                        jarakInfo.id = 'jarak-info';
                        status.after(jarakInfo);
                    }

                    jarakInfo.innerHTML = `Jarak ke apartemen: <strong>${meter} meter</strong><br>` +
                        (meter > 300
                            ? `<span class="text-danger">Terlalu jauh dari lokasi apartemen</span>`
                            : `<span class="text-success">Berada di sekitar apartemen</span>`);
                },
                function (error) {
                    status.textContent = "Gagal mendapatkan lokasi: " + error.message;
                    initMapApartemen();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            status.textContent = "Geolocation tidak didukung oleh browser Anda.";
            initMapApartemen();
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        initMapApartemen();
        initMapTamu();
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>