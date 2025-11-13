<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$contact = $_POST['contact_number'];

if (!preg_match('/^(\+63|0)\d{10}$/', $contact)) {
    $_SESSION['msg'] = '<div class="alert alert-danger">Invalid contact number format.</div>';
    header('Location: manage_clinic.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $profile_picture = null;

    // ✅ Password fields
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Get existing user info
    $stmt = $pdo->prepare("SELECT profile_picture, password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $old_pic = $user['profile_picture'] ?? null;
    $old_pass = $user['password'] ?? '';

    // ✅ Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $upload_dir = "../uploads/profiles/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $original_name = basename($_FILES["profile_picture"]["name"]);
        $safe_name = preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $original_name);
        $file_name = time() . "_" . $safe_name;
        $target_file = $upload_dir . $file_name;

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Invalid file type. Allowed: jpg, jpeg, png, gif.</div>';
            header("Location: manage_clinic_details.php");
            exit;
        }

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $file_name;

            // delete old file if not default
            if ($old_pic && $old_pic !== "default.png" && file_exists($upload_dir . $old_pic)) {
                unlink($upload_dir . $old_pic);
            }
        } else {
            $profile_picture = $old_pic;
        }
    } else {
        $profile_picture = $old_pic;
    }

    // ✅ Handle password update (optional)
    $password_to_save = $old_pass; // default keep old
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Please fill in all password fields.</div>';
            header("Location: manage_clinic_details.php");
            exit;
        }

        if (!password_verify($current_password, $old_pass)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Current password is incorrect.</div>';
            header("Location: manage_clinic_details.php");
            exit;
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['msg'] = '<div class="alert alert-warning">New passwords do not match.</div>';
            header("Location: manage_clinic_details.php");
            exit;
        }

        if (strlen($new_password) < 6) {
            $_SESSION['msg'] = '<div class="alert alert-warning">New password must be at least 6 characters.</div>';
            header("Location: manage_clinic_details.php");
            exit;
        }

        $password_to_save = password_hash($new_password, PASSWORD_BCRYPT);
    }

    // ✅ Update DB
    $stmt = $pdo->prepare("UPDATE users 
        SET name = ?, email = ?, contact_number = ?, address = ?, profile_picture = ?, password = ?
        WHERE user_id = ?");
    $stmt->execute([$name, $email, $contact, $address, $profile_picture, $password_to_save, $user_id]);
    
   // ✅ Update session
$_SESSION['name'] = $name;
$_SESSION['profile_picture'] = $profile_picture;
$_SESSION['email'] = $email;
$_SESSION['contact_number'] = $contact;
$_SESSION['address'] = $address;

// ✅ Set success message for SweetAlert
$_SESSION['update_msg'] = "Profile updated successfully!";

// ✅ Redirect back to manage_clinic_details.php
header("Location: index.php");
exit;

}
?>
