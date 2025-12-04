<?php if (isset($_SESSION['success'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '<?= addslashes($_SESSION['success']) ?>',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['success']);
endif; ?>

<?php if (!empty($_SESSION['update_msg'])): ?>
    <script>
        Swal.fire({
            icon: <?= (stripos($_SESSION['update_msg'], '❌') !== false || stripos($_SESSION['update_msg'], '⚠️') !== false) ? "'error'" : "'success'" ?>,
            title: 'Profile Update',
            text: <?= json_encode($_SESSION['update_msg']) ?>,
            confirmButtonColor: '#3085d6'
        });
    </script>
    <?php unset($_SESSION['update_msg']);
endif; ?>

<?php if (!empty($_GET['msg'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Updated Successfully!',
            text: <?= json_encode($_GET['msg']) ?>,
            confirmButtonColor: '#3085d6'
        });
    </script>
<?php endif; ?>