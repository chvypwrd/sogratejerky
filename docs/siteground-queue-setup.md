# Sograte Queue Worker Setup for SiteGround

Sograte uses Laravel 13's scheduler and the existing database queue on SiteGround shared hosting. A single cron entry runs the scheduler every minute; the scheduler starts a bounded queue worker that exits when the queue is empty.

## Laravel 13 Scheduling Location

There is no `app/Console/Kernel.php` in Laravel 13. Scheduled tasks are registered in:

`routes/console.php`

Current relevant schedule:

```php
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping(10);
```

The existing `blog:publish-scheduled` task remains registered separately.

## Queue Configuration

Recommended production values:

```env
QUEUE_CONNECTION=database
DB_QUEUE=sograte
DB_QUEUE_RETRY_AFTER=90
CACHE_STORE=database
```

- `jobs`, `job_batches`, `failed_jobs`, `cache`, and `cache_locks` tables are created by the standard migrations.
- The worker timeout is 60 seconds. Database queue `retry_after` is 90 seconds, leaving a 30-second safety margin.
- `withoutOverlapping(10)` uses the cache lock store for 10 minutes.
- Do not set `DB_QUEUE_RETRY_AFTER` at or below the worker timeout.
- If a job genuinely needs more than 60 seconds, investigate and fix the job instead of silently raising the timeout.

## SiteGround Cron Setup

In **Site Tools → Advanced → Cron Jobs**, add one cron job:

```cron
* * * * * cd /home/CUSTOMER/public_html/Live && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Replace the directory and PHP executable with values verified on the server:

```bash
pwd
which php
php -v
```

Use the stable deployment directory, commonly `Live` or a symlink to the current release. Do **not** point cron at a timestamped release directory, because that path changes on deployment.

Do not add a second cron job for `queue:work`. The scheduler owns queue processing.

## Verification

From the stable application directory:

```bash
php artisan schedule:list
```

Confirm the scheduled tasks appear:

```text
* * * * * php artisan blog:publish-scheduled
* * * * * php artisan queue:work --stop-when-empty --tries=3 --timeout=60
0 2 * * * php artisan backup:run --only-db
15 2 * * * php artisan backup:run --only-files
0 3 * * 0 php artisan backup:run --config=backup_full
0 4 * * * php artisan backup:clean
0 8 * * * php artisan backup:monitor
```

The backup tasks use the same scheduler cron. See `docs/production-readiness.md` for backup storage, retention, notifications, and restore procedures.

Manually exercise the scheduler once:

```bash
php artisan schedule:run
```

For a harmless queue check, dispatch a temporary test job using existing infrastructure, then run:

```bash
php artisan queue:work --stop-when-empty --tries=3 --timeout=60
```

The worker should print `Worker STOPPED Queue empty` and exit. Do not use customer email or payment jobs for smoke testing.

## Failed Jobs and Troubleshooting

```bash
php artisan queue:failed
php artisan queue:retry <uuid>
php artisan queue:retry all
php artisan queue:forget <uuid>
php artisan queue:flush
```

If scheduled jobs do not run:

1. Confirm the cron timezone and that cron is active in Site Tools.
2. Confirm the cron command uses the correct absolute PHP path and stable `Live` directory.
3. Run `php artisan schedule:list` and `php artisan schedule:run -v`.
4. Check `storage/logs/laravel.log` for errors.
5. Confirm `storage/` and `bootstrap/cache/` are writable.
6. Confirm the database queue and cache tables exist.
7. Temporarily change cron output from `/dev/null` to a private log file when diagnosing.
8. Clear stale cache locks with `php artisan schedule:clear-cache` only after confirming no worker is running.

After deployment:

```bash
php artisan optimize
php artisan queue:restart
```

`queue:restart` is harmless here and helps clear any old worker state.

## Shared Hosting Limitations

- Cron runs no more frequently than once per minute.
- Long-running workers should not be relied on; this setup intentionally uses `--stop-when-empty`.
- There is no Supervisor, Horizon, Redis, or dedicated worker infrastructure.
- Jobs must remain bounded and fit within the 60-second worker timeout.
- If queue volume grows significantly, move to a host with a managed worker before increasing volume.

## Remaining Production Configuration

- Install and verify the SiteGround cron entry manually.
- Configure production SMTP/API mail delivery and queue processing for transactional messages.
- Verify real queued notifications after SMTP setup.
- Review failed jobs and logs after the first production deployment.
- Complete the remaining launch gates documented in `docs/launch-readiness.md`.
