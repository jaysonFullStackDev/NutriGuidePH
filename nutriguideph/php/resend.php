<?php
session_start();

if (!isset($_SESSION['verify_email'])) {
    header("Location: ../pages/signin.html");
    exit();
}

$email = $_SESSION['verify_email'];
$name  = $_SESSION['verify_name'] ?? 'User';

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    header("Location: ../pages/verify.html?error=db_error");
    exit();
}

$code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$stmt = $conn->prepare("UPDATE accounts SET verification_code = ?, code_expires = ? WHERE email = ? AND is_verified = 0");
$stmt->bind_param("sss", $code, $expires, $email);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    header("Location: ../pages/verify.html?error=not_found");
    exit();
}

$stmt->close();
$conn->close();

require_once 'mailer.php';
$sent = sendVerificationEmail($email, $name, $code);

if ($sent) {
    header("Location: ../pages/verify.html?success=resent");
} else {
    header("Location: ../pages/verify.html?error=email_failed");
}
exit();
?>
