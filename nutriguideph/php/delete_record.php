<?php
require_once 'auth.php';
secureSessionStart();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id === 0) { echo json_encode(['error' => 'Invalid record ID']); exit(); }

$conn = getDB();

// Get record details before deleting for audit
$info = $conn->query("SELECT std_first_name, std_last_name FROM stdRecord WHERE id=$id");
$name = $info->num_rows ? $info->fetch_assoc() : null;
$detail = $name ? $name['std_first_name'] . ' ' . $name['std_last_name'] : 'Unknown';

$stmt = $conn->prepare("DELETE FROM stdRecord WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    auditLog('delete_record', 'stdRecord', $id, $detail);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Record not found']);
}
$stmt->close();
$conn->close();
?>
