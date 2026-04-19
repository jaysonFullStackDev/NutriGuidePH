<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->select_db(DB_NAME);
$conn->set_charset('utf8mb4');

// ── accounts ────────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS accounts (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        firstName         VARCHAR(50)  NOT NULL,
        lastName          VARCHAR(50)  NOT NULL,
        role              VARCHAR(30)  NOT NULL,
        email             VARCHAR(100) NOT NULL UNIQUE,
        password          VARCHAR(255) NOT NULL,
        is_verified       TINYINT(1)   DEFAULT 0,
        verification_code VARCHAR(6)   DEFAULT NULL,
        code_expires      DATETIME     DEFAULT NULL,
        consent_agreed    TINYINT(1)   DEFAULT 0,
        consent_date      DATETIME     DEFAULT NULL,
        status            VARCHAR(20)  DEFAULT 'pending',
        dark_mode         TINYINT(1)   DEFAULT 0,
        lang              VARCHAR(5)   DEFAULT 'en',
        created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )
");

// ── stdRecord ───────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS stdRecord (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        std_last_name    VARCHAR(50),
        std_first_name   VARCHAR(50),
        std_mid_initial  VARCHAR(5),
        gender           VARCHAR(10),
        grade_level      VARCHAR(20)  DEFAULT NULL,
        section          VARCHAR(50)  DEFAULT NULL,
        school_year      VARCHAR(20)  DEFAULT NULL,
        photo            VARCHAR(255) DEFAULT NULL,
        height           DECIMAL(6,2),
        height_unit      VARCHAR(10),
        weight           DECIMAL(6,2),
        weight_unit      VARCHAR(5),
        classification   VARCHAR(30),
        bmi              DECIMAL(5,1),
        guardian_name    VARCHAR(100),
        guardian_number  VARCHAR(20),
        guardian_email   VARCHAR(100),
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── feedingRecord ───────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS feedingRecord (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        student_id      INT NOT NULL,
        feed_date       DATE NOT NULL,
        meal_type       VARCHAR(30),
        notes           TEXT,
        recorded_by     VARCHAR(100),
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES stdRecord(id) ON DELETE CASCADE
    )
");

// ── audit_log ───────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS audit_log (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_email  VARCHAR(100),
        user_name   VARCHAR(100),
        action      VARCHAR(50) NOT NULL,
        target_type VARCHAR(30),
        target_id   INT DEFAULT NULL,
        details     TEXT,
        ip_address  VARCHAR(45),
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── notifications ───────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_email  VARCHAR(100),
        title       VARCHAR(100) NOT NULL,
        message     TEXT,
        type        VARCHAR(20) DEFAULT 'info',
        is_read     TINYINT(1) DEFAULT 0,
        link        VARCHAR(255) DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── data_corrections ────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS data_corrections (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        parent_email    VARCHAR(100),
        student_id      INT,
        field_name      VARCHAR(50),
        current_value   VARCHAR(255),
        requested_value VARCHAR(255),
        reason          TEXT,
        status          VARCHAR(20) DEFAULT 'pending',
        reviewed_by     VARCHAR(100) DEFAULT NULL,
        reviewed_at     DATETIME DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── meal_plans ──────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS meal_plans (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        plan_date   DATE NOT NULL,
        meal_type   VARCHAR(30) NOT NULL,
        menu        TEXT NOT NULL,
        calories    INT DEFAULT NULL,
        protein_g   DECIMAL(5,1) DEFAULT NULL,
        notes       TEXT,
        created_by  VARCHAR(100),
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── scheduled_reports ───────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS scheduled_reports (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        report_type     VARCHAR(50) NOT NULL,
        frequency       VARCHAR(20) NOT NULL,
        recipient_email VARCHAR(100) NOT NULL,
        last_sent       DATETIME DEFAULT NULL,
        is_active       TINYINT(1) DEFAULT 1,
        created_by      VARCHAR(100),
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// ── Migrations for existing tables ──────────────────────────
$mig = [
    ['accounts', 'is_verified', "ALTER TABLE accounts ADD COLUMN is_verified TINYINT(1) DEFAULT 0"],
    ['accounts', 'verification_code', "ALTER TABLE accounts ADD COLUMN verification_code VARCHAR(6) DEFAULT NULL"],
    ['accounts', 'code_expires', "ALTER TABLE accounts ADD COLUMN code_expires DATETIME DEFAULT NULL"],
    ['accounts', 'consent_agreed', "ALTER TABLE accounts ADD COLUMN consent_agreed TINYINT(1) DEFAULT 0"],
    ['accounts', 'consent_date', "ALTER TABLE accounts ADD COLUMN consent_date DATETIME DEFAULT NULL"],
    ['accounts', 'status', "ALTER TABLE accounts ADD COLUMN status VARCHAR(20) DEFAULT 'pending'"],
    ['accounts', 'dark_mode', "ALTER TABLE accounts ADD COLUMN dark_mode TINYINT(1) DEFAULT 0"],
    ['accounts', 'lang', "ALTER TABLE accounts ADD COLUMN lang VARCHAR(5) DEFAULT 'en'"],
    ['stdRecord', 'grade_level', "ALTER TABLE stdRecord ADD COLUMN grade_level VARCHAR(20) DEFAULT NULL AFTER gender"],
    ['stdRecord', 'section', "ALTER TABLE stdRecord ADD COLUMN section VARCHAR(50) DEFAULT NULL AFTER grade_level"],
    ['stdRecord', 'school_year', "ALTER TABLE stdRecord ADD COLUMN school_year VARCHAR(20) DEFAULT NULL AFTER section"],
    ['stdRecord', 'photo', "ALTER TABLE stdRecord ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER school_year"],
];
foreach ($mig as $m) {
    $check = $conn->query("SHOW COLUMNS FROM {$m[0]} LIKE '{$m[1]}'");
    if ($check->num_rows === 0) $conn->query($m[2]);
}

// ── Create uploads directory ────────────────────────────────
$uploadDir = __DIR__ . '/../uploads/photos';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

echo "<h2>Database setup complete!</h2>";
echo "<p>✔ <b>" . DB_NAME . "</b> — accounts, stdRecord, feedingRecord, audit_log, notifications, data_corrections, meal_plans, scheduled_reports</p>";

// Default Super Admin
$count = $conn->query("SELECT COUNT(*) as c FROM accounts")->fetch_assoc()['c'];
if ($count == 0) {
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO accounts (firstName, lastName, role, email, password, is_verified, status) VALUES ('System', 'Admin', 'Super Admin', 'admin@nutriph.com', '$defaultPass', 1, 'active')");
    echo "<p>✔ Default Super Admin created (admin@nutriph.com). Change password immediately.</p>";
}

echo "<br><a href='../pages/signin.php'>Go to Sign In</a>";
$conn->close();
?>
