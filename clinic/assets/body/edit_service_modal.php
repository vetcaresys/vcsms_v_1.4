<?php
$defaultServices = [
    "General Check-up",
    "Vaccination",
    "Deworming",
    "Grooming",
    "Dental Cleaning",
    "Spaying / Neutering",
    "Surgery",
    "Emergency Treatment",
    "Ultrasound",
    "X-ray",
    "Laboratory Test"
];
?>

<?php foreach ($serviceList as $row): ?>
<?php
    $isCustom = !in_array($row['service_name'], $defaultServices);
?>
<div class="modal fade" id="editServiceModal<?= $row['service_id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="service_id" value="<?= $row['service_id']; ?>">

                    <!-- Service Name -->
                    <div class="mb-3">
                        <label class="form-label">Service Name</label>
                        <select name="service_name" class="form-select"
                            onchange="toggleEditCustomService(<?= $row['service_id']; ?>)" required>

                            <option value="" disabled>Select a service</option>

                            <?php foreach ($defaultServices as $service): ?>
                                <option value="<?= $service ?>"
                                    <?= $row['service_name'] === $service ? 'selected' : '' ?>>
                                    <?= $service ?>
                                </option>
                            <?php endforeach; ?>

                            <option value="Other" <?= $isCustom ? 'selected' : '' ?>>
                                Other (specify)
                            </option>
                        </select>

                        <!-- Custom service input -->
                        <input type="text"
                            name="custom_service"
                            id="custom_service_<?= $row['service_id']; ?>"
                            class="form-control mt-2"
                            placeholder="Enter custom service name"
                            value="<?= $isCustom ? htmlspecialchars($row['service_name']) : '' ?>"
                            style="<?= $isCustom ? '' : 'display:none;' ?>">
                    </div>

                    <!-- Duration -->
                    <div class="mb-3">
                        <label class="form-label">Duration</label>
                        <input type="text" name="duration" class="form-control"
                            value="<?= htmlspecialchars($row['duration']); ?>" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_service" class="btn btn-warning">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>


<script>
function toggleEditCustomService(serviceId) {
    const select = document.querySelector(
        `#editServiceModal${serviceId} select[name="service_name"]`
    );
    const input = document.getElementById(`custom_service_${serviceId}`);

    if (select.value === "Other") {
        input.style.display = "block";
        input.required = true;
    } else {
        input.style.display = "none";
        input.required = false;
        input.value = "";
    }
}
</script>
