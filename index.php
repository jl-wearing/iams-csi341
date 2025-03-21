<?php
// index.php - Login page
require_once('config.php');
if (isset($_SESSION['user_id'])) {
    // If already logged in, redirect to appropriate dashboard
    switch ($_SESSION['role']) {
        case 'student': header("Location: views/student_dashboard.php"); break;
        case 'coordinator': header("Location: views/coordinator_dashboard.php"); break;
        case 'supervisor': header("Location: views/supervisor_dashboard.php"); break;
        case 'organization': header("Location: views/organization_dashboard.php"); break;
        case 'admin': header("Location: views/admin_dashboard.php"); break;
    }
    exit();
}
?>
<?php include('views/header.php'); ?>
<div class="form-container">
    <h2>Login</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <form method="post" action="controllers/loginController.php">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
        <p>No account? <a href="views/register.php">Register here</a>.</p>
    </form>
</div>
<?php include('views/footer.php'); ?>
