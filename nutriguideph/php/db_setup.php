<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->select_db(DB_NAME);

// accounts table
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
        created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )
");

// Migration: add missing columns
$migrations = [
    'is_verified'       => "ALTER TABLE accounts ADD COLUMN is_verified TINYINT(1) DEFAULT 0",
    'verification_code' => "ALTER TABLE accounts ADD COLUMN verification_code VARCHAR(6) DEFAULT NULL",
    'code_expires'      => "ALTER TABLE accounts ADD COLUMN code_expires DATETIME DEFAULT NULL",
    'consent_agreed'    => "ALTER TABLE accounts ADD COLUMN consent_agreed TINYINT(1) DEFAULT 0",
    'consent_date'      => "ALTER TABLE accounts ADD COLUMN consent_date DATETIME DEFAULT NULL",
    'status'            => "ALTER TABLE accounts ADD COLUMN status VARCHAR(20) DEFAULT 'pending'",
];
foreach ($migrations as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM accounts LIKE '$col'");
    if ($check->num_rows === 0) $conn->query($sql);
}

// stdRecord table
$conn->query("
    CREATE TABLE IF NOT EXISTS stdRecord (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        std_last_name    VARCHAR(50),
        std_first_name   VARCHAR(50),
        std_mid_initial  VARCHAR(5),
        gender           VARCHAR(10),
        grade_level      VARCHAR(20)  DEFAULT NULL,
        section          VARCHAR(50)  DEFAULT NULL,
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

// Migration: add grade/section if missing
$gl = $conn->query("SHOW COLUMNS FROM stdRecord LIKE 'grade_level'");
if ($gl->num_rows === 0) {
    $conn->query("ALTER TABLE stdRecord ADD COLUMN grade_level VARCHAR(20) DEFAULT NULL AFTER gender");
    $conn->query("ALTER TABLE stdRecord ADD COLUMN section VARCHAR(50) DEFAULT NULL AFTER grade_level");
}

// feedingRecord table
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

// audit_log table
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

echo "<h2>Database setup complete!</h2>";
echo "<p>✔ Database <b>" . DB_NAME . "</b></p>";
echo "<p>✔ Table <b>accounts</b></p>";
echo "<p>✔ Table <b>stdRecord</b> (with grade_level, section)</p>";
echo "<p>✔ Table <b>feedingRecord</b></p>";
echo "<p>✔ Table <b>audit_log</b></p>";

// Default Super Admin
$count = $conn->query("SELECT COUNT(*) as c FROM accounts")->fetch_assoc()['c'];
if ($count == 0) {
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO accounts (firstName, lastName, role, email, password, is_verified, status) VALUES ('System', 'Admin', 'Super Admin', 'admin@nutriph.com', '$defaultPass', 1, 'active')");
    echo "<br><div style='background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:16px;margin:16px 0;'>";
    echo "<p style='margin:0;font-weight:700;color:#856404;'>Default Super Admin created.</p>";
    echo "<p style='margin:4px 0 0;color:#856404;'>Email: <b>admin@nutriph.com</b></p>";
    echo "<p style='margin:4px 0 0;color:#856404;font-size:0.85rem;'>Default password has been set. Change it immediately after first login.</p></div>";
}

echo "<br><a href='../pages/signin.php'>Go to Sign In</a>";
$conn->close();
?>
