<?php
require_once 'auth.php';
secureSessionStart();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }

$fn = trim($_GET['fn'] ?? '');
$ln = trim($_GET['ln'] ?? '');
if (empty($fn) || empty($ln)) { echo json_encode(['error' => 'Missing parameters']); exit(); }

$conn = getDB();
$stmt = $conn->prepare("SELECT * FROM stdRecord WHERE std_first_name = ? AND std_last_name = ? ORDER BY created_at DESC");
$stmt->bind_param("ss", $fn, $ln);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) $records[] = $row;

echo json_encode(['records' => $records]);
$stmt->close();
$conn->close();
?>
