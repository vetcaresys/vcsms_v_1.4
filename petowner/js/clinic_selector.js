function reloadWithClinic(clinicId) {
    if (clinicId) {
        window.location.href = "?clinic_id=" + clinicId;
    }
}