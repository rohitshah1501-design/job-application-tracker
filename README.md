# Job Application Tracker

A private, responsive job-search tracker built with HTML, CSS, vanilla JavaScript, PHP 8.2, PDO, and MariaDB. Users can manage applications, track status changes, schedule interviews, and organize follow-up dates. Each account can access only its own records.

## Features

- Account registration, sign-in, sign-out, and protected pages
- Password hashing, session-based authentication, and CSRF-protected forms
- Application create, view, edit, delete, search, and status filtering
- Dashboard statistics and application status history
- Interview scheduling and management, with upcoming and past views
- Follow-up groups for overdue, due today, and upcoming dates
- Responsive layout with keyboard focus styles and accessible form labels

## Requirements

- Windows with XAMPP and PHP 8.2
- PHP extension `pdo_mysql`
- XAMPP MariaDB reachable at `127.0.0.1:3307`
- The `job_tracker` database
- A MariaDB account permitted to connect to that server and database

This project uses XAMPP's PHP built-in development server for local testing. It serves only `public/`, so application code, database configuration, migrations, and logs remain outside the web root. This does not change Apache, XAMPP, or either database server's configuration. The separate MySQL service on port 3306 is not used.

## Database setup

1. Start XAMPP MariaDB and open phpMyAdmin configured for port `3307`.
2. Create or select the database `job_tracker` using `utf8mb4`.
3. Before importing, inspect the database's table list. The migration creates four tables and is intended for an empty database. Do not import it again over an existing installation.
4. In phpMyAdmin, select `job_tracker`, choose **Import**, select `database/migrations/001_create_core_tables.sql`, and run the import once.
5. Confirm that `users`, `job_applications`, `application_status_history`, and `interviews` are present.

The migration uses InnoDB, `utf8mb4`, foreign keys, and MariaDB-compatible types. It contains no drop or reset statements. Do not run schema changes against a database with existing data unless you have reviewed the change and made a backup.

## Local database configuration

The private configuration is read from process environment variables by `app/config/database.php`. Defaults target host `127.0.0.1`, port `3307`, database `job_tracker`, and charset `utf8mb4`. Supply the database username in the PowerShell session where the server will run:

```powershell
$env:JOB_TRACKER_DB_USER = 'your MariaDB username'
```

For a local MariaDB account that intentionally has an empty password, explicitly enable that setting:

```powershell
$env:JOB_TRACKER_DB_ALLOW_EMPTY_PASSWORD = '1'
```

Leave `JOB_TRACKER_DB_PASSWORD` unset for that empty-password account. For an account with a password, set `JOB_TRACKER_DB_PASSWORD` privately in the same session and leave the empty-password flag unset. Do not save credentials in source files, the README, command history, or Git. The application rejects a missing username and permits an empty password only when the explicit flag is enabled.

## Start the local site

Open PowerShell in `C:\xampp\htdocs\job_tracker`, set the required environment variables as above, and run:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t 'C:\xampp\htdocs\job_tracker\public'
```

Then open <http://127.0.0.1:8000>. Stop the development server with `Ctrl+C`. This serves the public folder directly and does not require changing Apache's document root.

## Verification

From the project directory, lint every PHP file with XAMPP PHP:

```powershell
Get-ChildItem -Path app,public,tests -Filter *.php -Recurse | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```

The existing CLI database check makes a read-only connection and verifies the selected database and port. Run it only after configuring credentials for the XAMPP MariaDB account:

```powershell
& 'C:\xampp\php\php.exe' tests/database_connection.php
```

## Manual checks

1. Register an account, sign in, and sign out. Verify that protected pages redirect to sign-in when logged out.
2. Add an application with optional fields both filled and blank; view it; edit its status and follow-up date; then delete it after the confirmation page.
3. Search by company and job title, apply each status filter, and clear filters.
4. Create an interview for one of your applications, edit it, view it, and delete it. Check upcoming and past interview sections.
5. Set follow-up dates in the past, today, and future; confirm they appear in their matching dashboard groups.
6. Register a second account and verify it cannot view, edit, or delete the first account's applications or interviews by changing record IDs in URLs or form submissions.
7. Submit invalid dates, URLs, amounts, statuses, and a missing/invalid CSRF token; verify the page shows a safe error and no invalid change is saved.
8. Use keyboard-only navigation and inspect the layout at desktop, tablet, and mobile widths.

Database errors shown to users are intentionally generic. Check the local PHP/Apache logs when diagnosing server-side problems, and keep those logs private.
