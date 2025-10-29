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

    // Get old profile picture
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    $old_pic = $old['profile_picture'] ?? null;

    // Handle profile picture upload
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
            die("Invalid file type. Allowed: jpg, jpeg, png, gif.");
        }

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $file_name;

            // delete old file if not default
            if ($old_pic && $old_pic !== "default.png" && file_exists($upload_dir . $old_pic)) {
                unlink($upload_dir . $old_pic);
            }
        } else {
            $profile_picture = $old_pic; // keep old if failed
        }
    } else {
        $profile_picture = $old_pic; // keep old if none uploaded
    }

    // Update DB
    $stmt = $pdo->prepare("UPDATE users 
        SET name = ?, email = ?, contact_number = ?, address = ?, profile_picture = ?
        WHERE user_id = ?");
    $stmt->execute([$name, $email, $contact, $address, $profile_picture, $user_id]);
    
    // Update session
    $_SESSION['name'] = $name;
    $_SESSION['profile_picture'] = $profile_picture;
    $_SESSION['email'] = $email;
    $_SESSION['contact_number'] = $contact;
    $_SESSION['address'] = $address;

    header("Location: index.php?msg=Profile updated successfully");
    exit;
}
