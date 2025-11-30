// Cancel button for pending appointments
$(document).on('click', '.cancel-btn', function () {
    const id = $(this).data('id');
    Swal.fire({
        title: "Cancel Appointment?",
        text: "You cannot undo this action.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, cancel it"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "cancel_appointment.php?id=" + id;
        }
    });
});