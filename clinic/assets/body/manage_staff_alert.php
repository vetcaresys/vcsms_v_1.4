<?php if (isset($_SESSION['success'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '<?= addslashes($_SESSION['success']); ?>',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php elseif (isset($_SESSION['error'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: 'Error!',
            text: '<?= addslashes($_SESSION['error']); ?>',
            icon: 'error',
            confirmButtonColor: '#d33',
            timer: 2500,
            showConfirmButton: true
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- ✅ SweetAlert2 Alerts -->
<?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: '<?= isset($_SESSION['success']) ? "Success!" : "Error!"; ?>',
            text: '<?= addslashes($_SESSION['success'] ?? $_SESSION['error']); ?>',
            icon: '<?= isset($_SESSION['success']) ? "success" : "error"; ?>',
            confirmButtonColor: '<?= isset($_SESSION['success']) ? "#3085d6" : "#d33"; ?>',
            timer: 2500,
            showConfirmButton: true
        });
    </script>
    <?php unset($_SESSION['success'], $_SESSION['error']); ?>
<?php endif; ?>