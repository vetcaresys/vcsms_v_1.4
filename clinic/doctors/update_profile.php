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
    $profile_picture = null;

    // ✅ Handle profile picture upload (if any)
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

            // 🔄 Delete old picture if not default
            $stmt = $pdo->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
            $stmt->execute([$doctor_id]);
            $oldPic = $stmt->fetchColumn();
            if ($oldPic && $oldPic !== "default.png" && file_exists($uploadDir . $oldPic)) {
                unlink($uploadDir . $oldPic);
            }
        }
    }

    // ✅ Update staff table (doctor profile)
    if ($profile_picture) {
        $stmt = $pdo->prepare("UPDATE staff SET name = ?, email = ?, contact_number = ?, profile_picture = ? WHERE staff_id = ?");
        $stmt->execute([$name, $email, $contact_number, $profile_picture, $doctor_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE staff SET name = ?, email = ?, contact_number = ? WHERE staff_id = ?");
        $stmt->execute([$name, $email, $contact_number, $doctor_id]);
    }

    // ✅ Update session values (so navbar updates immediately)
    $_SESSION['name'] = $name;

    header("Location: index.php?profile_updated=1");
    exit;
}