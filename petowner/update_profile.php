<?php
session_start();
require '../config.php';

// Only allow pet_owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address']);

    $errors = [];

    // Validation
    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!empty($contact) && !preg_match('/^09\d{9}$/', $contact)) {
        $errors[] = "Contact number must be 11 digits starting with 09.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    // File validation (if uploaded)
    $fileName = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Profile picture must be JPG or PNG.";
        }
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Profile picture must not exceed 2MB.";
        }

        if (empty($errors)) {
            $targetDir = "../uploads/profiles/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                $errors[] = "Error uploading the profile picture.";
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: index.php");
        exit;
    }

    // Build query
    if ($fileName) {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, contact_number=?, address=?, profile_picture=? WHERE user_id=?");
        $stmt->execute([$name, $email, $contact, $address, $fileName, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, contact_number=?, address=? WHERE user_id=?");
        $stmt->execute([$name, $email, $contact, $address, $user_id]);
    }

    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: index.php");
    exit;
}
?>
