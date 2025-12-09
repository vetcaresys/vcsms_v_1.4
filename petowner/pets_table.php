<!-- Pets List -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-secondary text-white py-3 rounded-top d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Your Pets</h5>

        <!-- Export Buttons -->
        <div class="d-flex gap-2">
            <!-- <button id="printTable" class="btn btn-light btn-sm"><i class="bi bi-printer"></i> Print</button> -->
            <button id="exportExcel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i>
                Excel</button>
        </div>
    </div>

    <div class="card-body">

        <!-- Search + Show Entries -->
        <div class="row mb-4 align-items-center">

            <!-- Show Entries -->
            <div class="col-md-2">
                <div class="input-group shadow-sm">
                    <label class="input-group-text bg-white">Show</label>
                    <select id="showEntries" class="form-select">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="9999">All</option>
                    </select>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="col-md-3 ms-auto">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0"
                        placeholder="Search pets...">
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="petsTable" class="table table-hover table-bordered align-middle shadow-sm">
                <thead class="table-light">
                    <tr class="text-center">
                        <th>#</th>
                        <th>Photo</th>
                        <th class="sortable">Name</th>
                        <th class="sortable">Species</th>
                        <th class="sortable">Breed</th>
                        <th class="sortable">Age</th>
                        <th class="sortable">Status</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $num = 1;
                    foreach ($pets as $pet):

                        // --- SAFE AGE COMPUTATION ---
                        $age = "Unknown";
                        if (!empty($pet['birth_date']) && strtotime($pet['birth_date'])) {
                            $birth = new DateTime($pet['birth_date']);
                            $today = new DateTime();
                            $diff = $today->diff($birth);

                            if ($diff->y > 0) {
                                $age = $diff->y . " year" . ($diff->y > 1 ? "s" : "");
                            } elseif ($diff->m > 0) {
                                $age = $diff->m . " month" . ($diff->m > 1 ? "s" : "");
                            } else {
                                $age = $diff->d . " day" . ($diff->d > 1 ? "s" : "");
                            }
                        }
                        // -------------------------------------
                        ?>
                        <tr>
                            <td><?= $num++; ?></td>
                            <!-- PHOTO COLUMN -->
                            <td class="text-center">
                                <?php if (!empty($pet['photo'])): ?>
                                    <img src="../uploads/pets/<?= htmlspecialchars($pet['photo']); ?>" width="60" height="60"
                                        class="rounded-circle border" style="object-fit: cover;"
                                        onerror="this.src='../uploads/pets/default.png'">
                                <?php else: ?>
                                    <img src="../uploads/pets/default.png" width="60" height="60" class="rounded-circle border">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($pet['pet_name']); ?></td>
                            <td><?= htmlspecialchars($pet['species']); ?></td>
                            <td><?= htmlspecialchars($pet['breed']); ?></td>
                            <td><?= $age; ?></td>
                            <td><?= htmlspecialchars($pet['status']); ?></td>
                            <td style="max-width: 200px;"><?= htmlspecialchars($pet['description']); ?></td>

                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#editPetModal<?= $pet['pet_id']; ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- <button class="btn btn-sm btn-danger"
                                        onclick="return confirmDelete(event, <?= $pet['pet_id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button> -->
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- No results -->
            <p id="noResults" class="text-center text-muted mt-3" style="display:none;">No matching pets found.</p>
        </div>

        <!-- Pagination -->
        <nav>
            <ul id="pagination" class="pagination justify-content-end mt-3"></ul>
        </nav>

    </div>
</div>


<style>
    .sortable::after {
        content: " ⇅";
        font-size: 0.8rem;
        opacity: 0.4;
    }

    .sortable.asc::after {
        content: " ↑";
        opacity: 1;
    }

    .sortable.desc::after {
        content: " ↓";
        opacity: 1;
    }

    .page-link {
        color: #6c63ff;
    }

    .page-item.active .page-link {
        background-color: #6c63ff;
        border-color: #6c63ff;
    }
</style>