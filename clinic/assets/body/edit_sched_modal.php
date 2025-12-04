<!-- edit modal -->
<?php if (!empty($scheduleRows)): ?>
    <?php foreach ($scheduleRows as $row): ?>
        <div class="modal fade" id="editModal<?= $row['schedule_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title">Edit Schedule (<?= $row['day_of_week'] ?>)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">

                            <div class="mb-3">
                                <label class="form-label">Day</label>
                                <input type="text" class="form-control" name="day_of_week" value="<?= $row['day_of_week'] ?>"
                                    readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Opening Time</label>
                                <input type="time" class="form-control" name="open_time" value="<?= $row['open_time'] ?>"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Closing Time</label>
                                <input type="time" class="form-control" name="close_time" value="<?= $row['close_time'] ?>"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="open" <?= $row['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="closed" <?= $row['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_schedule" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>