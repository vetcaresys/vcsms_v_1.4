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
    if (strpos($logo, 'uploads/logos/') !== false) {
        return $logo; // already a full path
    }
    return "uploads/logos/" . basename($logo);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Browse Clinics - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/browse_clinic.css">
    <style>
        /* Enhanced Styles */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        #map {
            height: 500px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .clinic-list-container {
            max-height: 600px;
            /* Fixed height for scrolling */
            overflow-y: auto;
            border-radius: 10px;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .clinic-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .clinic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
            border-color: #007bff;
        }

        .clinic-card img {
            border-radius: 50%;
            /* Makes it circular */
            width: 120px;
            /* Square dimensions for perfect circle */
            height: 120px;
            object-fit: cover;
            /* Ensures proper cropping */
            margin: auto;
            /* Centers the image */
        }

        .clinic-card .card-body {
            padding: 1rem;
        }

        .clinic-card .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #007bff;
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            transform: scale(1.05);
        }

        h2,
        h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }

        /* Scrollbar styling for clinic list */
        .clinic-list-container::-webkit-scrollbar {
            width: 8px;
        }

        .clinic-list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .clinic-list-container::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 10px;
        }

        .clinic-list-container::-webkit-scrollbar-thumb:hover {
            background: #0056b3;
        }
    </style>
</head>

<body class="bg-light">

    <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">VetCareSys</a>
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
        <div class="main-content">
            <h2 class="text-primary mb-4 text-center">
                <i class="bi bi-geo-alt"></i> Browse Veterinary Clinics
            </h2>

            <!-- Two-Column Layout: Map on Left, Clinic List on Right -->
            <div class="row g-4">
                <!-- Left Column: Map -->
                <div class="col-md-7">
                    <div id="map"></div>
                </div>

                <!-- Right Column: Clinic List -->
                <div class="col-md-5">
                    <h3 class="text-primary mb-4">
                        <i class="bi bi-list-ul"></i> Clinic List
                    </h3>
                    <div class="clinic-list-container">
                        <?php if (empty($clinics)): ?>
                            <p class="text-muted text-center">No clinics available at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($clinics as $clinic): ?>
                                <div class="card clinic-card" onclick="showClinicDetails(<?= $clinic['clinic_id'] ?>)">
                                    <div class="row g-0">
                                        <div class="col-4 d-flex align-items-center justify-content-center">
                                            <img src="<?= getLogoPath($clinic['logo']) ?>" class="img-fluid"
                                                alt="Logo of <?= htmlspecialchars($clinic['clinic_name']) ?>">
                                        </div>
                                        <div class="col-8">
                                            <div class="card-body">
                                                <h6 class="card-title mb-2">
                                                    <i class="bi bi-building"></i>
                                                    <?= htmlspecialchars($clinic['clinic_name']) ?>
                                                </h6>
                                                <p class="card-text text-muted small mb-3">
                                                    <i class="bi bi-geo-alt-fill"></i>
                                                    <?= htmlspecialchars($clinic['address']) ?>
                                                </p>
                                                <button class="btn btn-primary btn-sm"
                                                    onclick="event.stopPropagation(); showClinicDetails(<?= $clinic['clinic_id'] ?>)">
                                                    <i class="bi bi-eye"></i> View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
                    <h5 class="modal-title" id="clinicName">
                        <i class="bi bi-building"></i> Clinic Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5 text-center">
                            <img id="clinicLogo" src="" alt="Clinic Logo" class="img-fluid rounded mb-3"
                                style="max-height:200px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);">
                            <p class="text-muted mb-1"><i class="bi bi-geo-alt"></i> <span id="clinicAddress"></span>
                            </p>
                            <p class="text-muted"><i class="bi bi-telephone"></i> <span id="clinicContact"></span></p>
                        </div>
                        <div class="col-md-7">
                            <div id="clinicInfo">
                                <h6 class="text-primary"><i class="bi bi-clock"></i> Schedule</h6>
                                <ul id="clinicSchedule" class="list-unstyled mb-3"></ul>
                                <h6 class="text-primary"><i class="bi bi-tools"></i> Services</h6>
                                <ul id="clinicServices" class="list-group"></ul>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3" id="miniMap"
                        style="height:250px; border-radius:8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);"></div>
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
                <div class='p-2'>
                    <h6 class='fw-bold mb-1'>${c.clinic_name}</h6>
                    <p class='text-muted small mb-1'>${c.address}</p>
                    <button class='btn btn-sm btn-primary me-1' onclick='showClinicDetails(${c.clinic_id})'>View</button>
                    <button class='btn btn-sm btn-outline-dark' onclick='window.location.href="login.php"'>Book</button>
                    <button class='btn btn-sm btn-success' onclick='getDirections(${c.latitude}, ${c.longitude})'>
                        Get Directions
                    </button>
                </div>
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
                        document.getElementById('clinicLogo').src = data.logo || 'assets/default-clinic.jpg';

                        // Schedules
                        const schedList = document.getElementById('clinicSchedule');
                        schedList.innerHTML = '';
                        data.schedules.forEach(s => {
                            schedList.innerHTML += `<li>${s.day_range}: ${s.open_time} - ${s.close_time}</li>`;
                        });

                        // Services
                        const servList = document.getElementById('clinicServices');
                        servList.innerHTML = '';

                        data.services.forEach(s => {
                            servList.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${s.service_name}</strong><br>
                                    <small class="text-muted">${s.duration || ''}</small>
                                </div>
                                <span class="badge bg-primary px-3 py-2">
                                    ${s.price ? '₱' + s.price : ''}
                                </span>
                            </li>
                        `;
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

        document.getElementById("getLocationBtn")?.addEventListener("click", () => {
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
            // If user location is already set, use it directly
            if (userLocation) {
                const [userLat, userLng] = userLocation;
                const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${destLat},${destLng}`;
                window.open(googleMapsUrl, "_blank");
                return;
            }

            // Otherwise, fetch location automatically
            if (navigator.geolocation) {
                alert("Fetching your location for directions...");
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const userLat = pos.coords.latitude;
                        const userLng = pos.coords.longitude;
                        userLocation = [userLat, userLng]; // Store for future use

                        // Add marker to map (optional, but keeps consistency)
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

                        // Now open directions
                        const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${destLat},${destLng}`;
                        window.open(googleMapsUrl, "_blank");
                    },
                    (err) => {
                        alert("Unable to get your location. Please allow location access or try again.");
                        console.error(err);
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>