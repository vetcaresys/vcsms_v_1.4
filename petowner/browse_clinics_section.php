<?php
// browse_clinics_section.php
require '../config.php';

// Fetch approved clinics
$stmt = $pdo->query("
    SELECT clinic_id, clinic_name, address, contact_info, latitude, longitude, logo
    FROM clinics
    WHERE status = 'approved'
");
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- CLINIC BROWSER SECTION -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .clinic-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: 15px;
        overflow: hidden;
    }

    .clinic-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .clinic-logo {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }

    .modal-map {
        width: 100%;
        height: 300px;
        border-radius: 10px;
        margin-top: 10px;
    }

    .section-title {
        font-weight: 600;
        margin-top: 15px;
        color: #0d6efd;
    }
</style>

<div class="container py-4">
    <h2 class="text-left mb-4">Browse Veterinary Clinics</h2>
    <div class="row g-4">
        <?php foreach ($clinics as $clinic): ?>
            <div class="col-md-4">
                <div class="card clinic-card" data-bs-toggle="modal"
                    data-bs-target="#clinicModal<?= $clinic['clinic_id'] ?>">
                    <img src="../<?= htmlspecialchars($clinic['logo']) ?>" class="clinic-logo"
                        alt="<?= htmlspecialchars($clinic['clinic_name']) ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($clinic['clinic_name']) ?></h5>
                    </div>
                </div>
            </div>

            <?php
            // Pull extra info for modal
            $serviceStmt = $pdo->prepare("SELECT service_name, duration, price FROM clinic_services WHERE clinic_id = ?");
            $serviceStmt->execute([$clinic['clinic_id']]);
            $services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

            $schedStmt = $pdo->prepare("SELECT day_of_week, open_time, close_time FROM clinic_schedules WHERE clinic_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
            $schedStmt->execute([$clinic['clinic_id']]);
            $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

            $docStmt = $pdo->prepare("SELECT name, contact_number, email, profile_picture FROM staff WHERE clinic_id = ? AND role = 'doctor'");
            $docStmt->execute([$clinic['clinic_id']]);
            $doctors = $docStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <!-- Modal -->
            <div class="modal fade" id="clinicModal<?= $clinic['clinic_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><?= htmlspecialchars($clinic['clinic_name']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>📍 Address:</strong> <?= htmlspecialchars($clinic['address']) ?></p>
                            <p><strong>📞 Contact:</strong> <?= htmlspecialchars($clinic['contact_info']) ?></p>
                            <div id="map<?= $clinic['clinic_id'] ?>" class="modal-map"></div>

                            <h6 class="section-title mt-3">💉 Services Offered</h6>
                            <?php if ($services): ?>
                                <ul class="list-group mb-3">
                                    <?php foreach ($services as $s): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($s['service_name']) ?>
                                            <span class="text-muted"><?= htmlspecialchars($s['duration'] ?: 'N/A') ?>
                                                <?= $s['price'] ? ' - ₱' . number_format($s['price'], 2) : '' ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No services listed.</p>
                            <?php endif; ?>

                            <h6 class="section-title">🕒 Clinic Hours</h6>
                            <?php if ($schedules): ?>
                                <ul class="list-group mb-3">
                                    <?php foreach ($schedules as $sc): ?>
                                        <li class="list-group-item"><?= $sc['day_of_week'] ?>:
                                            <?= date("h:i A", strtotime($sc['open_time'])) ?> -
                                            <?= date("h:i A", strtotime($sc['close_time'])) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No schedule information available.</p>
                            <?php endif; ?>

                            <h6 class="section-title">👨‍⚕️ Doctors</h6>
                            <?php if ($doctors): ?>
                                <div class="row">
                                    <?php foreach ($doctors as $doc): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body text-center">
                                                    <img src="../uploads/profiles/<?= htmlspecialchars($doc['profile_picture']) ?>"
                                                        alt="Doctor" class="rounded-circle mb-2" width="80" height="80">
                                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($doc['name']) ?></h6>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($doc['email']) ?></small>
                                                    <small
                                                        class="text-muted"><?= htmlspecialchars($doc['contact_number']) ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No doctors registered in this clinic.</p>
                            <?php endif; ?>

                            <button class="btn btn-success"
                                onclick="getDirections(<?= $clinic['latitude'] ?>, <?= $clinic['longitude'] ?>)">
                                Get Directions
                            </button>

                        </div>
                    </div>
                </div>
            </div>
            <br><br>

            <script>
                document.getElementById('clinicModal<?= $clinic['clinic_id'] ?>').addEventListener('shown.bs.modal', function () {
                    const mapContainer = document.getElementById('map<?= $clinic['clinic_id'] ?>');

                    // Prevent reinitializing map on repeated modal opens
                    if (mapContainer._leaflet_id) return;

                    // Initialize map
                    const map = L.map(mapContainer).setView([<?= $clinic['latitude'] ?>, <?= $clinic['longitude'] ?>], 15);

                    // 🗺️ Base layers
                    const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    });

                    const satellite = L.tileLayer(
                        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                        {
                            attribution: '&copy; <a href="https://www.esri.com/">Esri</a>, Earthstar Geographics'
                        }
                    );

                    // Default layer
                    street.addTo(map);

                    // 📍 Marker
                    L.marker([<?= $clinic['latitude'] ?>, <?= $clinic['longitude'] ?>])
                        .addTo(map)
                        .bindPopup("<b><?= htmlspecialchars($clinic['clinic_name']) ?></b><br><?= htmlspecialchars($clinic['address']) ?>")
                        .openPopup();

                    // 🧭 Layer control (switch between map types)
                    L.control.layers({
                        "🗺️ Street View": street,
                        "🛰️ Satellite View": satellite
                    }).addTo(map);
                });
            </script>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let userLocation = null;

function getDirections(destLat, destLng) {
    if (userLocation) {
        const [userLat, userLng] = userLocation;
        const url = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${destLat},${destLng}`;
        window.open(url, "_blank");
        return;
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                userLocation = [pos.coords.latitude, pos.coords.longitude];
                const url = `https://www.google.com/maps/dir/?api=1&origin=${userLocation[0]},${userLocation[1]}&destination=${destLat},${destLng}`;
                window.open(url, "_blank");
            },
            (err) => alert("Unable to get your location.")
        );
    } else {
        alert("Geolocation not supported.");
    }
}
</script>
