<!-- main content -->
<div class="container mt-4">
    <div class="p-4 border rounded shadow-sm bg-white">
        <?php if ($mainClinic): ?>
            <!-- MAIN CLINIC VIEW -->
            <h2><?= htmlspecialchars($mainClinic['clinic_name']) ?> (Main Clinic)</h2>
            <p><strong>Address:</strong> <?= htmlspecialchars($mainClinic['address']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($mainClinic['contact_info']) ?></p>

            <!-- Add Branch Button -->
            <a href="register_branch.php?parent=<?= $mainClinic['clinic_id'] ?>" class="btn btn-primary mb-3">
                + Register New Branch
            </a>

            <!-- Branch List -->
            <h3>Branches</h3>
            <ul class="list-group">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM clinics WHERE parent_clinic_id = ?");
                $stmt->execute([$mainClinic['clinic_id']]);
                $branches = $stmt->fetchAll();

                if ($branches):
                    foreach ($branches as $branch): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($branch['clinic_name']) ?></strong><br>
                            <?= htmlspecialchars($branch['address']) ?><br>
                            <small><?= htmlspecialchars($branch['contact_info']) ?></small>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="list-group-item text-muted">No branches yet.</li>
                <?php endif; ?>
            </ul>


        <?php elseif ($branchClinic): ?>
            <!-- BRANCH CLINIC VIEW -->
            <div class="alert alert-info">
                <strong>Note:</strong> This account belongs to a <b>branch clinic</b>.
                Only the <b>main clinic</b> can register and manage branches.
            </div>
            <h4><?= htmlspecialchars($branchClinic['clinic_name']) ?> (Branch Clinic)</h4>
            <p><strong>Address:</strong> <?= htmlspecialchars($branchClinic['address']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($branchClinic['contact_info']) ?></p>

        <?php else: ?>
            <!-- USER WITHOUT A CLINIC -->
            <div class="alert alert-warning">
                You haven’t registered your main clinic yet.
                <a href="clinic_details.php" class="btn btn-sm btn-success">Register Main Clinic</a>
            </div>
        <?php endif; ?>
    </div>
</div>