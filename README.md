# LakbayCash — Mobile Loan App for Filipinos

A complete, production-ready, mobile-first loan application built with **PHP + MySQL**.

## Brand
**LakbayCash** — "Kasama Mo Sa Bawat Hakbang" (Your companion in every step).
A premium fintech brand designed specifically for Filipino users.

## Tech Stack
- **Backend:** PHP (no framework, pure PHP)
- **Database:** MySQL
- **Hosting:** AeonFree (shared hosting compatible)

## Features

### User App
- Landing page with premium fintech design
- Authentication: Mobile, Name, Email → OTP simulation (code displayed on screen)
- 5-step onboarding with progress bar (0–100%):
  1. Personal Information (with dynamic dependents 1–5)
  2. Employment (document uploads + work info)
  3. Identity Verification (ID front, back, face scan)
  4. Disbursal Method (E-Wallet with QR agreement / Bank card)
  5. Emergency Contacts (exactly 3)
- Credit Limit generation (₱500–₱6,000 via system RNG)
- Loan application (1–9 months, 4% flat interest)
- Loan status tracking with auto-evaluation (~5 min)
- Credit restriction (one active loan at a time)
- Transaction history
- Profile view

### Admin Panel (hidden, not linked from landing page)
- Dashboard with stats
- User management with search & sort
- User detail view (all onboarding data grouped)
- Loan management with status updates
- Document viewer (uploaded files)
- QR/Agreement image manager
- Loan settings (limits, rate, delay)
- Admin password change

## Installation

### 1. Upload to hosting
Upload the entire `htdocs/` folder contents to your web root (e.g., `public_html/` on AeonFree).

### 2. Create database
1. Log into your hosting control panel (cPanel)
2. Create a MySQL database (e.g., `lakbaycash`)
3. Create a database user and assign it to the database with all privileges

### 3. Import schema
1. Open phpMyAdmin
2. Select your database
3. Click "Import"
4. Upload `database.sql` and click "Go"

### 4. Configure
Edit `config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 5. Set permissions
Ensure the `uploads/` directory and its subdirectories are writable (chmod 755 or 775).

### 6. Access
- User app: `https://yourdomain.com/`
- Admin panel: `https://yourdomain.com/admin/login.php`

## Default Admin Credentials
- **Username:** `superadmin`
- **Password:** `Admin@2024!`

**IMPORTANT:** Change the admin password immediately after first login via Settings.

## File Structure
```
htdocs/
├── index.php              # Landing page
├── login.php              # User registration
├── verify.php             # OTP verification
├── logout.php             # User logout
├── dashboard.php          # User dashboard
├── apply.php              # Loan application
├── loan-status.php        # Loan status
├── transactions.php       # Transaction history
├── profile.php            # User profile
├── config.php             # App configuration
├── database.sql           # Database schema
├── includes/
│   ├── auth.php           # Session & auth bootstrap
│   ├── db.php             # PDO connection
│   ├── helpers.php        # Helper functions
│   └── header.php         # Shared header/footer
├── onboarding/
│   ├── step1.php           # Personal Info
│   ├── step2.php           # Employment
│   ├── step3.php           # Identity Verification
│   ├── step4.php           # Disbursal Method
│   └── step5.php           # Emergency Contacts
├── admin/
│   ├── login.php           # Admin login
│   ├── logout.php          # Admin logout
│   ├── index.php           # Admin dashboard
│   ├── users.php           # User management
│   ├── user-detail.php     # User detail
│   ├── loans.php           # Loan management
│   ├── settings.php        # Settings
│   └── includes/
│       └── admin-header.php # Admin sidebar
├── assets/
│   ├── css/
│   │   ├── app.css         # Design system
│   │   └── landing.css     # Landing & admin styles
│   └── js/
│       └── app.js          # Shared JS
└── uploads/
    ├── documents/          # Employment & ID uploads
    ├── qr/                 # QR/agreement images
    └── profile/            # Profile images
```

## Security Notes
- Admin panel is not linked from the landing page
- Passwords are hashed with `password_hash()` (bcrypt)
- All database queries use PDO prepared statements
- Session cookies are HttpOnly
- File uploads are validated by extension
