<?php
include '../config.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM clinics WHERE resubmit_token = ?");
$stmt->execute([$token]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    die("Invalid or expired re-submission link.");
}
?>

<h2>Edit Clinic Information</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Clinic Name</label>
    <input type="text" name="clinic_name" value="<?= htmlspecialchars($clinic['clinic_name']) ?>" required>
    
    <label>Business Permit</label>
    <input type="file" name="business_permit" required>

    <button type="submit" name="resubmit">Re-Submit</button>
</form>

<?php
if (isset($_POST['resubmit'])) {
    // handle updates
    $stmt = $pdo->prepare("UPDATE clinics SET clinic_name=?, status='pending', resubmit_token=NULL WHERE resubmit_token=?");
    $stmt->execute([$_POST['clinic_name'], $token]);

    echo "<script>alert('Clinic info re-submitted for review!'); window.location='../login.php';</script>";
}
?>
