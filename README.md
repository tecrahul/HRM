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

## Production File Permissions and Storage Link

Profile avatars, company logos, and uploaded attachments are served from `public/storage`, so the storage symlink must exist in production.

Run from the project root:

```bash
php artisan storage:link
```

Set ownership and permissions so the web server can read app files and write only where needed.
Replace `<deploy-user>` and `<web-group>` with your server values (common web group: `www-data`).

```bash
# app ownership (example)
sudo chown -R <deploy-user>:<web-group> /var/www/hrm-system

# default permissions for code
sudo find /var/www/hrm-system -type f -exec chmod 644 {} \;
sudo find /var/www/hrm-system -type d -exec chmod 755 {} \;

# writable directories required by Laravel
sudo chown -R <deploy-user>:<web-group> /var/www/hrm-system/storage /var/www/hrm-system/bootstrap/cache
sudo chmod -R 775 /var/www/hrm-system/storage /var/www/hrm-system/bootstrap/cache
```

If uploads still fail on production, also verify PHP/web-server upload limits (`upload_max_filesize`, `post_max_size`, and nginx `client_max_body_size` if using nginx).

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

## Container Deployment (Docker)

This repository now includes a production-oriented container stack with:
- PHP-FPM app image (`Dockerfile`, multi-stage build)
- Nginx (default web entry)
- Apache (optional profile)
- MySQL 8.4
- Redis
- Queue worker and scheduler services

### Included Config Files

- `Dockerfile`
- `docker-compose.yml`
- `.dockerignore`
- `docker/entrypoint.sh`
- `docker/php/php.ini`
- `docker/php/opcache.ini`
- `docker/php/www.conf`
- `docker/nginx/nginx.conf`
- `docker/nginx/default.conf`
- `docker/apache/httpd.conf`

### Start the Stack

Use either Docker Compose command style supported on your machine:
- `docker compose ...`
- `docker-compose ...`

Default stack (Nginx + app + db + redis + queue + scheduler):

```bash
docker-compose up -d --build
```

Optional Apache web service (runs in addition to default services):

```bash
docker-compose --profile apache up -d --build
```

### First-Time App Initialization

After containers are running:

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
```

Optional (recommended for production):

```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Access URLs

- Nginx: `http://localhost:8080` (or `${NGINX_PORT}`)
- Apache profile: `http://localhost:8081` (or `${APACHE_PORT}`)
- Health check endpoint: `/healthz`

### Environment Notes

- The compose file reads `.env` via `env_file`.
- DB/Redis/container defaults are defined in `docker-compose.yml` and can be overridden in `.env`.
- Ensure production values are set for:
  - `APP_KEY`
  - `APP_URL`
  - `DB_*`
  - `REDIS_*`
  - `MAIL_*`

### Stop and Cleanup

Stop services:

```bash
docker-compose down
```

Stop and remove named volumes (data loss: DB/Redis/storage):

```bash
docker-compose down -v
```

## Testing

```bash
composer test
```
