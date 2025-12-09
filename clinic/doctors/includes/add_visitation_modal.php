<!-- Add Visitation Modal -->
<div class="modal fade" id="addVisitModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="save_visit.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Visitation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Day of Week</label>
                    <select name="day_of_week" class="form-select" required>
                        <option value="">--Select Day--</option>
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day)
                            echo "<option value='$day'>$day</option>";
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Start Time</label>
                    <input type="text" name="start_time" id="editStartTime" class="form-control timepicker" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">End Time</label>
                    <input type="text" name="end_time" id="editEndTime" class="form-control timepicker" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    $('.timepicker').timepicker({
        showMeridian: true, // 12-hour
        minuteStep: 5,
        defaultTime: false
    });
</script>