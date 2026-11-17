# SmartBus — Bus Reservation and Fleet Management System

> **A Commercial-Grade, Real Database-Connected Web Application for Intercity Travel & Transit Operations**  
> *Developed for College Final-Year & Major Academic Capstone Evaluation*

---

## 🚌 1. Project Overview

**SmartBus** is a modern full-stack web application designed to bring the booking convenience, polished aesthetics, and operational efficiency of commercial travel platforms (inspired by RedBus) into an original, secure, and extensible system.

Unlike static UI prototypes, SmartBus is a **100% database-backed, real-time application** featuring:
- Live multi-criteria bus search with dynamic fare calculation.
- Interactive seat layout selector supporting single/double sleeper berths and executive seater coaches.
- ACID-compliant database concurrency control with row-level locks preventing double-booking race conditions.
- Interactive demo payment checkout (UPI, Cards, NetBanking) with clear academic demonstration labeling.
- Printable digital E-Tickets with self-contained, offline-generated SVG QR codes and a dedicated conductor verification portal.
- Tiered automated cancellation and refund calculation engine.
- Real-time simulated GPS bus tracking powered by Leaflet and OpenStreetMap tiles.
- Comprehensive Administrator Dashboard with Chart.js analytics, operational logs, fleet/route/schedule CRUD, and CSV report exports.

---

## 🛠️ 2. Technology Stack

- **Frontend:** Semantic HTML5, CSS3 (Modern Flexbox, CSS Grid, Custom Variables), Vanilla JavaScript (ES6+).
- **Backend:** Core PHP 8.2+ (Clean modular MVC-inspired architecture with PSR-4 autoloading).
- **Database:** MySQL 8.x / MariaDB 10.x through XAMPP.
- **Database Connectivity:** PHP PDO with prepared statements for 100% of parameterized queries.
- **Local Web Server:** Apache through XAMPP (Windows).
- **Typography & Theme:** Inter typography, Deep Navy (`#0f172a`), Vibrant Coral/Red (`#ef4444`), Clean Slate surfaces.
- **Charts & Telematics:** Chart.js for analytics, Leaflet with OpenStreetMap for GPS tracking.
- **QR Codes:** Lightweight, pure-PHP offline SVG QR generator (zero external network/cloud dependencies).

---

## 📂 3. Project Directory Structure

```text
SmartBus/
├── app/
│   ├── config/             # Database and application configuration
│   │   ├── app.php
│   │   └── database.php
│   ├── core/               # Core infrastructure
│   │   ├── Auth.php        # Authentication & Role-based authorization
│   │   ├── Csrf.php        # CSRF token generation and validation
│   │   ├── Database.php    # PDO connection manager
│   │   ├── Session.php     # Session & flash message manager
│   │   ├── Validator.php   # Server-side input validation rules
│   │   └── View.php        # Template renderer & JSON helpers
│   ├── helpers/
│   │   └── helpers.php     # Dynamic base URL resolver, formatting, audit logs
│   ├── models/             # Database models
│   │   ├── User.php
│   │   ├── Bus.php
│   │   ├── Route.php
│   │   ├── Trip.php
│   │   ├── Booking.php
│   │   ├── Coupon.php
│   │   ├── Review.php
│   │   └── Notification.php
│   ├── services/           # Business logic services
│   │   ├── BookingService.php          # Atomic row-locked seat reservations
│   │   ├── QrCodeService.php           # Pure PHP offline SVG QR generator
│   │   ├── ReportService.php           # Aggregated statistics & CSV export
│   │   └── TrackingSimulationService.php# Simulated GPS telematics
│   └── bootstrap.php       # PSR-4 autoloader, session init, timezone
├── database/
│   ├── smartbus.sql        # Full normalized relational schema and seeds
│   └── install.php         # Automated 1-click database installer & migrator
├── public/
│   ├── assets/
│   │   ├── css/            # style.css, admin.css, seat-layout.css
│   │   ├── js/             # main.js, seat-selection.js, tracking.js
│   │   └── images/         # SVG icons & branding logo
│   ├── index.php           # Application homepage
│   ├── search.php          # Multi-criteria bus search engine
│   ├── seat_selection.php  # Interactive seat map & passenger checkout
│   ├── payment_demo.php    # Demo payment simulator
│   ├── booking_confirmation.php # Instant confirmation
│   ├── ticket.php          # Official E-Ticket view & printable receipt
│   ├── ticket_verify.php   # QR verification portal
│   ├── tracking.php        # Live simulated GPS route map
│   ├── login.php           # Sign in
│   ├── register.php        # Passenger registration
│   ├── forgot_password.php # Password recovery
│   ├── reset_password.php  # Password reset handler
│   ├── api/
│   │   └── coupon_validate.php # AJAX coupon validator
│   ├── passenger/          # Passenger portal (Dashboard, Bookings, Refunds, Settings)
│   ├── admin/              # Administrator portal (Dashboard, Fleet, Routes, Trips, Coupons, Reports)
│   └── pages/              # Static informational pages (About, Contact, FAQ, Terms, Privacy)
├── storage/
│   ├── logs/               # Application error logs
│   └── sessions/           # Local session fallback storage
├── tests/
│   └── run_tests.php       # Automated test suite
├── .env.example            # Environment configuration template
├── .htaccess               # Apache forwarder & security protection
└── README.md               # Complete setup and evaluation guide
```

