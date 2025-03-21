<?php
// views/coordinator_dashboard.php - Coordinator dashboard (student list & matching control)
require_once('../config.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: ../index.php");
    exit();
}

// Fetch all students with their preference and current assignment (if any)
$sql = "SELECT u.id, u.name, sp.preference, org_user.name AS org_name
        FROM users u
        LEFT JOIN student_preferences sp ON u.id = sp.user_id
        LEFT JOIN attachments a ON u.id = a.student_id
        LEFT JOIN organization org ON a.org_id = org.id
        LEFT JOIN users org_user ON org.user_id = org_user.id
        WHERE u.role = 'student'";
$result = $conn->query($sql);
$students = [];
$unassignedCount = 0;
while ($row = $result->fetch_assoc()) {
    if (!$row['org_name']) {
        $row['org_name'] = "Not assigned";
        $unassignedCount++;
    }
    $students[] = $row;
}
$result->free();
?>
<?php include('header.php'); ?>
<div class="dashboard">
    <h2>Coordinator Dashboard</h2>
    <div class="notifications">
        <?php if ($unassignedCount > 0): ?>
            <div class="notice">There are <?php echo $unassignedCount; ?> student(s) not yet assigned to an organization.</div>
        <?php else: ?>
            <div class="notice">All students have been assigned to organizations.</div>
        <?php endif; ?>
    </div>
    <!-- Button to run the matching algorithm -->
    <form action="../controllers/match.php" method="post" class="match-form">
        <button type="submit" class="btn">Run Matching Algorithm</button>
    </form>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <h3>Students Overview</h3>
    <div class="table-filter">
        <label for="studentSearch">Search:</label>
        <input type="text" id="studentSearch" placeholder="Search students...">
    </div>
    <table id="studentTable">
        <thead>
        <tr>
            <th>Name</th>
            <th>Preference</th>
            <th>Organization</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $stu): ?>
            <tr>
                <td><?php echo htmlspecialchars($stu['name']); ?></td>
                <td><?php echo htmlspecialchars($stu['preference'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($stu['org_name']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include('footer.php'); ?>
