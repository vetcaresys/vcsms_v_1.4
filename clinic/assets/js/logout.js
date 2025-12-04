document.getElementById('logoutBtn').addEventListener('click', function (e) {
    e.preventDefault(); // Prevent form from submitting instantly

    Swal.fire({
        title: 'Are you sure you want to logout?',
        text: "You’ll be logged out of your current session.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'No, stay here'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form only if confirmed
            document.getElementById('logoutForm').submit();
        }
    });
});