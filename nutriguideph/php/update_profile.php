<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database error']);
    exit();
}

mysqli_report(MYSQLI_REPORT_OFF);

$action = $_POST['action'] ?? '';
$email  = $_SESSION['user_id'];

if ($action === 'update_info') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $newEmail  = trim($_POST['email'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($newEmail)) {
        echo json_encode(['error' => 'All fields are required']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE accounts SET firstName=?, lastName=?, email=? WHERE email=?");
    $stmt->bind_param("ssss", $firstName, $lastName, $newEmail, $email);

    if ($stmt->execute()) {
        $_SESSION['firstName'] = $firstName;
        $_SESSION['user_id']   = $newEmail;
        echo json_encode(['success' => true, 'firstName' => $firstName]);
    } else {
        if ($conn->errno == 1062) {
            echo json_encode(['error' => 'That email is already in use']);
        } else {
            echo json_encode(['error' => 'Update failed']);
        }
    }
    $stmt->close();

} elseif ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($newPass) || empty($confirm)) {
        echo json_encode(['error' => 'All password fields are required']);
        exit();
    }

    if ($newPass !== $confirm) {
        echo json_encode(['error' => 'New passwords do not match']);
        exit();
    }

    if (strlen($newPass) < 6) {
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit();
    }

    $stmt = $conn->prepare("SELECT password FROM accounts WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['error' => 'Account not found']);
        exit();
    }

    // Support both hashed and plain text
    if (!password_verify($current, $user['password']) && $current !== $user['password']) {
        echo json_encode(['error' => 'Current password is incorrect']);
        exit();
    }

    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE accounts SET password=? WHERE email=?");
    $stmt->bind_param("ss", $hashed, $email);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Password update failed']);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>
