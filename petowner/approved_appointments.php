<!-- Approved Appointments -->
<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"></i> Approved Appointments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="approvedTable" class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Pet</th>
                            <th>Clinic</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($approvedAppointments) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No approved appointments</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvedAppointments as $appt):
                                $dt = new DateTime($appt['appointment_date']); // full datetime from appointment_date                              
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['clinic_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                    <td><?= date("M d, Y", strtotime($appt['appointment_date'])) ?></td>
                                    <td><span class="badge bg-primary"><?= ucfirst($appt['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>