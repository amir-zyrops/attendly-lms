# Attendly Academic Portal

Attendly is a PHP-based academic attendance portal for managing role-based access, attendance sheets, schedules, leave requests, reports, and profile preferences.

## What works

- Role-based portal access for administrators, faculty, students, and parents/guardians
- OTP-based sign-in flow with secure email verification and configurable expiry
- SMTP integration for automated OTP delivery via email
- PostgreSQL database backend with JSONB for flexible schema
- Centralized routing and request handling in `index.php`
- Role-specific views for dashboards, attendance sheets, schedule management, course registry, leave requests, reports, and profile settings
- Course create/edit/delete registry and schedule cards for faculty/admin portals
- Session-based preferences such as dark mode and notification toggles
- Production-ready security headers and error handling

## Requirements

- PHP 8.0 or newer (with PDO and PostgreSQL driver extensions)
- PostgreSQL 12 or newer
- A modern web browser with JavaScript enabled
- Valid SMTP credentials for email OTP delivery

## Installation

1. Clone the repository and navigate to the project directory
2. Configure environment variables in `.env` (copy from `.env.example`)
3. Run the database initialization script in PostgreSQL:
   ```bash
   psql -U attendly_user -d attendly -f db.sql
   ```
4. Serve the project through Apache, Nginx, or a managed PHP runtime

**For development**: PHP's built-in server can be used locally:
```bash
php -S localhost:8000
```

## Configuration

Create a `.env` file with the following:

```env
# PostgreSQL Database
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=attendly
DB_USER=attendly_user
DB_PASS=your_secure_password

# SMTP Email Delivery
SMTP_HOST=smtp.yourdomain.com
SMTP_PORT=587
SMTP_USER=your_email@yourdomain.com
SMTP_PASS=your_app_password
SMTP_FROM=noreply@yourdomain.com

# OTP Configuration
OTP_TTL_SECONDS=600
```

See `.env.example` for the complete template.

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

## Data Storage

The application uses PostgreSQL as the primary data store:

- `attendly_users` - Role-based portal shells (admin, faculty, student, parent)
- `attendly_students` - Student records with enrollment and attendance tracking
- `attendly_courses` - Course information with compliance and absence statistics
- `attendly_leaves` - Student leave requests with approval workflow
- `attendly_reports` - Generated academic reports archive
- `attendly_logs` - Audit trail of portal activities

On first deployment, run `db.sql` to initialize all tables and role shells.

## Security Features

- **OTP Authentication**: Email-based one-time passwords with configurable expiry
- **Session Security**: HTTPOnly, SameSite=Strict cookies
- **Security Headers**: X-Content-Type-Options, X-Frame-Options, CSP protection
- **Input Validation**: HTML escaping and parameterized SQL queries
- **Error Handling**: Production error handling with no sensitive information exposed
- **Environment Isolation**: `.env` credentials excluded from version control

## Production Deployment

1. Use a managed PHP runtime (Apache mod_php, PHP-FPM, or similar)
2. Configure `.env` with production credentials (keep file outside web root if possible)
3. Set up PostgreSQL with strong user authentication
4. Configure SMTP with your organization's mail server
5. Enable HTTPS/TLS for all connections
6. Set `error_reporting` to log errors only, never display to users
7. Configure regular database backups
8. Review security headers and customize as needed

## Development Notes

- For local development, use PHP's built-in server: `php -S localhost:8000`
- PHP syntax validation: `for file in *.php; do php -l "$file"; done`