# HRM System

Laravel 12 based Human Resource Management system.

## Requirements

- PHP `^8.2`
- Composer
- Node.js + npm
- MySQL (default config in `.env.example`)

## Setup

1. Clone the repository and move into the project directory.
2. Install dependencies and run initial app setup:

```bash
composer run setup
```

This command runs:
- `composer install`
- copies `.env.example` to `.env` if needed
- `php artisan key:generate`
- `php artisan migrate`
- `npm install`
- `npm run build`

3. Update database credentials in `.env` if your local MySQL setup differs from defaults.

## Seed Demo Data (Optional)

Use the custom demo command:

```bash
php artisan demo:data
```

Command purpose:
- Generate complete HRM demo dataset (users, profiles, departments, branches, attendance, leave, payroll, holidays, activity).

Command options:
- `--clean`  
  Drop all tables, run migrations again, then generate demo data.
- `--clean-only`  
  Drop all tables and run migrations only (no demo data generation).
- `--employees=<count>`  
  Number of demo employees to generate/update. Default: `30`.
- `--months=<count>`  
  Number of historical months for attendance/payroll demo data. Default: `3` (allowed: `1` to `12`).

Useful options:

```bash
# clean DB first, then generate demo data
php artisan demo:data --clean

# only clean DB (drop + migrate), no demo generation
php artisan demo:data --clean-only

# generate larger dataset
php artisan demo:data --employees=60 --months=6 --clean
```

Show command help:

```bash
php artisan help demo:data
```

Default seeded logins:
- `admin@hrm.test`
- `hr@hrm.test`
- `employee@hrm.test`

Password for all seeded users:
- `Password@123`

## Run Locally

Start the full local development stack (web server, queue listener, logs, and Vite):

```bash
composer run dev
```

## Testing

```bash
composer test
```
