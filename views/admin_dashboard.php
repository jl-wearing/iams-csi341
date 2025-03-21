<?php
// views/admin_dashboard.php - Admin dashboard (placement and performance reports)
require_once('../config.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Query summary of placements by organization
$summaryRes = $conn->query(
    "SELECT org_user.name AS org_name,
            COUNT(DISTINCT a.student_id) AS total_students,
            ROUND(AVG(e.rating), 1) AS avg_rating
     FROM organization org
     JOIN users org_user ON org.user_id = org_user.id
     LEFT JOIN attachments a ON org.id = a.org_id
     LEFT JOIN evaluation e ON a.student_id = e.student_id
     GROUP BY org.id, org_user.name"
);
$orgSummary = [];
while ($row = $summaryRes->fetch_assoc()) {
    $orgSummary[] = $row;
}
$summaryRes->free();

// Query detailed placements for each student
$detailRes = $conn->query(
    "SELECT u.name AS student_name,
            IF(org_user.name IS NULL, 'Not assigned', org_user.name) AS org_name,
            e.rating
     FROM users u
     LEFT JOIN attachments a ON u.id = a.student_id
     LEFT JOIN organization org ON a.org_id = org.id
     LEFT JOIN users org_user ON org.user_id = org_user.id
     LEFT JOIN evaluation e ON u.id = e.student_id
     WHERE u.role = 'student'
     ORDER BY u.name"
);
$placements = [];
while ($row = $detailRes->fetch_assoc()) {
    $placements[] = $row;
}
$detailRes->free();
?>
<?php include('header.php'); ?>
<div class="dashboard">
    <h2>Admin Dashboard - Reports</h2>
    <section>
        <h3>Placement Summary by Organization</h3>
        <table>
            <thead>
            <tr>
                <th>Organization</th>
                <th>Students Placed</th>
                <th>Avg. Rating</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orgSummary as $org):
                $avg = $org['avg_rating']; ?>
                <tr>
                    <td><?php echo htmlspecialchars($org['org_name']); ?></td>
                    <td><?php echo $org['total_students']; ?></td>
                    <td>
                        <?php
                        if ($org['total_students'] == 0 || $avg === NULL) {
                            echo "N/A";
                        } else {
                            echo htmlspecialchars($avg);
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section>
        <h3>Detailed Student Placement & Performance</h3>
        <table>
            <thead>
            <tr>
                <th>Student Name</th>
                <th>Organization</th>
                <th>Rating</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($placements as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['org_name']); ?></td>
                    <td><?php echo ($p['rating'] !== null ? htmlspecialchars($p['rating']) : "N/A"); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<?php include('footer.php'); ?>
