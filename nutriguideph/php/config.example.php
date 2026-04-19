<?php
// ── COPY THIS FILE TO config.php AND FILL IN YOUR CREDENTIALS ──
// cp config.example.php config.php

// ── Database ────────────────────────────────────────────────
// XAMPP: host=localhost, user=root, pass=(empty)
// Production: use a dedicated MySQL user with a STRONG password
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

// ── PRODUCTION CHECKLIST ────────────────────────────────────
// 1. Use a strong DB password (not root with empty password)
// 2. Deploy with HTTPS (free SSL from hosting or Cloudflare)
// 3. Run db_setup.php once, then DELETE or restrict access to it
// 4. Change the default admin password (admin@nutriph.com / admin123)
// 5. Set proper file permissions (755 for dirs, 644 for files)
// 6. Restrict access to /logs/ directory via .htaccess
?>
