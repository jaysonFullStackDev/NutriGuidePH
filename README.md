# 🥗 NutriPh Guide

**Community-Based Nutrition Monitoring System for San Antonio Central School**

NutriPh Guide is a PHP web application that tracks and supports student health through BMI monitoring, nutritional classification, feeding programs, and automated guardian notifications. Built for school staff to digitize the manual process of recording and managing student nutritional data.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [User Roles & Permissions](#user-roles--permissions)
- [Application Pages](#application-pages)
- [Database Schema](#database-schema)
- [CSV Bulk Import](#csv-bulk-import)
- [Security Features](#security-features)
- [Production Deployment](#production-deployment)

---

## Features

- **BMI Tracking** — Record student height/weight, auto-calculate BMI, and classify as Underweight, Normal, Overweight, or Obese
- **Feeding Program** — Automatically enroll underweight students and track daily meal sessions (breakfast, lunch, snack, dinner)
- **Meal Planning** — Weekly meal planner with calorie and protein tracking for feeding program menus
- **Email Alerts** — Guardians receive automated email notifications with dietary recommendations when a student is classified as at-risk
- **Parent Portal** — Parents/guardians can view their child's BMI history, weight trends, and feeding records via a dedicated dashboard
- **Reports & Exports** — Generate printable nutritional status reports, DepEd-formatted SBFP masterlists, OPT summaries, and export records to Excel
- **Bulk CSV Import** — Import multiple student records at once via CSV upload with automatic BMI calculation
- **User Management** — Role-based access control with Super Admin, Admin, Employee, and Parent/Guardian roles
- **Audit Logging** — Track all user actions (record creation, edits, deletions, logins) with timestamps and IP addresses
- **Notifications** — In-app notification system for staff and guardians
- **Data Corrections** — Parents can request corrections to their child's records, reviewed by staff
- **Database Backup** — Super Admins can download a full SQL backup of the database
- **Dark Mode** — User-toggleable dark theme
- **PWA Support** — Installable as a Progressive Web App with service worker and manifest

---

## Tech Stack

| Layer       | Technology                                      |
|-------------|------------------------------------------------|
| Backend     | PHP 8.x                                        |
| Database    | MySQL (via MySQLi)                              |
| Frontend    | Bootstrap 5.3, Font Awesome 6.5, Chart.js 4.4  |
| Animations  | AOS (Animate On Scroll)                         |
| Email       | PHPMailer (SMTP via Gmail)                      |
| Server      | Apache (XAMPP for local development)            |

---

## Project Structure

```
nutriguideph/
├── css/
│   └── index.css              # Global styles
├── images/
│   ├── logo.png               # App logo
│   └── happy.jpg              # Landing page image
├── logs/                      # Error logs (gitignored)
├── pages/
│   ├── signin.php             # Login page
│   ├── parent_signup.php      # Parent/Guardian registration
│   ├── verify.php             # Email verification
│   ├── forgot_password.php    # Password reset request
│   ├── reset_password.php     # Password reset form
│   └── error.php              # Error page
├── php/
│   ├── PHPMailer/             # PHPMailer library
│   ├── auth.php               # Session, CSRF, access control, helpers
│   ├── config.example.php     # Configuration template (copy to config.php)
│   ├── db_setup.php           # Database initialization script
│   ├── dashboard.php          # Staff dashboard with stats & charts
│   ├── parent_dashboard.php   # Parent/Guardian dashboard
│   ├── addrecord.php          # Add student record with BMI preview
│   ├── records.php            # Student records list with history
│   ├── feeding_program.php    # Feeding program management
│   ├── meal_planning.php      # Weekly meal planner
│   ├── reports.php            # Nutritional status reports
│   ├── deped_reports.php      # DepEd SBFP & OPT reports
│   ├── user_management.php    # User account management (Super Admin)
│   ├── audit_log.php          # Activity audit log (Super Admin)
│   ├── notifications.php      # Notification API (JSON)
│   ├── bulk_import.php        # CSV bulk import
│   ├── backup.php             # Database backup download
│   ├── export_records.php     # Export student records to Excel
│   ├── export_feeding.php     # Export feeding records to Excel
│   ├── navbar.php             # Shared navigation component
│   └── ...                    # Other PHP handlers
├── uploads/
│   └── photos/                # Student photos (gitignored)
├── index.php                  # Landing page
├── manifest.json              # PWA manifest
├── sw.js                      # Service worker
└── .gitignore
```

---

## Prerequisites

- **PHP** 8.0 or higher
- **MySQL** 5.7+ or MariaDB 10.3+
- **Apache** with `mod_rewrite` enabled
- **XAMPP** (recommended for local development)
- A **Gmail account** with App Password for SMTP email (optional, for email alerts)

---

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/NutriGuidePH.git
   ```

2. **Move to your web server directory**
   ```bash
   # For XAMPP on Windows:
   copy NutriGuidePH\nutriguideph C:\xampp\htdocs\nutriguideph
   ```

3. **Create the configuration file**
   ```bash
   cd nutriguideph/php
   cp config.example.php config.php
   ```

4. **Edit `config.php`** with your database and SMTP credentials (see [Configuration](#configuration))

5. **Start Apache and MySQL** via XAMPP Control Panel

6. **Run the database setup** by visiting:
   ```
   http://localhost/nutriguideph/php/db_setup.php
   ```
   This creates the database, all tables, and a default Super Admin account.

7. **Sign in** at:
   ```
   http://localhost/nutriguideph/pages/signin.php
   ```
   Default credentials:
   - Email: `admin@nutriph.com`
   - Password: `admin123`

   > ⚠️ **Change the default password immediately after first login.**

---

## Configuration

Edit `nutriguideph/php/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Use a strong password in production
define('DB_NAME', 'myDB');

// SMTP (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '<your-email@gmail.com>');
define('SMTP_PASS', '<your-app-password>');
define('SMTP_FROM', '<your-email@gmail.com>');
define('SMTP_NAME', 'NutriPh Guide');
```

To generate a Gmail App Password:
1. Enable 2-Step Verification on your Google Account
2. Go to **Security → App Passwords**
3. Generate a password for "Mail" and paste it as `SMTP_PASS`

---

## User Roles & Permissions

| Role             | Dashboard | Add Records | Edit/Delete | Reports | Feeding Program | Meal Planning | User Management | Audit Log | Backup |
|------------------|:---------:|:-----------:|:-----------:|:-------:|:---------------:|:-------------:|:---------------:|:---------:|:------:|
| Super Admin      | ✅        | ✅          | ✅          | ✅      | ✅              | ✅            | ✅              | ✅        | ✅     |
| Admin            | ✅        | ✅          | ✅          | ✅      | ✅              | ✅            | ❌              | ❌        | ❌     |
| Employee         | ✅        | ✅          | ❌          | ❌      | ✅              | ✅            | ❌              | ❌        | ❌     |
| Parent/Guardian  | Parent Dashboard only — view child's BMI history, feeding records, and health alerts |

- **Super Admin** can create staff accounts, approve pending registrations, change roles, and deactivate users
- **Parent/Guardian** accounts are self-registered and linked to students via guardian email

---

## Application Pages

### Public
- **Landing Page** (`index.php`) — Overview of the system, nutrition guide, BMI classification table

### Staff (Employee / Admin / Super Admin)
- **Dashboard** (`dashboard.php`) — Stats cards, BMI/gender distribution charts, paginated records table with live search, edit/delete modals
- **Add Record** (`addrecord.php`) — Student data entry form with BMI auto-calculation and preview before saving
- **Student Records** (`records.php`) — Searchable/filterable list of all students with record history modal
- **Feeding Program** (`feeding_program.php`) — Lists underweight students, log feeding sessions, view feeding history
- **Meal Planning** (`meal_planning.php`) — Weekly calendar view to plan meals with calorie/protein info
- **Bulk Import** (`bulk_import.php`) — Upload CSV to import multiple records at once
- **Reports** (`reports.php`) — Nutritional status summary with grade/gender breakdowns, printable
- **DepEd Reports** (`deped_reports.php`) — SBFP Masterlist, OPT Summary, Quarterly Nutritional Status in DepEd format

### Super Admin Only
- **User Management** (`user_management.php`) — Create staff accounts, approve/deactivate users, change roles
- **Audit Log** (`audit_log.php`) — Filterable log of all system actions
- **Database Backup** (`backup.php`) — Download full SQL backup

### Parent/Guardian
- **Parent Dashboard** (`parent_dashboard.php`) — View child's BMI stats, weight trends (Chart.js line chart), record history, health recommendations, and feeding program history

---

## Database Schema

The system uses 7 main tables:

| Table                | Purpose                                          |
|----------------------|--------------------------------------------------|
| `accounts`           | User accounts with roles, verification, consent  |
| `stdRecord`          | Student records (BMI, height, weight, guardian)   |
| `feedingRecord`      | Feeding program session logs                     |
| `audit_log`          | User action audit trail                          |
| `notifications`      | In-app notifications                             |
| `data_corrections`   | Parent-requested data correction requests        |
| `meal_plans`         | Weekly meal plans for the feeding program         |
| `scheduled_reports`  | Scheduled report email configuration             |

All tables are auto-created by `db_setup.php`. Migrations for schema changes are handled automatically.

---

## CSV Bulk Import

Upload a CSV file with the following columns (in order):

| # | Column         | Required | Example        |
|---|----------------|----------|----------------|
| 1 | Last Name      | ✅       | Dela Cruz      |
| 2 | First Name     | ✅       | Juan           |
| 3 | M.I.           |          | P              |
| 4 | Gender         | ✅       | Male           |
| 5 | Grade Level    |          | Grade 3        |
| 6 | Section        |          | Sampaguita     |
| 7 | Height         | ✅       | 120            |
| 8 | Weight         | ✅       | 25             |
| 9 | Height Unit    |          | cm (default)   |
| 10| Weight Unit    |          | kg (default)   |
| 11| Guardian Name  |          | Maria Dela Cruz|
| 12| Guardian Number|          | 09171234567    |
| 13| Guardian Email |          | maria@email.com|

A downloadable CSV template is available in the Bulk Import page.

---

## Security Features

- **CSRF Protection** — All forms include CSRF tokens validated server-side
- **Prepared Statements** — All database queries use parameterized queries (MySQLi)
- **Password Hashing** — Passwords stored using `password_hash()` with `PASSWORD_DEFAULT`
- **Session Security** — HTTP-only cookies, strict mode, SameSite=Lax, 1-hour inactivity timeout
- **Rate Limiting** — Session-based rate limiting on login and signup (5 attempts per 5 minutes)
- **Input Sanitization** — All user inputs sanitized with `htmlspecialchars()` and validated
- **Security Headers** — X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, HSTS (production)
- **HTTPS Enforcement** — Automatic redirect to HTTPS in production
- **Email Verification** — 6-digit code with 15-minute expiry for new accounts
- **Error Handling** — Errors logged to file, never displayed to users in production

---

## Production Deployment

1. Use a **strong database password** (not root with empty password)
2. Deploy with **HTTPS** (free SSL from hosting provider or Cloudflare)
3. Run `db_setup.php` once, then **delete or restrict access** to it
4. **Change the default admin password** (`admin@nutriph.com` / `admin123`)
5. Set proper **file permissions** (755 for directories, 644 for files)
6. Restrict access to `/logs/` directory via `.htaccess`
7. Ensure `config.php` is **never committed** to version control (already in `.gitignore`)

---

## License

This project was developed for San Antonio Central School, Philippines.

---

> **Disclaimer:** This system does not provide medical prescriptions or suggestions. It is designed to ease the manual process of recording and managing student BMI data and nutritional classification. Always consult a healthcare professional for medical advice.