---

## 🚀 4. Step-by-Step Installation Guide (Windows + XAMPP)

### Prerequisites
- Windows 10 or Windows 11.
- **XAMPP for Windows** installed (default path: `C:\xampp`).

---

### Step 1: Copy Project Files to `htdocs`
Copy or move the project folder into your XAMPP web root directory:
```text
C:\xampp\htdocs\SmartBus
```
*(If your folder is named `bus`, you can place it as `C:\xampp\htdocs\bus`)*.

---

### Step 2: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Ensure both modules turn green and report active port numbers (Apache: `80, 443`, MySQL: `3306`).

---

### Step 3: Database Setup

You have two convenient options to set up the database:

#### Option A: One-Click Browser Installer (Easiest)
Open your web browser and navigate to:
```text
http://localhost/SmartBus/database/install.php
```
*(or `http://localhost/bus/database/install.php`)*  
The automated installer will create the `smartbus` database, execute all table creation statements, configure foreign keys, and seed all routes, buses, and trips!

#### Option B: Via phpMyAdmin
1. Open your web browser and go to: `http://localhost/phpmyadmin/`.
2. Click **New** in the left sidebar and create a database named `smartbus` (Collation: `utf8mb4_unicode_ci`).
3. Select the `smartbus` database, click the **Import** tab at the top.
4. Click **Choose File**, select `C:\xampp\htdocs\SmartBus\database\smartbus.sql`, and click **Import** at the bottom.

#### Option C: Via Windows Command Prompt
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -p -e "CREATE DATABASE IF NOT EXISTS smartbus;"
C:\xampp\mysql\bin\mysql.exe -u root -p smartbus < "C:\xampp\htdocs\SmartBus\database\smartbus.sql"
```

---

### Step 4: Configure Database Connection (If Non-Default)
If your local MySQL uses a custom port (e.g. `3307`) or has a root password, edit `app/config/database.php`:
```php
return [
    'host' => '127.0.0.1',
    'port' => '3306',        // Change if non-default
    'dbname' => 'smartbus',
    'username' => 'root',
    'password' => '',        // Enter password if set
    'charset' => 'utf8mb4'
];
```

---

### Step 5: Launch SmartBus in Browser
Open your browser and visit:
```text
http://localhost/SmartBus/public/
```
*(or simply `http://localhost/SmartBus/` which automatically forwards to `public/`)*.

---

## 🔑 5. Pre-Seeded Demonstration Accounts

For academic evaluation and demonstration, the database includes two ready-to-use accounts:

| Role | Email Address | Password | Permissions / Capabilities |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@smartbus.com` | `Admin@123` | Full Admin Dashboard, fleet/route/schedule CRUD, financial reports, user suspension, audit trails |
| **Passenger** | `passenger@smartbus.com` | `Passenger@123` | Route search, seat selection, demo payment, E-Ticket printing, live tracking, cancellation |

> **Quick Fill Button:** On the Sign In page (`login.php`), click the "Fill Admin" or "Fill Passenger" buttons to instantly populate the credentials.

---

## 🏷️ 6. Active Promotional Discount Coupons

During the seat booking checkout, enter any of these active coupons into the promo code field:

| Coupon Code | Discount Type | Value | Minimum Booking Amount | Max Cap |
| :--- | :--- | :--- | :--- | :--- |
| `WELCOME50` | Percentage | 20% OFF | ₹ 400.00 | ₹ 150.00 |
| `SMART100` | Flat Amount | ₹ 100.00 OFF | ₹ 600.00 | ₹ 100.00 |
| `SUPERBUS` | Percentage | 15% OFF | ₹ 500.00 | ₹ 200.00 |

---

## 🧪 7. Automated Test Suite Execution

SmartBus comes with an automated PHP verification suite that validates core security, input validation rules, QR code generation, SQL schema integrity, and runs syntax linting across all 65 PHP project files.

To execute the test suite:
```powershell
& "C:\xampp\php\php.exe" tests/run_tests.php
```

Expected output:
```text
=========================================================
 SmartBus Automated Verification Suite
