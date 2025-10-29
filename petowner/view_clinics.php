<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

// Fetch all clinics with valid coordinates
$stmt = $pdo->query("SELECT clinic_name, address, latitude, longitude FROM clinics WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Clinics on Map</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #2b7a78; }
        #map { height: 600px; width: 100%; border: 2px solid #ccc; margin-top: 20px; }
    </style>
</head>
<body>

<h2>📍 Veterinary Clinics Near You</h2>
<p>Click the markers to view clinic info.</p>

<div id="map"></div>

<script>
    const map = L.map('map').setView([8.15, 123.85], 11); // Center on Misamis Occidental

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const clinics = <?php echo json_encode($clinics); ?>;

    clinics.forEach(clinic => {
        if (clinic.latitude && clinic.longitude) {
            L.marker([clinic.latitude, clinic.longitude])
                .addTo(map)
                .bindPopup(`<b>${clinic.clinic_name}</b><br>${clinic.address}`);
        }
    });
</script>

</body>
</html>
