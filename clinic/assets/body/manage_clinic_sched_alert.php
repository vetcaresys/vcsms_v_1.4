<?php if (!empty($msg)): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?= addslashes($msg) ?>',
            confirmButtonColor: '#3085d6'
        });
    </script>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?= strip_tags(addslashes($errorMsg)) ?>',
            confirmButtonColor: '#d33'
        });
    </script>
<?php endif; ?>