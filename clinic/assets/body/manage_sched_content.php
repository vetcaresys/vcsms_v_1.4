<div class="container py-4">
    <?php if (!isset($errorMsg)): ?>
        <!-- Add Schedule Form -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Add Weekly Schedule</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Select Days</label>
                        <select name="days[]" class="form-select" multiple required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                        <small class="text-muted">Hold CTRL (Windows) or CMD (Mac) to select multiple days.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Opening Time</label>
                        <input type="time" name="open_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Closing Time</label>
                        <input type="time" name="close_time" class="form-control" required>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" name="add_schedule" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Save Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Schedules Table -->
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-week"></i> Current Weekly Schedules</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($scheduleRows)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Day</th>
                                    <th>Opening Time</th>
                                    <th>Closing Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scheduleRows as $row): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= $row['day_of_week'] ?></span></td>
                                        <td><?= date("g:i A", strtotime($row['open_time'])) ?></td>
                                        <td><?= date("g:i A", strtotime($row['close_time'])) ?></td>
                                        <td>
                                            <span class="badge <?= $row['status'] === 'open' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Edit button triggers modal -->
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editModal<?= $row['schedule_id'] ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?= $row['schedule_id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="p-3 mb-0">No schedules set yet.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>