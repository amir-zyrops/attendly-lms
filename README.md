# Attendly Academic Portal

Attendly is a PHP-based academic attendance portal for managing role-based access, attendance sheets, schedules, leave requests, reports, and profile preferences.

## What works

- Role-based portal access for administrators, faculty, students, and parents/guardians
- OTP-based sign-in flow with email verification and expiry handling
- SMTP delivery support through environment variables
- PostgreSQL database support with schema initialization from `db.sql`
- JSON file fallback storage under `data/` for local development
- Centralized routing and action handling in `index.php`
- Role-specific views for dashboards, attendance sheets, schedules, leave requests, reports, and profile settings
- Session-based preferences such as dark mode and notification toggles

## Requirements

- PHP 8.0 or newer
- A modern web browser
- Optional: PostgreSQL server and valid `.env` credentials for database-backed storage

## Getting Started

Start the PHP development server from the project root:

```bash
php -S localhost:8000
```

Open the application in your browser:

```text
http://localhost:8000
```

## Project Structure

```text
.
├── index.php        # Main router and action controller
├── config.php       # Environment loading, helpers, storage abstraction, and OTP logic
├── login.php        # Role selection and OTP authentication screen
├── dashboard.php    # Role-based dashboard views
├── attendance.php   # Attendance marking workflow
├── timetable.php    # Schedule and leave request views
├── reports.php      # Report generation and archive view
├── settings.php     # Profile and preference settings
├── db.sql           # PostgreSQL schema definitions
├── .env             # Local runtime environment (ignored by Git)
├── .env.example     # Safe template for environment variables
└── data/            # Runtime JSON data, generated automatically
```

## Runtime Data

The application creates JSON data files under `data/` on first run. These files are treated as local runtime state and are ignored by Git.

A fresh install starts without sample students, courses, leaves, reports, or logs. The role shells are created so the portal can be accessed and configured.

## Development Notes

- Use PHP's built-in server for local development only.
- For production, serve the project through Apache, Nginx, or another managed PHP runtime.
- PostgreSQL is supported when `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` are configured in `.env`.
- SMTP OTP delivery is supported when `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, and `SMTP_FROM` are configured.
- Add authentication and authorization hardening before production deployment.

## Validation

Run PHP syntax checks with:

```bash
for file in *.php; do php -l "$file"; done
```