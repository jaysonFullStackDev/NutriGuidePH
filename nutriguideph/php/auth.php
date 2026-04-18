<?php
// Include this at the top of every protected page after session_start()
// Usage: require_once 'auth.php'; checkAccess(['Admin', 'Super Admin', 'Employee']);

function checkAccess($allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../pages/signin.html");
        exit();
    }

    if (!empty($allowedRoles) && !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
        // Redirect parents to their own dashboard
        if (($_SESSION['role'] ?? '') === 'Parent/Guardian') {
            header("Location: parent_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}

function isRole($role) {
    return ($_SESSION['role'] ?? '') === $role;
}

function isStaff() {
    return in_array($_SESSION['role'] ?? '', ['Employee', 'Admin', 'Super Admin']);
}

function isAdmin() {
    return in_array($_SESSION['role'] ?? '', ['Admin', 'Super Admin']);
}

function isSuperAdmin() {
    return ($_SESSION['role'] ?? '') === 'Super Admin';
}

function isParent() {
    return ($_SESSION['role'] ?? '') === 'Parent/Guardian';
}
?>
