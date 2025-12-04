$(document).ready(function () {
    $('#clinicSelect').on('change', function () {
        var clinicId = $(this).val();

        if (clinicId) {
            $.ajax({
                url: 'get_clinic_schedule.php',
                method: 'GET',
                data: { clinic_id: clinicId },
                success: function (response) {
                    $('#clinicScheduleDisplay').html(response);
                },
                error: function () {
                    $('#clinicScheduleDisplay').html('<div class="text-danger">Failed to load schedule. Try again.</div>');
                }
            });
        } else {
            $('#clinicScheduleDisplay').html('Please select a clinic to view available schedules.');
        }
    });
});