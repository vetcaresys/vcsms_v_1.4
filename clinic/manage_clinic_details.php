<?php
include '../config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$name = htmlspecialchars($_SESSION['name']);

// ✅ One consistent definition for profile picture
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";

// Refresh clinic info
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$existingClinic = $stmt->fetch();

// 🔥 RE-PARSE ADDRESS AFTER SAVE
$existingBarangay = '';
$existingMunicipality = '';

if (!empty($existingClinic['address'])) {
    $parts = array_map('trim', explode(',', $existingClinic['address']));
    if (count($parts) >= 2) {
        $existingBarangay = $parts[0];
        $existingMunicipality = $parts[1];
    }
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = $_POST['clinic_name'];
    $address = $_POST['address'];
    if (stripos($address, 'Misamis Occidental') === false) {
        $msg = "Address must include 'Misamis Occidental'.";
    }
    $contact_info = $_POST['contact_info'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $logo_path = $existingClinic['logo'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $upload_dir = "../uploads/logos/";
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES["logo"]["name"]);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                $logo_path = $file_name;
            } else {
                $msg = "Failed to upload logo.";
            }
        } else {
            $msg = "Invalid image type.";
        }
    }

    if (empty($msg)) {
        if ($existingClinic) {
            $sql = "UPDATE clinics SET clinic_name=?, address=?, contact_info=?, latitude=?, longitude=?";
            $params = [$clinic_name, $address, $contact_info, $latitude, $longitude];
            if ($logo_path) {
                $sql .= ", logo=?";
                $params[] = $logo_path;
            }
            $sql .= " WHERE user_id=?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Clinic updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clinics (user_id, clinic_name, address, contact_info, latitude, longitude, logo) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$user_id, $clinic_name, $address, $contact_info, $latitude, $longitude, $logo_path]);
            $msg = "Clinic registered successfully!";
        }

        // Refresh clinic info
        $stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $existingClinic = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Clinic - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_clinic_details.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>

<body class="bg-light">

    <?php if (!empty($msg)): ?>
        <script>
            Swal.fire({
                icon: <?= (stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false) ? "'error'" : "'success'" ?>,
                title: <?= (stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false) ? "'Error!'" : "'Success!'" ?>,
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
    <?php endif; ?>



    <?php include 'assets/body/navbar.php' ?>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building-fill"></i>
                    <?= $existingClinic ? "Update Your Clinic" : "Register Your Clinic"; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">

                    <!-- CLINIC NAME -->
                    <div class="mb-3">
                        <label class="form-label">Clinic Name</label>
                        <input type="text" class="form-control" name="clinic_name"
                            value="<?= $existingClinic['clinic_name'] ?? '' ?>" required>
                        <div class="form-text text-muted">
                            Enter your official clinic name. Example: <b>VetCare Animal Clinic</b>
                        </div>
                    </div>

                    <!-- ADDRESS
                <div class="mb-3">
                    <label class="form-label">Clinic Address</label>
                    <input type="text" id="clinic_address" class="form-control" name="address"
                        placeholder="Ex: Ozamiz City, Misamis Occidental"
                        value="<?= $existingClinic['address'] ?? '' ?>" required>
                    <div class="form-text text-muted">
                        Enter your complete clinic address. Must include <b>Misamis Occidental</b>.
                    </div>
                </div> -->

                    <!-- PROVINCE -->
                    <div class="mb-3">
                        <label class="form-label">Province</label>
                        <select id="province" class="form-select" disabled>
                            <option selected>Misamis Occidental</option>
                        </select>
                    </div>

                    <!-- MUNICIPALITY / CITY -->
                    <div class="mb-3">
                        <label class="form-label">Municipality / City</label>
                        <select id="municipality" class="form-select" required>
                            <option value="">Select Municipality / City</option>

                            <!-- Cities -->
                            <option value="Oroquieta City">Oroquieta City</option>
                            <option value="Ozamiz City">Ozamiz City</option>
                            <option value="Tangub City">Tangub City</option>

                            <!-- Municipalities -->
                            <option value="Aloran">Aloran</option>
                            <option value="Baliangao">Baliangao</option>
                            <option value="Bonifacio">Bonifacio</option>
                            <option value="Calamba">Calamba</option>
                            <option value="Clarin">Clarin</option>
                            <option value="Concepcion">Concepcion</option>
                            <option value="Don Victoriano Chiongbian">Don Victoriano Chiongbian</option>
                            <option value="Jimenez">Jimenez</option>
                            <option value="Lopez Jaena">Lopez Jaena</option>
                            <option value="Panaon">Panaon</option>
                            <option value="Plaridel">Plaridel</option>
                            <option value="Sapang Dalaga">Sapang Dalaga</option>
                            <option value="Sinacaban">Sinacaban</option>
                            <option value="Tudela">Tudela</option>
                        </select>
                    </div>

                    <!-- BARANGAY -->
                    <div class="mb-3">
                        <label class="form-label">Barangay</label>
                        <select id="barangay" class="form-select" required>
                            <option value="">Select Barangay</option>
                        </select>
                    </div>

                    <!-- FINAL ADDRESS (HIDDEN / READONLY) -->
                    <input type="hidden" name="address" id="clinic_address"
                        value="<?= $existingClinic['address'] ?? '' ?>">

                    <!-- CONTACT -->
                    <div class="mb-3">
                        <label class="form-label">Contact Info</label>
                        <input type="text" class="form-control" name="contact_info"
                            value="<?= htmlspecialchars($existingClinic['contact_info'] ?? '') ?>" required
                            maxlength="11" pattern="^0\d{10}$"
                            title="Enter a valid 11-digit Philippine mobile number (e.g. 09123456789)" oninput="
                            this.value = this.value.replace(/[^0-9]/g, '');
                            if (this.value.length > 11) this.value = this.value.slice(0, 11);
                        ">
                        <div class="form-text text-muted">
                            Format: <b>09XXXXXXXXX</b> — 11 digits only.
                        </div>
                    </div>

                    <!-- MAP AREA -->
                    <div class="mb-3" id="map"></div>
                    <div class="form-text text-muted mb-3">
                        Drag the marker to set your exact clinic location.
                    </div>


                    <!-- COORDINATES -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude"
                                value="<?= $existingClinic['latitude'] ?? '' ?>" readonly required>
                            <div class="form-text text-muted">
                                Auto-filled based on your selected map location.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude"
                                value="<?= $existingClinic['longitude'] ?? '' ?>" readonly required>
                            <div class="form-text text-muted">
                                Auto-filled based on your selected map location.
                            </div>
                        </div>
                    </div>

                    <!-- LOGO UPLOAD -->
                    <div class="mb-3">
                        <label class="form-label">Upload Logo</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <div class="form-text text-muted">
                            Upload your clinic logo (PNG, JPG recommended).
                        </div>

                        <?php if (!empty($existingClinic['logo']) && file_exists('../uploads/logos/' . $existingClinic['logo'])): ?>
                            <div class="mt-2">
                                <label class="form-label">Current Logo:</label><br>
                                <img src="../uploads/logos/<?= htmlspecialchars($existingClinic['logo']) ?>"
                                    alt="Clinic Logo"
                                    style="max-width: 150px; max-height: 150px; border: 1px solid #ccc; padding: 5px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success">
                            <?= $existingClinic ? "Update Clinic" : "Register Clinic"; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/footer_all.php' ?>

    <script>
        const barangaysByMunicipality = {

            /* ================= CITIES ================= */

            "Ozamiz City": [
                "Aguada", "Bagakay", "Balintawak", "Banadero", "Baybay San Roque",
                "Carmen", "Catadman-Manabay", "Clarin Settlement", "Cogon",
                "Dalapang", "Dimaluna", "Doña Consuelo", "Gango",
                "Kinuman Norte", "Kinuman Sur", "Labo", "Lam-an",
                "Litragan", "Lower Lamac", "Mobod", "San Roque",
                "Tinago", "Upper Lamac"
            ],

            "Oroquieta City": [
                "Apil", "Bunga", "Canubay", "Clarin", "Dulapo",
                "Layawan", "Mobod", "Paypayan", "Talairon",
                "Tipan", "Upper Lamac"
            ],

            "Tangub City": [
                "Aquino", "Balatacan", "Guinobatan", "Hoyohoy",
                "Kimat", "Labuyo", "Mantic", "Migcanaway",
                "Silanga", "Sumirap", "Tugas"
            ],

            /* ================= MUNICIPALITIES ================= */

            "Aloran": [
                "Banisilon", "Calube", "Caputol", "Casusan",
                "Ciriaco Pastrano", "Dullan Norte", "Dullan Sur",
                "Ibabao", "Macubon", "Makawa", "Manamong", "Mimbalot",
                "Mohon", "Palayan", "Panalsalan", "San Roque",
                "Santa Ana", "Taguima", "Talic", "Tubod"
            ],

            "Baliangao": [
                "Del Pilar", "Landing", "Lumipac", "Misom",
                "Mitan-ao", "Northern Poblacion", "Southern Poblacion",
                "Punta Miray", "Sinian", "Tugas"
            ],

            "Bonifacio": [
                "Bag-ong Anonang", "Bag-ong Dalaguete", "Bag-ong Maslog",
                "Bag-ong Silang", "Bag-ong Tubig", "Bagumbang",
                "Baybay", "Bongbong", "Calao", "Dimalco",
                "Lower Usugan", "Map-an", "Montol", "Poblacion",
                "Remedios", "San Jose", "Upper Usugan"
            ],

            "Calamba": [
                "Bonifacio", "Calubian", "Clarin", "Dapacan Alto",
                "Dapacan Bajo", "Langub", "Lanos", "Libertad",
                "Magcamiguing", "Malindang", "Mamalad", "Mansabay Bajo",
                "Mansabay Alto", "North Poblacion", "South Poblacion",
                "Silo-o", "Singalat", "Solinog", "Sulipat", "Waterfall"
            ],

            "Clarin": [
                "Canibungan Daku", "Canibungan Putol", "Dela Paz",
                "Dolipos Alto", "Dolipos Bajo", "Lapasan",
                "Masabud", "Pan-ay", "Penacio", "Poblacion 1",
                "Poblacion 2", "Sebucal", "Tobunan"
            ],

            "Concepcion": [
                "Bagong Nayon", "Calaran", "Guimad",
                "Maligaya", "New Casul", "Poblacion",
                "Soso-on", "Upper Dapitan"
            ],

            "Don Victoriano Chiongbian": [
                "Lake Duminagat", "Lalud", "Liboron",
                "Maramara", "Napangan", "New Cebu",
                "Petianan", "Sitio Canibongan"
            ],

            "Jimenez": [
                "Butuay", "Corrales", "Dicoloc",
                "Maligaya", "Naga", "Santa Cruz", "Sebasi"
            ],

            "Lopez Jaena": [
                "Biasong", "Bonbon", "Calube", "Eastern Poblacion",
                "Katipa", "Labrador", "Lower Rizal", "Mabas",
                "Mahayahay", "Mangidkid", "Molatuhan Bajo",
                "Molatuhan Alto", "Peniel", "Rizal", "Santa Cruz",
                "Sibula", "Sibugon", "Southern Poblacion"
            ],

            "Panaon": [
                "Bangko", "Camanucan", "Labo",
                "Map-an", "Poblacion", "Salimpuno",
                "San Andres", "San Juan", "San Roque"
            ],

            "Plaridel": [
                "Agunod", "Banocboc", "Cebulin", "Clarin",
                "Daraga", "Ilisan", "Katipunan",
                "Kauswagan", "Lao Proper", "Looc",
                "Mangidkid", "New Look", "Northern Poblacion",
                "Panalsalan", "Santa Cruz", "Southern Poblacion",
                "Tipolo", "Union"
            ],

            "Sapang Dalaga": [
                "Agapito Yap Sr.", "Bautista", "Bitibut",
                "Boundary", "Casul", "Dalumpinas",
                "Dasa", "Disoy", "El Paraiso",
                "Guinabot", "Libertad", "Manla",
                "Medallo", "Poblacion", "Salimpuno",
                "San Agustin", "San Isidro", "San Vicente",
                "Sinaad", "Sipac", "Upper Bautista"
            ],

            "Sinacaban": [
                "Colupan Alto", "Colupan Bajo", "Estrella",
                "Katipunan", "San Vicente", "Tipan"
            ],

            "Tudela": [
                "Balon", "Barra", "Bongabong",
                "Calambutan Bajo", "Calambutan Alto",
                "Camating", "Canibungan Proper",
                "Centro Hulpa", "Centro Napu",
                "Centro Upper", "Duha", "Gala",
                "Locso-on", "Namut", "Napo",
                "Pan-ay", "Poblacion", "San Nicolas",
                "Silongon", "Taguima"
            ]
        };
    </script>

    <script>
        const existingMunicipality = <?= json_encode($existingMunicipality) ?>;
        const existingBarangay = <?= json_encode($existingBarangay) ?>;
    </script>

    <script>
        const municipalitySelect = document.getElementById("municipality");
        const barangaySelect = document.getElementById("barangay");
        const addressInput = document.getElementById("clinic_address");

        // Function to populate barangays
        function updateBarangays(selectedMunicipality, preselectBarangay = '') {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            if (barangaysByMunicipality[selectedMunicipality]) {
                barangaysByMunicipality[selectedMunicipality].forEach(brgy => {
                    const option = document.createElement("option");
                    option.value = brgy;
                    option.textContent = brgy;
                    if (brgy === preselectBarangay) option.selected = true;
                    barangaySelect.appendChild(option);
                });
            }
        }

        // Prefill existing clinic info on page load
        window.addEventListener('DOMContentLoaded', () => {

            if (existingMunicipality) {
                municipalitySelect.value = existingMunicipality;
                updateBarangays(existingMunicipality, existingBarangay);

                if (existingBarangay) {
                    barangaySelect.value = existingBarangay;
                    addressInput.value =
                        `${existingBarangay}, ${existingMunicipality}, Misamis Occidental`;
                }
            }
        });

        // Update barangays when municipality changes
        municipalitySelect.addEventListener("change", function () {
            const selectedMunicipality = this.value;
            updateBarangays(selectedMunicipality);
            addressInput.value = ''; // reset hidden address
        });

        // Update hidden address when barangay changes
        barangaySelect.addEventListener("change", function () {
            if (municipalitySelect.value && this.value) {
                addressInput.value = `${this.value}, ${municipalitySelect.value}, Misamis Occidental`;
            }
        });

        // Validate before form submission
        document.querySelector("form").addEventListener("submit", function (e) {
            if (!addressInput.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Address',
                    text: 'Please select both Municipality/City and Barangay.',
                    confirmButtonColor: '#0d6efd'
                });
                return false;
            }

            if (!addressInput.value.toLowerCase().includes("misamis occidental")) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Address',
                    text: 'Please include "Misamis Occidental" in the clinic address.',
                    confirmButtonColor: '#0d6efd'
                });
                return false;
            }
        });
    </script>

    <script>
        const defaultLat = <?php echo $existingClinic['latitude'] ?? '8.15'; ?>;
        const defaultLng = <?php echo $existingClinic['longitude'] ?? '123.84'; ?>;

        // Normal Map (OpenStreetMap)
        const normalMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        });

        // Satellite Map (ESRI)
        const satelliteMap = L.tileLayer(
            "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
            {
                attribution: "Tiles © Esri"
            }
        );

        // Init map
        const map = L.map('map', {
            center: [defaultLat, defaultLng],
            zoom: 13,
            layers: [normalMap]  // default view
        });

        // Layer toggle control
        const baseLayers = {
            "Street View": normalMap,
            "Satellite View": satelliteMap
        };

        L.control.layers(baseLayers).addTo(map);

        // Draggable Marker
        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            document.getElementById('latitude').value = pos.lat;
            document.getElementById('longitude').value = pos.lng;
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>