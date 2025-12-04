<?php foreach ($serviceList as $row): ?>
    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal<?= $row['service_id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Edit Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="service_id" value="<?= $row['service_id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="service_name" class="form-control"
                                value="<?= htmlspecialchars($row['service_name']); ?>" required>
                        </div>

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