document.getElementById("doctorSelect").addEventListener("change", function () {
    const doctorId = this.value;
    const clinicId = document.getElementById("clinicSelect").value;

    if (!doctorId || !clinicId) {
        document.getElementById("doctorScheduleDisplay").innerHTML =
            "Please select a doctor.";
        return;
    }

    // Fetch schedule using AJAX
    fetch("fetch_doctor_schedule.php?doctor_id=" + doctorId + "&clinic_id=" + clinicId)
        .then(response => response.text())
        .then(data => {
            document.getElementById("doctorScheduleDisplay").innerHTML = data;
        });
});