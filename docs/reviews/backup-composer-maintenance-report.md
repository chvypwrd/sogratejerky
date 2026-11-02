# Backup Infrastructure & Composer Dependency Maintenance — Implementation Report

**Date:** 2026-11-02
**Status:** Implemented and verified locally. Not committed or deployed.

## 1. Existing Backup Status

- `spatie/laravel-backup` was not installed before this work.
- Existing documentation described manual database and media backup procedures.
- The scheduler previously handled blog publishing and the bounded database queue worker only.
- Production recurring backups, off-site storage, monitoring, and restore drills remained pending.

## 2. Composer Audit Findings

```text
composer validate --strict: Valid
composer outdated --direct: All direct dependencies up to date
composer audit: No security vulnerability advisories found
composer show spatie/laravel-backup: 10.3.3
composer why-not php 8.4: No blockers
composer check-platform-reqs: Passed on local PHP 8.3.32
```

The local environment runs PHP 8.3.32. PHP 8.4 compatibility was checked through Composer constraints; no installed package blocks PHP 8.4.

## 3. Packages Installed and Updated

Installed:

- `spatie/laravel-backup` 10.3.3
- `spatie/db-dumper` 4.1.1
- `spatie/laravel-signal-aware-command` 2.1.2
- `league/flysystem-aws-s3-v3` 3.35.3
- AWS SDK dependencies for optional S3 off-site backups

Controlled direct updates:

- `ezyang/htmlpurifier` 4.19.1
- `laravel/fortify` 1.41.0
- `laravel/pao` 1.1.5
- `laravel/pint` 1.32.1
- `phpunit/phpunit` 12.5.43
- `spatie/laravel-medialibrary` 11.23.11
- `spatie/laravel-permission` 8.3.2
- `spatie/laravel-sluggable` 4.0.6

Composer constraints were tightened to remove unbound `*` requirements.

## 4. Backup Configuration and Schedules

Local backup storage:

- Disk: `backups`
- Path: `storage/app/backups`
- Private visibility
- Outside the public document root

Scheduled tasks:

| Schedule | Task |
|---|---|
| Daily 02:00 UTC | Database backup |
| Daily 02:15 UTC | Customer-uploaded media backup |
| Sunday 03:00 UTC | Comprehensive database and application backup |
| Daily 04:00 UTC | Retention cleanup |
| Daily 08:00 UTC | Backup health monitoring |

The existing SiteGround `schedule:run` cron remains the only required cron entry.

Configured safeguards:

- Excludes `.env`, vendor, node_modules, public build output, logs, framework caches, backup archives, PHPUnit caches, and release directories.
- Uses `follow_links=false` by default.
- Includes resolved media paths so shared-storage symlinks are not blindly assumed.
- Supports local plus optional off-site disks through `BACKUP_DISKS`.
- Supports archive encryption through `BACKUP_ARCHIVE_PASSWORD`.
- Sends failure notifications to `BACKUP_NOTIFICATION_EMAIL`.
- Applies retention and maximum storage limits.

## 5. Test Results

```text
Full Laravel suite: 787 passed, 2,343 assertions
Backup configuration tests: 4 passed, 22 assertions
Laravel Pint: passed
Blade compilation: passed
```

Backup validation performed:

- Database-only backup succeeded.
- Media backup succeeded.
- Full application backup succeeded.
- Archive inspection confirmed relative paths.
- Full archive excluded `.env`, vendor, node_modules, PHPUnit caches, and backup archives.
- `backup:list` reported healthy backups.
- `backup:monitor` reported healthy backups.
- `backup:clean` completed successfully.
- Controlled restore into a temporary local database succeeded:
  - 52 tables restored
  - 42 migrations restored
- Temporary restore database was dropped after verification.

PHPUnit reported 39 risky tests with no failures.

## 6. SiteGround Configuration Requirements

1. Keep the existing scheduler cron.
2. Ensure `mysqldump` is available, or set `DB_DUMP_BINARY_PATH`.
3. Verify write access to `storage/app/backups` and `storage/app/backup-temp`.
4. Set `BACKUP_NOTIFICATION_EMAIL`.
5. Set and securely store `BACKUP_ARCHIVE_PASSWORD`.
6. Configure `BACKUP_DISKS=backups,s3` only after S3 credentials are verified.
7. Verify the real media path under SiteGround shared storage.
8. Do not enable `BACKUP_FOLLOW_LINKS` without testing the production storage layout.

## 7. Restore Instructions

1. Put the application into maintenance mode.
2. Retrieve the selected private archive.
3. Extract it to a temporary private directory.
4. Restore the SQL dump into the target database.
5. Restore `storage/app/public`.
6. Run `php artisan optimize`.
7. Run `php artisan app:production-check`.
8. Verify storefront, account, admin, media, and payment-status pages.

Detailed instructions are documented in `docs/production-readiness.md`.

## 8. Deployment Considerations

- Configure backup credentials before deployment.
- Test restoration into a temporary database before production use.
- Keep off-site credentials in the protected production `.env`.
- Keep backup archives private and outside `public/`.
- The working tree also contains the previously accepted but uncommitted test-order cleanup work; backup changes are separate.
- No commit, push, deployment, or production data restoration was performed.
