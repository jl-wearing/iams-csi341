<?php
// config.php - Database configuration and session initialization
session_start();  // Start session for authentication tracking

// Database connection settings
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'makeitcount';
$DB_NAME = 'iams_db';

// Connect to MySQL database
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
