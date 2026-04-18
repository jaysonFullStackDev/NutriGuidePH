<?php
require_once 'auth.php';
secureSessionStart();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }

$conn = getDB();
$stmt = $conn->prepare("SELECT firstName, lastName, email, role, consent_agreed, consent_date, created_at FROM accounts WHERE email=?");
$email = $_SESSION['user_id'];
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->num_rows === 1 ? ['success' => true, 'user' => $result->fetch_assoc()] : ['error' => 'User not found']);
$stmt->close();
$conn->close();
?>
