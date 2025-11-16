<?php
session_start();
include '../../config.php';

// ✅ Check if logged in as doctor
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../clinic/doctors/login.php");
    exit;
}

$doctor_id = $_SESSION['staff_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $specialization = trim($_POST['specialization'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $profile_picture = null;

    /* ---------------------------------------------------
        PROFILE PICTURE UPLOAD
    --------------------------------------------------- */
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../uploads/profiles/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = uniqid("doctor_" . $doctor_id . "_") . "." . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $profile_picture = $fileName;

            // Delete old picture
            $stmt = $pdo->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
            $stmt->execute([$doctor_id]);
            $oldPic = $stmt->fetchColumn();
            if ($oldPic && $oldPic !== "default.png" && file_exists($uploadDir . $oldPic)) {
                unlink($uploadDir . $oldPic);
            }
        }
    }

    /* ---------------------------------------------------
        UPDATE BASIC PROFILE INFO
    --------------------------------------------------- */
    if ($profile_picture) {
        $stmt = $pdo->prepare("
            UPDATE staff 
            SET name = ?, email = ?, contact_number = ?, profile_picture = ?
            WHERE staff_id = ?
        ");
        $stmt->execute([$name, $email, $contact_number, $profile_picture, $doctor_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE staff 
            SET name = ?, email = ?, contact_number = ?
            WHERE staff_id = ?
        ");
        $stmt->execute([$name, $email, $contact_number, $doctor_id]);
    }

    /* ---------------------------------------------------
        UPDATE / INSERT DOCTOR DETAILS
    --------------------------------------------------- */
    $check = $pdo->prepare("SELECT COUNT(*) FROM doctors WHERE staff_id = ?");
    $check->execute([$doctor_id]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $updateDoc = $pdo->prepare("
            UPDATE doctors 
            SET specialization = ?, education = ?, experience = ?, license_no = ?
            WHERE staff_id = ?
        ");
        $updateDoc->execute([$specialization, $education, $experience, $license_no, $doctor_id]);
    } else {
        $insertDoc = $pdo->prepare("
            INSERT INTO doctors (staff_id, specialization, education, experience, license_no)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertDoc->execute([$doctor_id, $specialization, $education, $experience, $license_no]);
    }

    /* ---------------------------------------------------
        CHANGE PASSWORD (OPTIONAL)
    --------------------------------------------------- */
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {

        // Must fill all fields
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            header("Location: index.php?error=Fill all password fields");
            exit;
        }

        // Get old password hash
        $stmt = $pdo->prepare("SELECT password FROM staff WHERE staff_id = ?");
        $stmt->execute([$doctor_id]);
        $old_hashed_password = $stmt->fetchColumn();

        // Check current password
        if (!password_verify($current_password, $old_hashed_password)) {
            header("Location: index.php?error=Incorrect current password");
            exit;
        }

        // New password match
        if ($new_password !== $confirm_password) {
            header("Location: index.php?error=New passwords do not match");
            exit;
        }

        // Minimum length
        if (strlen($new_password) < 6) {
            header("Location: index.php?error=Password must be at least 6 characters");
            exit;
        }

        // Hash new password
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update in DB
        $updatePass = $pdo->prepare("UPDATE staff SET password = ? WHERE staff_id = ?");
        $updatePass->execute([$new_hashed, $doctor_id]);
    }

    /* ---------------------------------------------------
        UPDATE SESSION NAME
    --------------------------------------------------- */
    $_SESSION['name'] = $name;

    header("Location: index.php?profile_updated=1");
    exit;
}
?>
