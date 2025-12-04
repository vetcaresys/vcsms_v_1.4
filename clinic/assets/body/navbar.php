<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                <li class="nav-item"><a href="manage_clinic.php" class="nav-link text-white">Manage Clinic</a></li>
                <li class="nav-item"><a href="manage_staff.php" class="nav-link text-white">Manage Staff</a></li>
                <li class="nav-item"><a href="manage_clinic_schedules.php" class="nav-link text-white">Manage
                        Schedules</a></li>
                <li class="nav-item"><a href="manage_services.php" class="nav-link text-white">Manage Services</a>
                </li>
            </ul>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                        <span id="notif_count"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.65rem; padding: 3px 6px;">
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 320px; max-height: 400px;"
                        id="notif_list_container">

                        <li class="d-flex justify-content-between align-items-center mb-2 px-2">
                            <h6 class="mb-0">Notifications</h6>
                            <button id="mark_all_btn" class="btn btn-link btn-sm p-0 text-decoration-none"
                                style="font-size: 0.8rem;" disabled>
                                Mark all as read
                            </button>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <div id="notif_list" style="max-height: 350px; overflow-y: auto;">
                            <li class="text-center text-muted">Loading...</li>
                        </div>
                    </ul>
                </li>
            </ul>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                    id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= $profilePic ?>" alt="Profile" width="32" height="32" class="rounded-circle me-2">
                    <strong><?= htmlspecialchars($name) ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">View
                            Profile</a></li>
                    <li><a class="dropdown-item" href="manage_clinic_details.php">Update Clinic Info</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="logout.php" id="logoutForm" class="m-0">
                            <button class="dropdown-item text-danger" type="submit" id="logoutBtn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>