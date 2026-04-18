<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['Email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: ../pages/signin.html?error=empty_fields");
        exit();
    }

    require_once 'config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        header("Location: ../pages/signin.html?error=db_error");
        exit();
    }

    $stmt = $conn->prepare("SELECT firstName, password, is_verified, role FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password']) || $password === $user['password']) {
            // Block login if email not verified yet
            if (!$user['is_verified']) {
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_name']  = $user['firstName'];
                header("Location: ../pages/verify.html?error=not_verified");
                exit();
            }

            $_SESSION['user_id']   = $email;
            $_SESSION['firstName'] = $user['firstName'];
            $_SESSION['role']      = $user['role'];

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
