<?php
session_start();

$firstName        = trim($_POST['firstName']);
$lastName         = trim($_POST['lastName']);
$role             = $_POST['role'];
$email            = trim($_POST['Email']);
$password         = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$consent          = isset($_POST['consent_agreed']) ? 1 : 0;

// Determine which signup page to redirect back to
$redirect_page = ($role === 'Parent/Guardian') ? 'parent_signup.html' : 'Signup.html';

if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    header("Location: ../pages/$redirect_page?error=empty_fields");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: ../pages/$redirect_page?error=password_mismatch");
    exit();
}

// Parents must agree to consent
if ($role === 'Parent/Guardian' && !$consent) {
    header("Location: ../pages/$redirect_page?error=consent_required");
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    header("Location: ../pages/$redirect_page?error=db_error");
    exit();
}

// Disable exception mode so we can handle duplicate errors gracefully
mysqli_report(MYSQLI_REPORT_OFF);

// Generate a 6-digit verification code
$code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
$consent_date = $consent ? date('Y-m-d H:i:s') : null;

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO accounts (firstName, lastName, role, email, password, verification_code, code_expires, is_verified, consent_agreed, consent_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
");
$stmt->bind_param("sssssssis", $firstName, $lastName, $role, $email, $hashed_password, $code, $expires, $consent, $consent_date);

if ($stmt->execute()) {
    require_once 'mailer.php';
    $sent = sendVerificationEmail($email, $firstName, $code);

    $_SESSION['verify_email'] = $email;
    $_SESSION['verify_name']  = $firstName;

    if ($sent) {
        header("Location: ../pages/verify.html");
    } else {
        header("Location: ../pages/verify.html?error=email_failed");
    }
    exit();
} else {
    if ($conn->errno == 1062) {
        header("Location: ../pages/$redirect_page?error=email_exists");
    } else {
        header("Location: ../pages/$redirect_page?error=db_error");
    }
    exit();
}

$stmt->close();
$conn->close();
?>
