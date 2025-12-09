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

                <!-- ADDRESS -->
                <div class="mb-3">
                    <label class="form-label">Clinic Address</label>
                    <input type="text" id="clinic_address" class="form-control" name="address"
                        placeholder="Ex: Ozamiz City, Misamis Occidental"
                        value="<?= $existingClinic['address'] ?? '' ?>" required>
                    <div class="form-text text-muted">
                        Enter your complete clinic address. Must include <b>Misamis Occidental</b>.
                    </div>
                </div>

                <!-- CONTACT -->
                <div class="mb-3">
                    <label class="form-label">Contact Info</label>
                    <input type="text" class="form-control" name="contact_info"
                        value="<?= htmlspecialchars($existingClinic['contact_info'] ?? '') ?>" required maxlength="11"
                        pattern="^0\d{10}$" title="Enter a valid 11-digit Philippine mobile number (e.g. 09123456789)"
                        oninput="
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
                            <img src="../uploads/logos/<?= htmlspecialchars($existingClinic['logo']) ?>" alt="Clinic Logo"
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

<script>
    document.querySelector("form").addEventListener("submit", function (e) {
        const addressInput = document.getElementById("clinic_address").value.trim().toLowerCase();

        if (!addressInput.includes("misamis occidental")) {
            e.preventDefault(); // stop form submission
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Address',
                text: 'Please include "Misamis Occidental" in the clinic address to confirm it’s within the service area.',
                confirmButtonColor: '#0d6efd'
            });
            return false;
        }
    });
</script>