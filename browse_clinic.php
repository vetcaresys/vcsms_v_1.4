<?php
require 'config.php';

// Fetch all registered clinics with coordinates **and approved status**
$stmt = $pdo->query("
    SELECT clinic_id, clinic_name, address, latitude, longitude, logo 
    FROM clinics 
    WHERE latitude IS NOT NULL 
      AND longitude IS NOT NULL
      AND status = 'approved'
");
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Helper function: fix logo path
function getLogoPath($logo)
{
    if (empty($logo)) {
        return "assets/default-clinic.jpg"; // fallback image
    }

    // point to correct uploads/logos folder
    return "uploads/logos/" . basename($logo);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Browse Clinics - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootswatch Theme (Lux) -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* 🌟 Global Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fb;
            color: #2e2e2e;
            line-height: 1.6;
        }

        /* 🧭 Navbar */
        .navbar {
            background: linear-gradient(90deg, #0d6efd, #007bff);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 50%;
            background: #fff;
            padding: 3px;
            margin-right: 10px;
            transition: transform 0.2s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.08);
        }

        /* Links */
        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #ffc107 !important;
        }

        /* 🧾 Summary Cards */
        .summary-card {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-card h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .summary-card h2 {
            font-weight: 700;
            font-size: 2rem;
        }

        /* 💼 Tables */
        .table {
            border-radius: 10px;
            overflow: hidden;
            font-size: 0.95rem;
        }

        .table thead {
            background-color: #0d6efd;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f2f7ff;
        }

        /* 🪄 Buttons */
        .btn {
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* 🧩 Modals */
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            background: linear-gradient(90deg, #0d6efd, #007bff);
            color: white;
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        /* 🧍 Form */
        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* ⚡ Sweet alert pop */
        .swal2-popup {
            font-family: 'Inter', sans-serif !important;
            border-radius: 15px !important;
        }

        /* 🌈 Badges */
        .badge {
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 8px;
        }

        /* 🐾 Page Titles */
        h4.text-primary {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #0d6efd !important;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* 📦 Footer vibe */
        .container-footer {
            text-align: center;
            margin-top: 50px;
            font-size: 0.9rem;
            color: #777;
        }

        /* 🧭 Datatables */
        div.dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        div.dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
        }

        /* 🧁 Animations */
        .card,
        .modal-content {
            transition: all 0.25s ease-in-out;
        }
    </style>
    <style>
        #map {
            height: 450px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .clinic-card {
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .clinic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .clinic-logo {
            height: 150px;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
        }
    </style>
</head>

<body class="bg-light">

    <!-- 🌟 Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>

            <!-- Toggler (hamburger) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible Content -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
                    <a href="login.php" class="btn btn-outline-light">Login</a>
                    <a href="register.php" class="btn btn-light">Register</a>
                </div>
            </div>
        </div>
    </nav>


    <!-- Main Content -->
    <div class="container py-5">
        <h2 class="text-primary mb-4"><i class="bi bi-search"></i> Browse Veterinary Clinics</h2>
        <button id="getLocationBtn" class="btn btn-outline-primary mb-3">
            <i class="bi bi-geo-alt"></i> Get My Location
        </button>
        <!-- Map -->
        <div id="map"></div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center text-lg-start border-top mt-5">
        <div class="container py-3">
            <p class="mb-1 text-muted">&copy; 2025 VetCareSys. All rights reserved.</p>
        </div>
    </footer>

    <!-- Clinic Details Modal -->
    <div class="modal fade" id="clinicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="clinicName"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5 text-center">
                            <img id="clinicLogo" src="" alt="Clinic Logo" class="img-fluid rounded mb-3"
                                style="max-height:200px;">
                            <p class="text-muted mb-1"><i class="bi bi-geo-alt"></i> <span id="clinicAddress"></span>
                            </p>
                            <p class="text-muted"><i class="bi bi-telephone"></i> <span id="clinicContact"></span></p>
                        </div>
                        <div class="col-md-7">
                            <div id="clinicInfo">
                                <h6 class="text-primary">Schedule</h6>
                                <ul id="clinicSchedule" class="list-unstyled mb-3"></ul>
                                <h6 class="text-primary">Services</h6>
                                <ul id="clinicServices" class="list-unstyled"></ul>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3" id="miniMap" style="height:250px;border-radius:8px;"></div>
                </div>
            </div>
        </div>
    </div>


    <script>
        // 🌍 Initialize Map with Satellite + Street toggle
        const map = L.map('map').setView([8.35, 123.75], 10);

        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        });

        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; Esri, Earthstar Geographics'
        });

        street.addTo(map);

        const baseMaps = {
            "🗺 Street": street,
            "🛰 Satellite": satellite
        };

        L.control.layers(baseMaps).addTo(map);

        // 🐾 Load clinics and markers
        const clinics = <?= json_encode($clinics) ?>;
        const markersMap = {};

        clinics.forEach(c => {
            if (c.latitude && c.longitude) {
                const marker = L.marker([c.latitude, c.longitude])
                    .addTo(map)
                    .bindPopup(`
                    <b>${c.clinic_name}</b><br>
                    ${c.address}<br>
                    <button class='btn btn-sm btn-primary mt-2' onclick='showClinicDetails(${c.clinic_id})'>
                        View Details
                    </button>
                    <button class='btn btn-sm btn-success mt-2' onclick='getDirections(${c.latitude}, ${c.longitude})'>
                        Get Directions
                    </button>
                `);
                markersMap[c.clinic_id] = marker;
            }
        });

        function focusClinic(lat, lng) {
            map.setView([lat, lng], 15);
        }

        // 🧠 Fetch clinic full details from backend and show modal
        function showClinicDetails(clinicId) {
            fetch(`get_clinic_details.php?id=${clinicId}`)
                .then(res => res.json())
                .then(data => {
                    if (data) {
                        // Basic info
                        document.getElementById('clinicName').innerText = data.clinic_name;
                        document.getElementById('clinicAddress').innerText = data.address;
                        document.getElementById('clinicContact').innerText = data.contact_info || 'No contact info';
                        document.getElementById('clinicLogo').src = data.logo ? `uploads/logos/${data.logo}` : 'assets/default-clinic.jpg';

                        // Schedules
                        const schedList = document.getElementById('clinicSchedule');
                        schedList.innerHTML = '';
                        data.schedules.forEach(s => {
                            schedList.innerHTML += `<li>${s.day_of_week}: ${s.open_time} - ${s.close_time}</li>`;
                        });

                        // Services
                        const servList = document.getElementById('clinicServices');
                        servList.innerHTML = '';
                        data.services.forEach(s => {
                            servList.innerHTML += `<li>${s.service_name} — ${s.duration || ''} (${s.price ? '₱' + s.price : 'Free'})</li>`;
                        });

                        // 🛰 Mini-map with Satellite view by default
                        setTimeout(() => {
                            const mini = L.map('miniMap').setView([data.latitude, data.longitude], 16);

                            const miniStreet = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap contributors'
                            });

                            const miniSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                attribution: '&copy; Esri, Earthstar Geographics'
                            });

                            miniSatellite.addTo(mini);

                            const miniBase = {
                                "🗺 Street": miniStreet,
                                "🛰 Satellite": miniSatellite
                            };
                            L.control.layers(miniBase).addTo(mini);

                            L.marker([data.latitude, data.longitude]).addTo(mini);
                        }, 300);

                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('clinicModal'));
                        modal.show();
                    }
                })
                .catch(err => console.error('Error fetching details:', err));
        }

        let userMarker = null;
        let userLocation = null;

        document.getElementById("getLocationBtn").addEventListener("click", () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        userLocation = [lat, lng];

                        // Add or update marker
                        if (userMarker) {
                            userMarker.setLatLng(userLocation);
                        } else {
                            userMarker = L.marker(userLocation, {
                                icon: L.icon({
                                    iconUrl: "https://cdn-icons-png.flaticon.com/512/64/64113.png",
                                    iconSize: [30, 30]
                                })
                            }).addTo(map)
                                .bindPopup("📍 You are here").openPopup();
                        }

                        map.setView(userLocation, 13);
                    },
                    (err) => {
                        alert("Location access denied or unavailable.");
                        console.error(err);
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });

    </script>

    <script>
        function getDirections(destLat, destLng) {
            if (!userLocation) {
                alert("Please click 'Get My Location' first!");
                return;
            }

            const [userLat, userLng] = userLocation;
            const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${destLat},${destLng}`;
            window.open(googleMapsUrl, "_blank");
        }
    </script>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>