let lastUpdated = null;

function refreshAppointments() {
    $.get('get_appointments.php', function (data) {
        const table = $('#appointmentsTable').DataTable();

        const temp = $('<table>').html(data);
        const newest = $(temp).find('tr:first').data('updated');

        // Notify if something changed
        if (lastUpdated && newest !== lastUpdated) {
            Swal.fire({
                icon: 'info',
                title: 'Appointment Updated',
                text: 'One or more of your appointments have been changed by the clinic.',
                timer: 4000,
                showConfirmButton: false
            });
        }

        lastUpdated = newest;

        table.clear().draw();
        $('#appointmentsTable tbody').html(data);
        table.rows.add($('#appointmentsTable tbody tr')).draw(); // re-init
    });
}

// Poll every 15 seconds
setInterval(refreshAppointments, 15000);

// Load immediately
refreshAppointments();