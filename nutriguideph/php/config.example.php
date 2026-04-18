<?php
// ── COPY THIS FILE AND RENAME TO config.php ─────────────────
// cp config.example.php config.php
// Then fill in your actual credentials below.

// ── Database ────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'myDB');

// ── SMTP (Gmail) ────────────────────────────────────────────
// 1. Enable 2-Step Verification on your Google Account
// 2. Go to Security > App Passwords > generate one for "Mail"
// 3. Paste the app password below
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password-here');
define('SMTP_FROM', 'your-email@gmail.com');
define('SMTP_NAME', 'NutriPh Guide');
?>
