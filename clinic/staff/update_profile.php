<?php
session_start();
include '../../config.php';

// 🔒 Access Control
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../clinic/staff/login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🧹 Sanitize inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);

    // 🖼 Default: keep current profile picture
    $profile_picture = null;

    // ✅ Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../uploads/profiles/";

        // Create directory if missing
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique file name
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed_exts)) {
            $fileName = uniqid("staff_" . $staff_id . "_") . "." . $ext;
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $destPath = $uploadDir . $fileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $profile_picture = $fileName;

                // 🧹 Delete old picture (if not default)
                $stmt = $pdo->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
                $stmt->execute([$staff_id]);
                $oldPic = $stmt->fetchColumn();

                if ($oldPic && $oldPic !== "default.png" && file_exists($uploadDir . $oldPic)) {
                    unlink($uploadDir . $oldPic);
                }
            }
        }
    }

    // ✅ Update database
    if ($profile_picture) {
        $stmt = $pdo->prepare("
            UPDATE staff 
            SET name = ?, email = ?, contact_number = ?, profile_picture = ? 
            WHERE staff_id = ?
        ");
        $stmt->execute([$name, $email, $contact_number, $profile_picture, $staff_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE staff 
            SET name = ?, email = ?, contact_number = ? 
            WHERE staff_id = ?
        ");
        $stmt->execute([$name, $email, $contact_number, $staff_id]);
    }

    // 🔄 Update session for live navbar refresh
    $_SESSION['name'] = $name;

    // 🎉 Redirect back to dashboard with success flag
    header("Location: index.php?profile_updated=1");
    exit;
}
?>
