<?php
// controllers/loginController.php - Processes login form submission
require_once('../config.php');
require_once('../models/UserModel.php');

// Get form input
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
if (empty($email) || empty($password)) {
    // Validation: ensure fields are not empty
    $_SESSION['error'] = "Please enter both email and password.";
    header("Location: ../index.php");
    exit();
}

// Attempt to authenticate the user
$user = UserModel::authenticate($email, $password);
if ($user) {
    // Login successful: initialize session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    // Redirect to the appropriate dashboard based on role
    switch ($user['role']) {
        case 'student':
            header("Location: ../views/student_dashboard.php");
            break;
        case 'coordinator':
            header("Location: ../views/coordinator_dashboard.php");
            break;
        case 'supervisor':
            header("Location: ../views/supervisor_dashboard.php");
            break;
        case 'organization':
            header("Location: ../views/organization_dashboard.php");
            break;
        case 'admin':
            header("Location: ../views/admin_dashboard.php");
            break;
    }
} else {
    // Authentication failed: send back to login with error
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../index.php");
}
exit();
?>
