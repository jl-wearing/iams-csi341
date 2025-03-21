<?php
// views/supervisor_dashboard.php - Supervisor dashboard (list of students to evaluate)
require_once('../config.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: ../index.php");
    exit();
}

$supervisorId = $_SESSION['user_id'];
$evalMsg = "";

// Process evaluation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studId = $_POST['student_id'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $comments = trim($_POST['comments'] ?? '');
    // Verify that this student is indeed supervised by the current user
    $check = $conn->prepare("SELECT 1 FROM attachments WHERE student_id = ? AND supervisor_id = ?");
    $check->bind_param("ii", $studId, $supervisorId);
    $check->execute();
    $resCheck = $check->get_result();
    $validStudent = $resCheck->num_rows > 0;
    $check->close();
    if (!$validStudent) {
        $evalMsg = "Unauthorized or invalid student.";
    } elseif ($rating === "" || $comments === "") {
        $evalMsg = "Please provide both rating and comments.";
    } else {
        // Check if an evaluation already exists for this student
        $res = $conn->query("SELECT id FROM evaluation WHERE student_id = $studId");
        if ($res->num_rows > 0) {
            // Update existing evaluation
            $stmt = $conn->prepare("UPDATE evaluation SET rating = ?, comments = ?, evaluated_at = NOW() WHERE student_id = ? AND supervisor_id = ?");
            $stmt->bind_param("isii", $rating, $comments, $studId, $supervisorId);
            $stmt->execute();
            $stmt->close();
            $evalMsg = "Updated evaluation for student ID $studId.";
        } else {
            // Insert new evaluation
            $stmt = $conn->prepare("INSERT INTO evaluation (student_id, supervisor_id, rating, comments) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $studId, $supervisorId, $rating, $comments);
            $stmt->execute();
            $stmt->close();
            $evalMsg = "Submitted evaluation for student ID $studId.";
        }
    }
}

// Fetch all students supervised by this user, including any existing evaluations
$sql = "SELECT u.id AS student_id, u.name AS student_name, org_user.name AS org_name, e.rating, e.comments
        FROM attachments a
        JOIN users u ON a.student_id = u.id
        JOIN organization org ON a.org_id = org.id
        JOIN users org_user ON org.user_id = org_user.id
        LEFT JOIN evaluation e ON a.student_id = e.student_id
        WHERE a.supervisor_id = $supervisorId";
$result = $conn->query($sql);
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$result->free();

// Count how many evaluations are pending (not yet done)
$pendingCount = 0;
foreach ($students as $s) {
    if ($s['rating'] === null) {
        $pendingCount++;
    }
}
?>
<?php include('header.php'); ?>
<div class="dashboard">
    <h2>Supervisor Dashboard</h2>
    <div class="notifications">
        <?php if (empty($students)): ?>
            <div class="notice">You have no students to supervise at this time.</div>
        <?php elseif ($pendingCount > 0): ?>
            <div class="notice">You have <?php echo $pendingCount; ?> pending evaluation(s) to complete.</div>
        <?php else: ?>
            <div class="notice">All assigned students have been evaluated.</div>
        <?php endif; ?>
    </div>
    <?php if ($evalMsg): ?>
        <div class="message"><?php echo htmlspecialchars($evalMsg); ?></div>
    <?php endif; ?>
    <?php if (!empty($students)): ?>
        <?php foreach ($students as $stu): ?>
            <div class="eval-item">
                <h3><?php echo htmlspecialchars($stu['student_name']); ?> (Org: <?php echo htmlspecialchars($stu['org_name']); ?>)</h3>
                <form method="post" action="">
                    <input type="hidden" name="student_id" value="<?php echo $stu['student_id']; ?>">
                    <div class="form-group">
                        <label for="rating_<?php echo $stu['student_id']; ?>">Rating:</label>
                        <select name="rating" id="rating_<?php echo $stu['student_id']; ?>" required>
                            <option value="" disabled <?php if($stu['rating'] === null) echo 'selected'; ?>>-- Select --</option>
                            <?php for ($r = 1; $r <= 5; $r++): ?>
                                <option value="<?php echo $r; ?>" <?php if($stu['rating'] !== null && $stu['rating'] == $r) echo 'selected'; ?>>
                                    <?php echo $r; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="comments_<?php echo $stu['student_id']; ?>">Comments:</label>
                        <textarea name="comments" id="comments_<?php echo $stu['student_id']; ?>" rows="3" required><?php echo htmlspecialchars($stu['comments'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Evaluation</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include('footer.php'); ?>
