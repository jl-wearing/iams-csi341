<?php
// controllers/logout.php - Logs out the current user
session_start();
session_unset();
session_destroy();
// Redirect to login page
header("Location: ../index.php");
exit();
?>
