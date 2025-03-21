<?php
// views/header.php - Common page header with navigation menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>IAMS - Industrial Attachment Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="navbar">
    <div class="logo">IAMS</div>
    <nav>
        <ul>
            <?php if (isset($_SESSION['user_id'])):
                $role = $_SESSION['role']; ?>
                <?php if ($role === 'student'): ?>
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="student_profile.php">Profile</a></li>
                <li><a href="../controllers/logout.php">Logout</a></li>
            <?php elseif ($role === 'coordinator'): ?>
                <li><a href="coordinator_dashboard.php">Dashboard</a></li>
                <li><a href="../controllers/logout.php">Logout</a></li>
            <?php elseif ($role === 'supervisor'): ?>
                <li><a href="supervisor_dashboard.php">Dashboard</a></li>
                <li><a href="../controllers/logout.php">Logout</a></li>
            <?php elseif ($role === 'organization'): ?>
                <li><a href="organization_dashboard.php">Dashboard</a></li>
                <li><a href="../controllers/logout.php">Logout</a></li>
            <?php elseif ($role === 'admin'): ?>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="../controllers/logout.php">Logout</a></li>
            <?php endif; ?>
            <?php else: ?>
                <!-- Not logged in: show login/register links -->
                <li><a href="../index.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main class="container">
