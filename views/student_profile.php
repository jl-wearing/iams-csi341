<?php
// views/student_profile.php - Student profile for updating attachment preferences
require_once('../config.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$currentPref = "";

// Fetch current preference (if any)
$res = $conn->query("SELECT preference FROM student_preferences WHERE user_id = $userId");
if ($res->num_rows > 0) {
    $currentPref = $res->fetch_assoc()['preference'];
}
$res->free();

$updateMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pref = $_POST['preference'] ?? "";
    if ($pref === "") {
        $updateMsg = "Please select a preference.";
    } else {
        // Insert or update the preference
        $stmt = $conn->prepare("REPLACE INTO student_preferences (user_id, preference) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $pref);
        $stmt->execute();
        $stmt->close();
        $updateMsg = "Preferences updated successfully.";
        $currentPref = $pref;
    }
}

// Define available fields for preference selection
$fields = ["Software Development", "Networking", "Data Science", "Marketing", "Finance", "Human Resources", "Engineering", "Other"];
?>
<?php include('header.php'); ?>
<div class="content">
    <h2>Your Profile & Preferences</h2>
    <?php if ($updateMsg): ?>
        <div class="message"><?php echo htmlspecialchars($updateMsg); ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <div class="form-group">
            <label for="preference">Preferred Attachment Field:</label>
            <select name="preference" id="preference" required>
                <option value="" disabled <?php if($currentPref==="") echo 'selected'; ?>>-- Select Field --</option>
                <?php foreach ($fields as $field): ?>
                    <option value="<?php echo $field; ?>" <?php if($currentPref === $field) echo 'selected'; ?>>
                        <?php echo $field; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Save Preferences</button>
    </form>
</div>
<?php include('footer.php'); ?>
