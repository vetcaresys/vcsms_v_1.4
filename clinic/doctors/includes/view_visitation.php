<div class="container py-5">
    <h2 class="fw-bold">Clinic Information</h2>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex align-items-center">
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
                        <td><?= date("g:i A", strtotime($v['start_time'])) ?></td>
                        <td><?= date("g:i A", strtotime($v['end_time'])) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm editVisitBtn" data-id="<?= $v['visit_id']; ?>"
                                data-day="<?= htmlspecialchars($v['day_of_week']); ?>"
                                data-start="<?= htmlspecialchars($v['start_time']); ?>"
                                data-end="<?= htmlspecialchars($v['end_time']); ?>">
                                Edit
                            </button>
                            <!-- <button class="btn btn-danger btn-sm deleteVisit" data-id="<?= $v['visit_id']; ?>">
                                Delete
                            </button> -->
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Visitation Modal -->
<div class="modal fade" id="editVisitModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editVisitForm" method="POST" action="edit_visit.php" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Visitation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="visit_id" id="editVisitId">

                <div class="mb-3">
                    <label for="editDay" class="form-label">Day</label>
                    <select name="day_of_week" id="editDay" class="form-select" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="editStartTime" class="form-label">Start Time</label>
                    <input type="time" name="start_time" id="editStartTime" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="editEndTime" class="form-label">End Time</label>
                    <input type="time" name="end_time" id="editEndTime" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll(".deleteVisit").forEach(btn => {
        btn.addEventListener("click", function () {
            let id = this.dataset.id;

            Swal.fire({
                title: "Are you sure?",
                text: "This visitation will be permanently deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = "delete_visit.php?id=" + id;
                }
            });
        });
    });
</script>

<script>
    document.querySelectorAll(".editVisitBtn").forEach(btn => {
        btn.addEventListener("click", function () {
            const id = this.dataset.id;
            const day = this.dataset.day;
            const start = this.dataset.start;
            const end = this.dataset.end;

            document.getElementById("editVisitId").value = id;
            document.getElementById("editDay").value = day;
            document.getElementById("editStartTime").value = start;
            document.getElementById("editEndTime").value = end;

            new bootstrap.Modal(document.getElementById("editVisitModal")).show();
        });
    });

</script>