document.getElementById('clinicSelect').addEventListener('change', function () {
    const clinicId = this.value;
    const serviceSelect = document.getElementById('serviceSelect');

    if (!clinicId) {
        serviceSelect.innerHTML = '<option value="">Please select a clinic first</option>';
        serviceSelect.disabled = true;
        return;
    }

    // Fetch services from PHP
    fetch('get_services.php?clinic_id=' + clinicId)
        .then(res => res.json())
        .then(data => {
            serviceSelect.innerHTML = '';
            if (data.length > 0) {
                serviceSelect.disabled = false;
                serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';
                data.forEach(service => {
                    const opt = document.createElement('option');
                    opt.value = service.service_id;
                    opt.textContent = `${service.service_name}`;
                    serviceSelect.appendChild(opt);
                });
            } else {
                serviceSelect.disabled = true;
                serviceSelect.innerHTML = '<option value="">No services available</option>';
            }
        })
        .catch(err => {
            console.error(err);
            serviceSelect.disabled = true;
            serviceSelect.innerHTML = '<option value="">Error loading services</option>';
        });
});