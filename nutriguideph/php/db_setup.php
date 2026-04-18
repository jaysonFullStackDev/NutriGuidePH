<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
        created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    )
");

// Add columns if table already existed without them
$existing = $conn->query("SHOW COLUMNS FROM accounts LIKE 'is_verified'");
if ($existing->num_rows === 0) {
    $conn->query("ALTER TABLE accounts ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE accounts ADD COLUMN verification_code VARCHAR(6) DEFAULT NULL");
    $conn->query("ALTER TABLE accounts ADD COLUMN code_expires DATETIME DEFAULT NULL");
}
$consent_col = $conn->query("SHOW COLUMNS FROM accounts LIKE 'consent_agreed'");
if ($consent_col->num_rows === 0) {
    $conn->query("ALTER TABLE accounts ADD COLUMN consent_agreed TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE accounts ADD COLUMN consent_date DATETIME DEFAULT NULL");
}

// stdRecord table
$conn->query("
    CREATE TABLE IF NOT EXISTS stdRecord (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        std_last_name    VARCHAR(50),
        std_first_name   VARCHAR(50),
        std_mid_initial  VARCHAR(5),
        gender           VARCHAR(10),
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

echo "<h2>Database setup complete!</h2>";
echo "<p>✔ Database <b>myDB</b> ready</p>";
echo "<p>✔ Table <b>accounts</b> ready (with email verification columns)</p>";
echo "<p>✔ Table <b>stdRecord</b> ready</p>";
echo "<p>✔ Table <b>feedingRecord</b> ready</p>";
echo "<br><a href='../pages/signin.html'>Go to Sign In</a>";

$conn->close();
?>
