<?php
include '../config.php';
$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM clinics WHERE resubmit_token = ?");
$stmt->execute([$token]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    die("Invalid or expired re-submission link.");
}

$remarks = $clinic['remarks'] ?? '';
$missingFields = [];

if (!empty($remarks) && str_contains($remarks, 'Missing required fields')) {
    $missingPart = trim(str_replace('Missing required fields:', '', $remarks));
    $missingFields = array_map('trim', explode(',', $missingPart));
}
?>

<h2>Edit & Re-Submit Clinic Information</h2>
<form method="POST" enctype="multipart/form-data">
    <?php if (in_array('Clinic name', $missingFields)): ?>
        <label>Clinic Name</label>
        <input type="text" name="clinic_name" value="<?= htmlspecialchars($clinic['clinic_name']) ?>" required>
    <?php endif; ?>

    <?php if (in_array('Address', $missingFields)): ?>
        <label>Clinic Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($clinic['address']) ?>" required>
    <?php endif; ?>

    <?php if (in_array('Contact info', $missingFields)): ?>
        <label>Contact Info</label>
        <input type="text" name="contact_info" value="<?= htmlspecialchars($clinic['contact_info']) ?>" required>
    <?php endif; ?>

    <?php if (in_array('Logo', $missingFields)): ?>
        <label>Clinic Logo</label>
        <input type="file" name="logo" accept="image/*" required>
    <?php endif; ?>

    <?php if (in_array('Business permit', $missingFields)): ?>
        <label>Business Permit</label>
        <input type="file" name="business_permit" accept="image/*,.pdf" required>
    <?php endif; ?>

    <button type="submit" name="resubmit">Re-Submit</button>
</form>

<?php
if (isset($_POST['resubmit'])) {
    $updates = [];
    $params = [];

    if (!empty($_POST['clinic_name'])) {
        $updates[] = "clinic_name = ?";
        $params[] = $_POST['clinic_name'];
    }

    if (!empty($_POST['address'])) {
        $updates[] = "address = ?";
        $params[] = $_POST['address'];
    }

    if (!empty($_POST['contact_info'])) {
        $updates[] = "contact_info = ?";
        $params[] = $_POST['contact_info'];
    }

    if (!empty($_FILES['logo']['name'])) {
        $logoPath = "../uploads/logos/" . uniqid() . "_" . basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);
        $updates[] = "logo = ?";
        $params[] = $logoPath;
    }

    if (!empty($_FILES['business_permit']['name'])) {
        $permitPath = "../uploads/permits/" . uniqid() . "_" . basename($_FILES['business_permit']['name']);
        move_uploaded_file($_FILES['business_permit']['tmp_name'], $permitPath);
        $updates[] = "business_permit = ?";
        $params[] = $permitPath;
    }

    if (count($updates) > 0) {
        $params[] = $token;
        $sql = "UPDATE clinics SET " . implode(', ', $updates) . ", status='pending', resubmit_token=NULL, remarks=NULL WHERE resubmit_token=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo "<script>alert('Clinic information re-submitted for review!'); window.location='../login.php';</script>";
    }
}
?>
