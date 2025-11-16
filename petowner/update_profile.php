<?php
session_start();
require '../config.php';

// Only allow pet_owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']);
    $email           = trim($_POST['email']);
    $contact         = trim($_POST['contact_number'] ?? '');
    $address         = trim($_POST['address']);
    $current_pass    = $_POST['current_password'] ?? '';
    $new_pass        = $_POST['new_password'] ?? '';
    $confirm_pass    = $_POST['confirm_password'] ?? '';

    $errors = [];

    // -------------------- VALIDATION --------------------
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

    // Password validation (if trying to change)
    if (!empty($current_pass) || !empty($new_pass) || !empty($confirm_pass)) {
        if (!password_verify($current_pass, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_pass) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = "New password and confirmation do not match.";
        }
    }

    // -------------------- PROFILE PICTURE --------------------
    $fileName = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $fileTmp = $_FILES['profile_picture']['tmp_name'];
        $fileExt = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $fileSize = $_FILES['profile_picture']['size'];

        if (!in_array($fileExt, $allowed)) {
            $errors[] = "Profile picture must be JPG or PNG.";
        }

        if ($fileSize > 2 * 1024 * 1024) {
            $errors[] = "Profile picture must not exceed 2MB.";
        }

        if (empty($errors)) {
            $targetDir = "../uploads/profiles/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Generate unique filename
            $fileName = bin2hex(random_bytes(8)) . '.' . $fileExt;
            $targetFilePath = $targetDir . $fileName;

            if (!move_uploaded_file($fileTmp, $targetFilePath)) {
                $errors[] = "Error uploading the profile picture.";
            } else {
                // Delete old profile picture if exists and not default
                if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'default.png') {
                    $oldFile = $targetDir . $user['profile_picture'];
                    if (file_exists($oldFile)) unlink($oldFile);
                }
            }
        }
    }

    // -------------------- HANDLE ERRORS --------------------
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: pet_owner_dashboard.php");
        exit;
    }

    // -------------------- UPDATE DB --------------------
    $fields = "name=?, email=?, contact_number=?, address=?";
    $params = [$name, $email, $contact, $address];

    if ($fileName) {
        $fields .= ", profile_picture=?";
        $params[] = $fileName;
    }

    if (!empty($new_pass)) {
        $fields .= ", password=?";
        $params[] = password_hash($new_pass, PASSWORD_DEFAULT);
    }

    $params[] = $user_id;

    $stmt = $pdo->prepare("UPDATE users SET $fields WHERE user_id=?");
    $stmt->execute($params);

    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: index.php");
    exit;
}
?>
