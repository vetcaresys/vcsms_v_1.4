document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        const href = btn.getAttribute('href');
        Swal.fire({
            title: "Are you sure?",
            text: "This staff will be permanently deleted.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, delete it!"
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
});