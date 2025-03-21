<?php
// views/organization_dashboard.php - Organization dashboard (assigned students and capacity status)
require_once('../config.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organization') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get this organization's info and current assignments
$orgRes = $conn->query("SELECT id, capacity FROM organization WHERE user_id = $userId");
$orgData = $orgRes->fetch_assoc();
$orgId = $orgData['id'];
$remainingSlots = (int)$orgData['capacity'];
$orgRes->free();

// Count students currently assigned to this organization
$res2 = $conn->query("SELECT COUNT(*) AS count FROM attachments WHERE org_id = $orgId");
$assignedCount = $res2->fetch_assoc()['count'];
$res2->free();
$originalCapacity = $assignedCount + $remainingSlots;

// Fetch assigned students' details (name and email)
$studentRes = $conn->query("SELECT u.name, u.email FROM attachments a JOIN users u ON a.student_id = u.id WHERE a.org_id = $orgId");
$students = [];
while ($row = $studentRes->fetch_assoc()) {
    $students[] = $row;
}
$studentRes->free();
?>
<?php include('header.php'); ?>
<div class="dashboard">
    <h2>Organization Dashboard</h2>
    <div class="org-info">
        <p><strong>Total Capacity:</strong> <?php echo $originalCapacity; ?> student(s)</p>
        <p><strong>Currently Hosting:</strong> <?php echo $assignedCount; ?> student(s)</p>
        <p><strong>Remaining Slots:</strong> <?php echo $remainingSlots; ?> student(s)</p>
    </div>
    <h3>Assigned Students</h3>
    <?php if (empty($students)): ?>
        <p>No students have been assigned to your organization yet.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Student Name</th>
                <th>Email</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $stu): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stu['name']); ?></td>
                    <td><?php echo htmlspecialchars($stu['email']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include('footer.php'); ?>
