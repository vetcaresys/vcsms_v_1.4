<?php
session_start();
require '../config.php';

// ✅ Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch all inquiries
$inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Inquiries - Admin</title>
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">

    <!-- BOOTSTRAP & ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- DATATABLES -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- ⭐ ENHANCED DESIGN -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #eef2f7;
            color: #2e2e2e;
            line-height: 1.6;
        }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(90deg, #0d6efd, #007bff);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .nav-link:hover {
            color: #ffc107 !important;
        }

        /* PAGE HEADER */
        h2 {
            font-weight: 700;
            color: #1a3d8f;
            margin-bottom: 1rem;
        }

        /* CARD WRAP */
        .content-card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        /* TABLE DESIGN */
        table.dataTable {
            border-radius: 12px;
            overflow: hidden;
        }

        table.dataTable thead {
            background: linear-gradient(90deg, #0d6efd, #007bff);
            color: white !important;
        }

        table.dataTable tbody tr:hover {
            background-color: #f0f6ff;
        }

        #inquiriesTable th {
            padding: 14px;
            font-size: 14px;
            font-weight: 600;
        }

        #inquiriesTable td {
            padding: 12px;
        }

        /* REPLY BUTTON */
        .btn-reply {
            background: #0d6efd;
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 8px;
            border: none;
            transition: .2s;
        }

        .btn-reply:hover {
            background: #094db5;
            transform: translateY(-2px);
        }

        /* MODAL */
        .modal-content {
            border-radius: 14px;
            border: none;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: linear-gradient(90deg, #0d6efd, #007bff);
            border-top-left-radius: 14px !important;
            border-top-right-radius: 14px !important;
            color: white;
        }

        /* INPUT FIELDS */
        .form-control {
            border-radius: 10px;
            padding: 10px;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 .18rem rgba(13,110,253,0.25);
        }

        /* FOOTER */
        footer {
            background: #0a1f4b !important;
            padding: 12px;
            text-align: center;
            color: white;
            font-size: 14px;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys Admin</a>

            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item">
                    <a href="admin_inquiries.php" class="nav-link text-white">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </a>
                </li>

                <!-- NOTIFICATION BELL -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                        <span id="notif_count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .65rem; padding: 3px 6px;"></span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 320px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <div id="notif_list" style="max-height: 350px; overflow-y: auto;">
                            <li class="text-center text-muted">Loading...</li>
                        </div>
                    </ul>
                </li>

                <!-- LOGOUT -->
                <li class="nav-item">
                    <form method="POST" action="logout.php" id="logoutForm" class="d-inline">
                        <button type="submit" class="btn btn-light btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container my-4">
        <div class="content-card">

            <h2>User Inquiries</h2>

            <table id="inquiriesTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($inquiries as $inq): ?>
                    <tr>
                        <td><?= $inq['inquiry_id'] ?></td>
                        <td><?= htmlspecialchars($inq['name']) ?></td>
                        <td><?= htmlspecialchars($inq['email']) ?></td>
                        <td><?= htmlspecialchars($inq['subject']) ?></td>
                        <td><?= htmlspecialchars($inq['message']) ?></td>
                        <td><?= ucfirst($inq['status']) ?></td>
                        <td><?= $inq['created_at'] ?></td>
                        <td>
                            <button class="btn-reply sendMessageBtn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#sendMessageModal"
                                    data-email="<?= htmlspecialchars($inq['email']) ?>"
                                    data-name="<?= htmlspecialchars($inq['name']) ?>"
                                    data-subject="<?= htmlspecialchars($inq['subject']) ?>">
                                <i class="bi bi-envelope-fill"></i> Reply
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

    <!-- SEND MESSAGE MODAL -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <form id="sendMessageForm" method="POST">

                    <div class="modal-header">
                        <h5 class="modal-title">Reply to Inquiry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <label>Email</label>
                        <input type="email" id="recipient_email" name="recipient_email" class="form-control mb-3" required>

                        <label>Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control mb-3" required>

                        <label>Message</label>
                        <textarea id="message" name="message" rows="5" class="form-control mb-2" required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Send Reply</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="mt-auto">
        All Rights Reserved. © 2025 VetCareSys
    </footer>

    <!-- JS SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(() => {
            $('#inquiriesTable').DataTable();
        });
    </script>

    <!-- PREFILL MODAL -->
    <script>
        $('.sendMessageBtn').click(function () {
            $('#recipient_email').val($(this).data('email'));
            $('#subject').val('Re: ' + $(this).data('subject'));
            $('#message').val(`Hi ${$(this).data('name')},\n\n`);
        });
    </script>

    <!-- AJAX SEND MESSAGE -->
    <script>
        $('#sendMessageForm').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: 'send_email.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Sent!', res.msg, 'success');
                        $('#sendMessageModal').modal('hide');
                        $('#sendMessageForm')[0].reset();
                    } else {
                        Swal.fire('Error!', res.msg, 'error');
                    }
                },
                error: () => Swal.fire('Error!', 'Network error', 'error')
            });
        });
    </script>

</body>
</html>
