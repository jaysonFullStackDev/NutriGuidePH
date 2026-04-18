<?php
require_once 'auth.php';
secureSessionStart();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['Email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: ../pages/signin.html?error=empty_fields");
        exit();
    }

    // Rate limit: 5 attempts per 5 minutes
    if (!checkRateLimit('login_' . $email, 5, 300)) {
        header("Location: ../pages/signin.html?error=rate_limited");
        exit();
    }

    $conn = getDB();

    $stmt = $conn->prepare("SELECT firstName, password, is_verified, role, status FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password']) || $password === $user['password']) {
            // Block unverified
            if (!$user['is_verified']) {
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_name']  = $user['firstName'];
                header("Location: ../pages/verify.html?error=not_verified");
                exit();
            }

            // Block pending staff accounts
            if (($user['status'] ?? 'pending') === 'pending' && $user['role'] !== 'Parent/Guardian') {
                header("Location: ../pages/signin.html?error=pending_approval");
                exit();
            }

            // Block deactivated accounts
            if (($user['status'] ?? '') === 'deactivated') {
                header("Location: ../pages/signin.html?error=deactivated");
                exit();
            }

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']   = $email;
            $_SESSION['firstName'] = $user['firstName'];
            $_SESSION['role']      = $user['role'];

            auditLog('login', 'account', null, $user['role']);

            unset($_SESSION['rate_login_' . $email]);

            if ($user['role'] === 'Parent/Guardian') {
                header("Location: parent_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            header("Location: ../pages/signin.html?error=wrong_password");
            exit();
        }
    } else {
        header("Location: ../pages/signin.html?error=no_account");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
