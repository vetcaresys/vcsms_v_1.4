<div class="container py-5">
    <h2 class="fw-bold">Clinic Information</h2>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex align-items-center">
            <img src="../../<?= htmlspecialchars($clinic['logo']) ?>?t=<?= time() ?>" width="80" class="me-3 rounded">

            <div>
                <h4><?= htmlspecialchars($clinic['clinic_name']) ?></h4>
                <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($clinic['address']) ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($clinic['contact_info']) ?></p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>My Visitations</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitModal">+ Add
            Visitation</button>
    </div>

    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>Day</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($visits)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No visitations added yet.</td>
                </tr>
            <?php else:
                foreach ($visits as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['day_of_week']) ?></td>
                        <td><?= htmlspecialchars($v['start_time']) ?></td>
                        <td><?= htmlspecialchars($v['end_time']) ?></td>
                        <td>
                            <form action="delete_visit.php" method="POST" class="d-inline">
                                <input type="hidden" name="visit_id" value="<?= $v['visit_id'] ?>">
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>