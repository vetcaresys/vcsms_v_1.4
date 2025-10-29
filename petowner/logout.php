<?php
session_start();            // Start the session
session_unset();            // Clear all session variables
session_destroy();          // Destroy the session

header("Location: ../login.php"); // Redirect back to login
exit;
?>