=========================================================
1. Testing Security & CSRF Token Engine...
  [PASS] CSRF token is generated and non-empty
  [PASS] CSRF token validates with exact match
  [PASS] CSRF token rejects forged token
  ...
5. Testing PHP Syntax Lint Across Project Files...
  [PASS] All 65 PHP files passed syntax linting with 0 errors
=========================================================
 Test Summary: 28 Passed, 0 Failed
=========================================================
```

---

## 🔒 8. Security & Concurrency Controls

1. **ACID Row Locking on Seats:**  
   When booking seats, the system initiates a PDO database transaction and performs `SELECT ... FOR UPDATE` on `trip_seats`. If another passenger attempts to book the same seat at the same second, their request safely encounters an availability exception and rolls back cleanly without inconsistent states.
2. **Role-Based Authorization Guards:**  
   Every admin endpoint strictly validates `Auth::requireAdmin()`. Non-admins or unauthenticated users attempting direct access are redirected to `views/errors/403.php` with an HTTP 403 status code.
3. **Password Security:**  
   Passwords are never stored in plaintext. They are hashed using `password_hash($password, PASSWORD_BCRYPT)` and authenticated with `password_verify()`.
4. **Session Fixation Defense:**  
   `session_regenerate_id(true)` is executed upon successful login to invalidate old session tokens.
5. **CSRF Tokens:**  
   Every state-changing POST form embeds a cryptographically randomized CSRF token verified via `hash_equals()`.
6. **Output Escaping:**  
   All dynamic database output is escaped via `e()` using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` to neutralize XSS vectors.

---

## 🔄 9. Cancellation & Refund Policy Engine

Cancellations are calculated strictly on the server based on the hours remaining until scheduled departure:
- **> 24 hours prior:** 90% refund (10% cancellation charge).
- **12 – 24 hours prior:** 75% refund (25% cancellation charge).
- **2 – 12 hours prior:** 50% refund (50% cancellation charge).
- **< 2 hours prior:** Non-refundable (100% cancellation charge).

Upon cancellation, a database transaction automatically marks the booking as `CANCELLED`, records the refund in `cancellations`, and **releases the seats in `trip_seats` back to `available`** so other travelers can book them immediately.

---

## 💡 10. Educational Demonstrations & Simulations

- **Simulated Payment Gateway:** SmartBus simulates real-time card and UPI payment processing with success, failure, and cancellation paths. No actual credit cards or bank credentials are ever stored.
- **Simulated GPS Telematics:** The live tracking page (`tracking.php`) visualizes route waypoints on an OpenStreetMap Leaflet layer and periodically simulates GPS movement along the highway.
- **Simulated Local Development Email:** In local development environments without an active SMTP server, password reset links are displayed directly in the browser with instructions on how to connect PHPMailer or SMTP for production.

---

## 🛠️ 11. Troubleshooting Common XAMPP Issues

- **Apache Port 80 Conflict:**  
  If Apache fails to start because port 80 is occupied (e.g., by Skype or IIS), open XAMPP Control Panel $\to$ Apache **Config** $\to$ `httpd.conf` $\to$ change `Listen 80` to `Listen 8080`, and access the project via `http://localhost:8080/SmartBus/public/`.
- **MySQL Connection Error (10013 / 2002):**  
  Ensure MySQL is started in XAMPP. If using a custom port (e.g. 3307), update `app/config/database.php`.
- **Database "smartbus" Not Found:**  
  Run the automated installer at `http://localhost/SmartBus/database/install.php` to generate the database and seed records.

---

**SmartBus** — *Engineered with precision for seamless intercity transit and academic excellence.*

