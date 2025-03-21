<?php
// views/student_dashboard.php - Student's main dashboard
require_once('../config.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Check if the student has been assigned to an organization
$stmt = $conn->prepare("SELECT org.industry, org_user.name AS org_name, sup.name AS supervisor_name
                        FROM attachments a
                        JOIN organization org ON a.org_id = org.id
                        JOIN users org_user ON org.user_id = org_user.id
                        LEFT JOIN users sup ON a.supervisor_id = sup.id
                        WHERE a.student_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$placement = $res->fetch_assoc();
$stmt->close();
$assigned = $placement ? true : false;
$orgName = $assigned ? $placement['org_name'] : null;
$orgIndustry = $assigned ? $placement['industry'] : null;
$supName = $assigned ? ($placement['supervisor_name'] ?? 'N/A') : null;

// Handle weekly logbook submission
$logMsg = "";
if (isset($_POST['submit_logbook'])) {
    if (!$assigned) {
        $logMsg = "You cannot submit a logbook without an attachment placement.";
    } else {
        $week = $_POST['week'] ?? '';
        $content = trim($_POST['log_content'] ?? '');
        if ($week === '' || $content === '') {
            $logMsg = "Please fill in the week number and content.";
        } else {
            $stmt = $conn->prepare("INSERT INTO logbook (student_id, week_no, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $userId, $week, $content);
            $stmt->execute();
            $stmt->close();
            $logMsg = "Logbook for week $week submitted.";
        }
    }
}

// Handle final report submission
$reportMsg = "";
if (isset($_POST['submit_report'])) {
    if (!$assigned) {
        $reportMsg = "You cannot submit a report without an attachment placement.";
    } else {
        if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] != 0) {
            $reportMsg = "Please select a report file to upload.";
        } else {
            $fileName = $_FILES['report_file']['name'];
            $fileSize = $_FILES['report_file']['size'];
            $fileTmp = $_FILES['report_file']['tmp_name'];
            // Validate file type and size
            $allowedExt = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $reportMsg = "Only PDF, DOC, or DOCX files are allowed.";
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $reportMsg = "File size must be 2MB or less.";
            } else {
                // Ensure upload directory exists
                $uploadDir = "../uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFileName = "report_{$userId}_" . time() . ".$ext";
                move_uploaded_file($fileTmp, $uploadDir . $newFileName);
                $savePath = "uploads/" . $newFileName;
                // Save or update report record in database
                $checkRes = $conn->query("SELECT id, file_path FROM reports WHERE student_id = $userId");
                if ($checkRes->num_rows > 0) {
                    $row = $checkRes->fetch_assoc();
                    $oldPath = $row['file_path'];
                    if ($oldPath && file_exists("../$oldPath")) {
                        unlink("../$oldPath");  // remove old file
                    }
                    $stmt = $conn->prepare("UPDATE reports SET file_path = ?, submitted_at = NOW() WHERE student_id = ?");
                    $stmt->bind_param("si", $savePath, $userId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO reports (student_id, file_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $userId, $savePath);
                    $stmt->execute();
                    $stmt->close();
                }
                $reportMsg = "Final report uploaded successfully.";
            }
        }
    }
}
?>
<?php include('header.php'); ?>
<div class="dashboard">
    <h2>Student Dashboard</h2>
    <!-- Notifications and reminders -->
    <div class="notifications">
        <?php if ($assigned): ?>
            <?php
            // Weekly logbook reminder
            $resLogs = $conn->query("SELECT COUNT(*) AS cnt FROM logbook WHERE student_id = $userId");
            $logCount = $resLogs->fetch_assoc()['cnt'];
            $resLogs->free();
            if ($logCount < 8):
                $nextWeek = $logCount + 1; ?>
                <div class="notice">Reminder: Week <?php echo $nextWeek; ?> logbook is due this week.</div>
            <?php else: ?>
                <div class="notice">All weekly logbooks have been submitted.</div>
            <?php endif; ?>
            <?php
            // Final report reminder
            $resRep = $conn->query("SELECT id FROM reports WHERE student_id = $userId");
            if ($resRep->num_rows === 0): ?>
                <div class="notice">Reminder: Final report is due at the end of the attachment period.</div>
            <?php endif; $resRep->free(); ?>
        <?php else: ?>
            <div class="notice">You have not been assigned to an organization yet. Please wait for your coordinator to assign you.</div>
        <?php endif; ?>
    </div>
    <!-- Placement info -->
    <?php if ($assigned): ?>
        <div class="placement-info">
            <p><strong>Organization:</strong> <?php echo htmlspecialchars("$orgName ($orgIndustry)"); ?></p>
            <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($supName); ?></p>
        </div>
    <?php endif; ?>
    <!-- Weekly Logbook Submission -->
    <section class="submission-section">
        <h3>Weekly Logbook Submission</h3>
        <?php if ($logMsg): ?>
            <div class="message"><?php echo htmlspecialchars($logMsg); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="submit_logbook" value="1">
            <div class="form-group">
                <label for="week">Week Number:</label>
                <input type="number" name="week" id="week" min="1" max="52">
            </div>
            <div class="form-group">
                <label for="log_content">Work Done (Log):</label>
                <textarea name="log_content" id="log_content" rows="4"></textarea>
            </div>
            <button type="submit" class="btn">Submit Logbook</button>
        </form>
    </section>
    <!-- Final Report Submission -->
    <section class="submission-section">
        <h3>Final Report Submission</h3>
        <?php if ($reportMsg): ?>
            <div class="message"><?php echo htmlspecialchars($reportMsg); ?></div>
        <?php endif; ?>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="submit_report" value="1">
            <div class="form-group">
                <label for="report_file">Upload Report (PDF/DOC):</label>
                <input type="file" name="report_file" id="report_file" accept=".pdf,.doc,.docx">
            </div>
            <button type="submit" class="btn">Submit Final Report</button>
        </form>
    </section>
</div>
<?php include('footer.php'); ?>
