<?php if (isset($_SESSION['booking_msg'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['booking_msg'] === "success" ? "success" : "error" ?>',
            title: '<?= $_SESSION['booking_msg'] === "success" ? "Appointment booked!" : "Booking failed." ?>',
            text: '<?= $_SESSION['booking_msg'] === "success"
                ? "Please wait for approval."
                : ($_SESSION['booking_error_text'] ?? "Please try again later.") ?>'
        });
    </script>
    <?php
    unset($_SESSION['booking_msg']);
    unset($_SESSION['booking_error_text']);
endif; ?>