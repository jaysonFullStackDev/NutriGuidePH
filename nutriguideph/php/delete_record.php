<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'auth.php';
if (!isAdmin()) {
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id === 0) {
    echo json_encode(['error' => 'Invalid record ID']);
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database error']);
    exit();
}

mysqli_report(MYSQLI_REPORT_OFF);

$stmt = $conn->prepare("DELETE FROM stdRecord WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Record not found or already deleted']);
}

$stmt->close();
$conn->close();
?>
