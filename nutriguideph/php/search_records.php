<?php
require_once 'auth.php';
secureSessionStart();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isStaff()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$conn = getDB();

$search  = trim($_GET['search'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

if ($search !== '') {
    $like = "%$search%";
    $countStmt = $conn->prepare("SELECT COUNT(*) as c FROM stdRecord WHERE std_first_name LIKE ? OR std_last_name LIKE ? OR classification LIKE ? OR guardian_name LIKE ?");
    $countStmt->bind_param("ssss", $like, $like, $like, $like);
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['c'];
    $countStmt->close();

    $stmt = $conn->prepare("SELECT * FROM stdRecord WHERE std_first_name LIKE ? OR std_last_name LIKE ? OR classification LIKE ? OR guardian_name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssssii", $like, $like, $like, $like, $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $totalRecords = $conn->query("SELECT COUNT(*) as c FROM stdRecord")->fetch_assoc()['c'];
    $result = $conn->query("SELECT * FROM stdRecord ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
}

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

$totalPages = max(1, ceil($totalRecords / $perPage));

echo json_encode([
    'records'      => $records,
    'page'         => $page,
    'totalPages'   => $totalPages,
    'totalRecords' => $totalRecords,
    'search'       => $search
]);

if (isset($stmt)) $stmt->close();
$conn->close();
?>
