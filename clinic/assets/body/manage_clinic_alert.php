<?php if (!empty($_SESSION['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                title: 'Success!',
                text: <?= json_encode($_SESSION['msg']) ?>,
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
        });
    </script>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>