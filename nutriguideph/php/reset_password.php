<?php
require_once 'auth.php';
secureSessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/reset_password.php");
    exit();
}

verifyCsrf();

if (!isset($_SESSION['reset_email'])) {
    header("Location: ../pages/reset_password.php?error=no_session");
    exit();
}

$code     = trim($_POST['code'] ?? '');
$newPass  = $_POST['new_password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$email    = $_SESSION['reset_email'];

if (strlen($newPass) < 6) {
    header("Location: ../pages/reset_password.php?error=weak");
    exit();
}

if ($newPass !== $confirm) {
    header("Location: ../pages/reset_password.php?error=mismatch");
    exit();
}

$conn = getDB();

$stmt = $conn->prepare("SELECT verification_code, code_expires FROM accounts WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: ../pages/reset_password.php?error=no_session");
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

if (new DateTime() > new DateTime($row['code_expires'])) {
    header("Location: ../pages/reset_password.php?error=code_expired");
    exit();
}

if ($code !== $row['verification_code']) {
    header("Location: ../pages/reset_password.php?error=wrong_code");
    exit();
}

$hashed = password_hash($newPass, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE accounts SET password = ?, verification_code = NULL, code_expires = NULL WHERE email = ?");
$upd->bind_param("ss", $hashed, $email);
$upd->execute();
$upd->close();
$conn->close();

unset($_SESSION['reset_email']);

header("Location: ../pages/signin.php?success=password_reset");
exit();
?>
