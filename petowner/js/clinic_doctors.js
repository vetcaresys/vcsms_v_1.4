// for the get_doctors.php
document.getElementById('clinicSelect').addEventListener('change', function () {
    const clinicId = this.value;
    const doctorSelect = document.getElementById('doctorSelect');

    if (!clinicId) {
        doctorSelect.innerHTML = '<option value="">-- Optional: Select Doctor --</option>';
        doctorSelect.disabled = true;
        return;
    }

    fetch('get_doctors.php?clinic_id=' + clinicId)
        .then(res => res.json())
        .then(data => {
            const doctorSelect = document.getElementById('doctorSelect');
            doctorSelect.innerHTML = '<option value="">Select Doctor</option>';

            if (data.length > 0) {
                doctorSelect.disabled = false;
                data.forEach(doctor => {
                    const opt = document.createElement('option');
                    opt.value = doctor.doctor_id; // make sure this matches your PHP output
                    opt.textContent = doctor.name + " (" + doctor.specialization + ")";
                    doctorSelect.appendChild(opt);
                });
            } else {
                doctorSelect.disabled = true;
                doctorSelect.innerHTML = '<option value="">No doctors available</option>';
            }
        });

});