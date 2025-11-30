<!-- Pending Appointments -->
<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"></i> Pending Appointments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="pendingTable" class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Pet</th>
                            <th>Clinic</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pendingAppointments) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No pending appointments</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingAppointments as $appt): ?>
                                <tr>
                                    <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['clinic_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                    <td><?= date("M d, Y", strtotime($appt['appointment_date'])) ?></td>
                                    <td><span class="badge bg-warning"><?= ucfirst($appt['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger cancel-btn"
                                            data-id="<?= $appt['appointment_id'] ?>">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>