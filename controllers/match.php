<?php
// controllers/match.php - Runs the matchmaking algorithm (coordinator action)
require_once('../config.php');
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'coordinator' && $_SESSION['role'] !== 'admin')) {
    // Only coordinators (or admins) are allowed to run matching
    header("Location: ../index.php");
    exit();
}

// Retrieve all unassigned students and their preferences
$students = [];
$sql = "SELECT u.id AS student_id, u.name, sp.preference 
        FROM users u 
        LEFT JOIN student_preferences sp ON u.id = sp.user_id
        LEFT JOIN attachments a ON u.id = a.student_id
        WHERE u.role = 'student' AND a.student_id IS NULL";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$result->free();

// Retrieve all organizations with available capacity
$orgs = [];
$res2 = $conn->query("SELECT o.id, o.industry, o.capacity FROM organization o WHERE o.capacity > 0");
while ($org = $res2->fetch_assoc()) {
    $orgs[] = $org;
}
$res2->free();

// Retrieve all supervisor IDs (to assign one per student in a round-robin fashion)
$supervisors = [];
$res3 = $conn->query("SELECT id FROM users WHERE role = 'supervisor'");
while ($sup = $res3->fetch_assoc()) {
    $supervisors[] = $sup['id'];
}
$res3->free();
$supCount = count($supervisors);
$assignedCount = 0;
$supIndex = 0;

// Match each unassigned student to an organization based on preference
foreach ($students as $stu) {
    $pref = $stu['preference'] ?? '';
    $assignedOrgId = null;
    // Try to find an organization matching the student's preference
    foreach ($orgs as &$org) {
        if ($org['capacity'] > 0 && $pref !== '' && $org['industry'] === $pref) {
            $assignedOrgId = $org['id'];
            $org['capacity']--;  // reserve a slot
            break;
        }
    }
    // If no matching org found, assign to any org with capacity (fallback)
    if ($assignedOrgId === null) {
        foreach ($orgs as &$org) {
            if ($org['capacity'] > 0) {
                $assignedOrgId = $org['id'];
                $org['capacity']--;
                break;
            }
        }
    }
    // Assign a supervisor from the list (cycling through)
    $assignedSupId = null;
    if ($supCount > 0) {
        $assignedSupId = $supervisors[$supIndex % $supCount];
        $supIndex++;
    }
    // Create an attachment record if a slot was found
    if ($assignedOrgId !== null) {
        $stId = $stu['student_id'];
        $stmt = $conn->prepare("INSERT INTO attachments (student_id, org_id, supervisor_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $stId, $assignedOrgId, $assignedSupId);
        $stmt->execute();
        $stmt->close();
        // Update the organization's remaining capacity in the database
        $conn->query("UPDATE organization SET capacity = capacity - 1 WHERE id = $assignedOrgId");
        $assignedCount++;
    }
}

// Store a success message and redirect back to the coordinator dashboard
$_SESSION['message'] = "Matchmaking complete. $assignedCount student(s) assigned.";
header("Location: ../views/coordinator_dashboard.php");
exit();
?>
