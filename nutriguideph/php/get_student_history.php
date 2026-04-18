<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$fn = trim($_GET['fn'] ?? '');
$ln = trim($_GET['ln'] ?? '');

if (empty($fn) || empty($ln)) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database error']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM stdRecord WHERE std_first_name = ? AND std_last_name = ? ORDER BY created_at DESC");
$stmt->bind_param("ss", $fn, $ln);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['records' => $records]);
?>
