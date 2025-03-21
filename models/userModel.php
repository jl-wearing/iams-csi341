<?php
// models/UserModel.php - Contains user-related database operations
class UserModel {
    // Fetch a user record by email (for login and duplicate checks)
    public static function getByEmail($email) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    // Create a new user account (for registration). Returns new user ID or false on failure.
    public static function createUser($name, $email, $password, $role) {
        global $conn;
        // Hash the password for security
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $passwordHash, $role);
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            return $newId;
        } else {
            // Insert failed (e.g., email already exists due to UNIQUE constraint)
            $stmt->close();
            return false;
        }
    }

    // Authenticate a user by email and password. Returns user data array if successful, or false if not.
    public static function authenticate($email, $password) {
        global $conn;
        $user = self::getByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct
            return $user;
        }
        return false;
    }
}
?>
