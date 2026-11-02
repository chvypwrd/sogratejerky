# Sograte Production Readiness

This document covers the current Laravel 13 / MySQL / Stripe deployment procedure. It does not authorize launch. Use `docs/launch-readiness.md` for the current Go/No-Go checklist.

## Prerequisites

- PHP 8.3+ with required Laravel extensions.
- MySQL/MariaDB.
- Composer 2.
- Node.js/npm for the Vite production build.
- Web server document root pointed at `public/`, never the repository root.
- HTTPS certificate and production domain.
- Writable `storage/` and `bootstrap/cache/`.
- `public/storage` created with `php artisan storage:link`.

## Production Environment

Start from `.env.production.example` and keep secrets out of Git.

Required values include:

```env
APP_NAME="Sograte"
APP_ENV=production
APP_KEY=<stable generated key>
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=<host>
DB_PORT=3306
DB_DATABASE=<database>
DB_USERNAME=<user>
DB_PASSWORD=<password>

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=sograte_session

QUEUE_CONNECTION=database
DB_QUEUE=sograte
CACHE_STORE=database

LOG_CHANNEL=daily
LOG_LEVEL=warning
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=<host>
MAIL_PORT=587
MAIL_USERNAME=<user>
MAIL_PASSWORD=<password>
MAIL_FROM_ADDRESS=<verified sender>
MAIL_FROM_NAME="Sograte"

CHECKOUT_ENABLED=true
ALLOW_PRODUCTION_CHECKOUT=false
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_API_VERSION=2026-10-28.endive
```

Keep `ALLOW_PRODUCTION_CHECKOUT=false` until every critical gate in `docs/launch-readiness.md` is verified.

### APP_KEY

Generate once in the production environment with `php artisan key:generate` and preserve it securely. Do not change an existing production key unless the session/data impact is understood.

## Deployment Procedure

1. Put the application in maintenance mode:
   `php artisan down --render="errors::503" --retry=60`
2. Create a database and media backup (see Backup and Restore).
3. Check out the approved commit/tag. Do not deploy unapproved changes.
4. Install PHP dependencies:
   `composer install --no-dev --prefer-dist --optimize-autoloader`
5. Install and build frontend assets:
   `npm ci`
   `npm run build`
6. Configure `.env` from `.env.production.example`; verify mode, HTTPS URL, secure sessions, mail, Stripe, queue, and the closed checkout gate.
7. Ensure `storage/` and `bootstrap/cache/` are writable by the web server user.
8. Create or verify the public storage link:
   `php artisan storage:link`
9. Run migrations:
   `php artisan migrate --force`
10. Rebuild caches:
    `php artisan optimize`
11. Restart long-running queue workers:
    `php artisan queue:restart`
12. Bring the application online:
    `php artisan up`
13. Verify `GET /up` returns 200.
14. Run `php artisan app:production-check` and review every warning/failure.
15. Complete smoke checks for homepage, catalog, cart, checkout gate, admin login, sitemap, robots, media, and private-page protections.

## One-Time Administrator Seeding

The administrator seeder is not part of the normal DatabaseSeeder run. Set temporary values in the production environment:

```env
ADMIN_SEED_NAME=<administrator display name>
ADMIN_SEED_EMAIL=<administrator email>
ADMIN_SEED_PASSWORD=<strong one-time password>
```

Run exactly once:

```bash
php artisan db:seed --class=AdminUserSeeder
```

The seeder creates a verified Super Admin, refuses to promote an existing customer account, leaves existing administrators and passwords unchanged, and prints no credentials. Remove the temporary ADMIN_SEED_* values after successful creation. Existing administrator 2FA settings are preserved.

## Queue Worker

For the current SiteGround shared hosting deployment, queue processing is bounded through Laravel's scheduler rather than a permanent worker daemon. See `docs/siteground-queue-setup.md` for the complete procedure.

The scheduler runs:

```text
queue:work --stop-when-empty --tries=3 --timeout=60
```

every minute with overlap protection. The database queue `retry_after` remains 90 seconds, leaving a 30-second safety margin.

Restart workers after deployment with `php artisan queue:restart`. Monitor failed jobs with `php artisan queue:failed`.

## Scheduler

Add the Laravel scheduler to cron:

```cron
* * * * * cd /home/CUSTOMER/public_html/Live && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Use the verified production path and PHP executable; the values above are placeholders. The cron entry must point to the stable `Live` deployment directory, not a timestamped release directory. Do not add a separate `queue:work` cron entry.

The application schedules both `blog:publish-scheduled` and the bounded queue worker every minute. Confirm cron is active and `php artisan schedule:list` shows both tasks.

## Logging and Operations

- Use the `daily` log channel and a production-appropriate log level.
- Keep `storage/logs/` private.
- Review application logs and failed queue jobs after deployment.
- Monitor Stripe webhook failures and `stripe_events` failure records.
- Do not log credentials, full Stripe payloads, or card data.

## Database and Media Backup

### Database

```bash
mysqldump -u <user> -p --single-transaction --routines --triggers <database> > sograte-$(date +%Y%m%d-%H%M).sql
```

### Media

Back up `storage/app/public` and the database `media` table together. Uploaded product, page, blog, gallery, and branding files live there.

### Retention and Storage

- Keep daily database backups and regular media backups.
- Store copies off-host with restricted access.
- Encrypt backups where supported.
- Define retention consistent with business and legal requirements.

### Restore Procedure

1. Put the application in maintenance mode.
2. Restore the database:
   `mysql -u <user> -p <database> < backup.sql`
3. Restore `storage/app/public`.
4. Rebuild caches with `php artisan optimize`.
5. Run `php artisan app:production-check`.
6. Verify health and representative pages before bringing the site online.

A local non-destructive database restore verification was completed during Milestone 12 by restoring a dump into a temporary database. Production backup schedules and off-host restore drills remain launch tasks.

## Rollback

1. Put the application in maintenance mode.
2. Check out the previous approved commit/tag.
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. Run `npm ci && npm run build`.
5. If a migration must be reversed and was reviewed as safe, roll it back; otherwise restore the pre-deployment database backup.
6. Run `php artisan optimize`.
7. Restart queue workers.
8. Bring the site online and verify `/up` plus critical commerce/admin pages.

Do not reverse destructive migrations without a verified backup.

## Security Requirements

- `APP_DEBUG=false` and `APP_ENV=production`.
- HTTPS only; secure, encrypted session cookies.
- Document root restricted to `public/`.
- `.env` outside the web root and never committed.
- Admin 2FA enabled and administrative permissions unchanged.
- Stripe webhook signature verification enabled.
- Checkout gate closed until launch approval.
- Backup credentials protected separately from the application.
