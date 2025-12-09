<div class="container py-4">
    <?php
    if (!empty($errorMsg)) {
        echo $errorMsg;
    }
    if (!empty($msg)) {
        echo $msg;
    }
    ?>

    <?php if (!isset($errorMsg)): ?>
        <div class="row g-4">
            <!-- Add Service Form (Left Column) -->
            <div class="col-lg-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Service</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Service Name</label>
                                <select name="service_name" id="service_name" class="form-select" required
                                    onchange="toggleCustomService()">
                                    <option value="" disabled selected>Select a service</option>
                                    <option value="General Check-up">General Check-up</option>
                                    <option value="Vaccination">Vaccination</option>
                                    <option value="Deworming">Deworming</option>
                                    <option value="Grooming">Grooming</option>
                                    <option value="Dental Cleaning">Dental Cleaning</option>
                                    <option value="Spaying / Neutering">Spaying / Neutering</option>
                                    <option value="Surgery">Surgery</option>
                                    <option value="Emergency Treatment">Emergency Treatment</option>
                                    <option value="Ultrasound">Ultrasound</option>
                                    <option value="X-ray">X-ray</option>
                                    <option value="Laboratory Test">Laboratory Test</option>
                                    <option value="Other">Other (specify)</option>
                                </select>

                                <!-- Hidden custom input field -->
                                <input type="text" name="custom_service" id="custom_service" class="form-control mt-2"
                                    placeholder="Enter custom service name" style="display:none;">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Duration</label>
                                <input type="text" name="duration" class="form-control" placeholder="e.g., 30 minutes"
                                    required>
                            </div>
                            <div class="col-12 d-flex align-items-end">
                                <button type="submit" name="add_service" class="btn btn-success w-100">
                                    <i class="bi bi-check-lg"></i> Add
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Services Table (Right Column) -->
            <div class="col-lg-8">
                <div class="card shadow h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Current Services</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($serviceList)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Service</th>
                                            <th>Duration</th>
                                            <th style="width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($serviceList as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['service_name']); ?></td>
                                                <td><?= htmlspecialchars($row['duration']); ?></td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <!-- Edit button -->
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#editServiceModal<?= $row['service_id']; ?>">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>

                                                        <!-- Delete button -->
                                                        <!-- <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="confirmDelete(<?= $row['service_id']; ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button> -->
                                                    </div>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="p-3 mb-0">No services added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>