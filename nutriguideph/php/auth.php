<?php
// ── Environment ─────────────────────────────────────────────
// Set to false on localhost/XAMPP, true on production server
define('IS_PRODUCTION', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

// ── Security Headers ────────────────────────────────────────
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (IS_PRODUCTION) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
setSecurityHeaders();

// ── HTTPS Redirect (production only) ────────────────────────
function enforceHttps() {
    if (IS_PRODUCTION) return; // Already HTTPS
    // On production hosting, redirect HTTP to HTTPS
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'http') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit();
    }
}
enforceHttps();

// ── Session Security ────────────────────────────────────────
function secureSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        if (IS_PRODUCTION) {
            ini_set('session.cookie_secure', 1);
        }
        ini_set('session.gc_maxlifetime', 3600); // 1 hour
        session_start();

        // Session timeout: 1 hour of inactivity
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
}

// ── Error Handler ───────────────────────────────────────────
function setupErrorHandler() {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $logDir . '/error_' . date('Y-m') . '.log');
    set_error_handler(function($severity, $message, $file, $line) {
        error_log("[" . date('Y-m-d H:i:s') . "] [$severity] $message in $file:$line");
        return true;
    });
    set_exception_handler(function($e) {
        error_log("[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        http_response_code(500);
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'An unexpected error occurred']);
        } else {
            echo '<h3>Something went wrong</h3><p>Please try again later.</p>';
        }
        exit();
    });
}
setupErrorHandler();

// ── Access Control ──────────────────────────────────────────
function checkAccess($allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../pages/signin.php");
        exit();
    }
    if (!empty($allowedRoles) && !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
        if (($_SESSION['role'] ?? '') === 'Parent/Guardian') {
            header("Location: parent_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}

function isRole($role) { return ($_SESSION['role'] ?? '') === $role; }
function isStaff() { return in_array($_SESSION['role'] ?? '', ['Employee', 'Admin', 'Super Admin']); }
function isAdmin() { return in_array($_SESSION['role'] ?? '', ['Admin', 'Super Admin']); }
function isSuperAdmin() { return ($_SESSION['role'] ?? '') === 'Super Admin'; }
function isParent() { return ($_SESSION['role'] ?? '') === 'Parent/Guardian'; }

// ── CSRF Protection ─────────────────────────────────────────
function generateCsrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrf() . '">';
}

function verifyCsrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid request token. Please refresh the page.']);
        } else {
            header("Location: ../pages/signin.php");
        }
        exit();
    }
}

// ── Rate Limiting ───────────────────────────────────────────
function checkRateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
    $now = time();
    $sessionKey = 'rate_' . $key;
    if (!isset($_SESSION[$sessionKey])) $_SESSION[$sessionKey] = [];
    $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], fn($t) => ($now - $t) < $windowSeconds);
    if (count($_SESSION[$sessionKey]) >= $maxAttempts) return false;
    $_SESSION[$sessionKey][] = $now;
    return true;
}

// ── Input Validation ────────────────────────────────────────
function validateHeight($height, $unit) {
    $h = floatval($height);
    if ($h <= 0) return false;
    return match($unit) {
        'cm' => $h >= 30 && $h <= 300, 'm' => $h >= 0.3 && $h <= 3.0,
        'inch' => $h >= 12 && $h <= 120, 'feet' => $h >= 1 && $h <= 10,
        default => false
    };
}

function validateWeight($weight, $unit) {
    $w = floatval($weight);
    if ($w <= 0) return false;
    return match($unit) {
        'kg' => $w >= 2 && $w <= 300, 'lbs' => $w >= 4 && $w <= 660,
        default => false
    };
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// ── Audit Logging ───────────────────────────────────────────
function auditLog($action, $targetType = null, $targetId = null, $details = null) {
    try {
        require_once __DIR__ . '/config.php';
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) return;
        mysqli_report(MYSQLI_REPORT_OFF);
        $email = $_SESSION['user_id'] ?? 'system';
        $name  = $_SESSION['firstName'] ?? 'System';
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $conn->prepare("INSERT INTO audit_log (user_email, user_name, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $email, $name, $action, $targetType, $targetId, $details, $ip);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// ── DB Helper ───────────────────────────────────────────────
function getDB() {
    require_once __DIR__ . '/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
        http_response_code(503);
        die("Service temporarily unavailable. Please try again later.");
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn->set_charset('utf8mb4');
    return $conn;
}
?>
