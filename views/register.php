<?php
// views/register.php - User Registration page
require_once('../config.php');
require_once('../models/UserModel.php');

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'student': header("Location: student_dashboard.php"); break;
        case 'coordinator': header("Location: coordinator_dashboard.php"); break;
        case 'supervisor': header("Location: supervisor_dashboard.php"); break;
        case 'organization': header("Location: organization_dashboard.php"); break;
        case 'admin': header("Location: admin_dashboard.php"); break;
    }
    exit();
}

$error = "";
$name = $email = $password = $confirm = $role = $industry = "";
$capacity = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate input
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";
    $confirm = $_POST['confirm_password'] ?? "";
    $role = $_POST['role'] ?? "";
    $industry = trim($_POST['industry'] ?? "");
    $capacity = $_POST['capacity'] ?? "";
    if ($name === "" || $email === "" || $password === "" || $confirm === "" || $role === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif ($role === 'organization') {
        // Additional validation for organization
        if ($industry === "" || $capacity === "") {
            $error = "Please provide industry and capacity for the organization.";
        } elseif (!ctype_digit($capacity) || intval($capacity) < 1) {
            $error = "Capacity must be a positive number.";
        }
    }
    if ($error === "") {
        // Check if email is already taken
        if (UserModel::getByEmail($email)) {
            $error = "An account with that email already exists.";
        } else {
            // Create the new user
            $newUserId = UserModel::createUser($name, $email, $password, $role);
            if ($newUserId === false) {
                $error = "Registration failed. Please try again.";
            } else {
                // If user is an organization, save organization details
                if ($role === 'organization') {
                    $capInt = intval($capacity);
                    $stmt = $conn->prepare("INSERT INTO organization (user_id, industry, capacity) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $newUserId, $industry, $capInt);
                    $stmt->execute();
                    $stmt->close();
                }
                // Auto-login the user after successful registration
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;
                // Redirect to the user's dashboard
                switch ($role) {
                    case 'student': header("Location: student_dashboard.php"); break;
                    case 'coordinator': header("Location: coordinator_dashboard.php"); break;
                    case 'supervisor': header("Location: supervisor_dashboard.php"); break;
                    case 'organization': header("Location: organization_dashboard.php"); break;
                }
                exit();
            }
        }
    }
}

// Define industry options for dropdown (for demonstration purposes)
$industries = ["Software Development", "Networking", "Data Science", "Marketing", "Finance", "Human Resources", "Engineering", "Other"];
?>
<?php include('header.php'); ?>
<div class="form-container">
    <h2>Register</h2>
    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <div class="form-group">
            <label for="name">Full Name / Organization Name:</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <div class="form-group">
            <label for="role">Register as:</label>
            <select name="role" id="role" required>
                <option value="" disabled <?php if($role==="") echo 'selected'; ?>>-- Select Role --</option>
                <option value="student" <?php if($role==="student") echo 'selected'; ?>>Student</option>
                <option value="coordinator" <?php if($role==="coordinator") echo 'selected'; ?>>Coordinator</option>
                <option value="supervisor" <?php if($role==="supervisor") echo 'selected'; ?>>Supervisor</option>
                <option value="organization" <?php if($role==="organization") echo 'selected'; ?>>Organization</option>
            </select>
        </div>
        <!-- Organization-specific fields (shown only if "Organization" is selected) -->
        <div id="orgFields" style="display: <?php echo ($role==='organization') ? 'block' : 'none'; ?>;">
            <div class="form-group">
                <label for="industry">Industry/Field:</label>
                <select name="industry" id="industry">
                    <option value="" disabled <?php if($industry==="") echo 'selected'; ?>>-- Select Industry --</option>
                    <?php foreach ($industries as $ind): ?>
                        <option value="<?php echo $ind; ?>" <?php if($industry === $ind) echo 'selected'; ?>>
                            <?php echo $ind; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="capacity">Capacity (No. of students you can host):</label>
                <input type="number" name="capacity" id="capacity" value="<?php echo htmlspecialchars($capacity); ?>" min="1">
            </div>
        </div>
        <button type="submit" class="btn">Register</button>
        <p>Already have an account? <a href="../index.php">Login here</a>.</p>
    </form>
</div>
<?php include('footer.php'); ?>
