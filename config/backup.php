<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

$backupName = env('BACKUP_NAME', env('APP_NAME', 'Sograte'));
$backupDisks = array_values(array_filter(array_map('trim', explode(',', (string) env('BACKUP_DISKS', 'backups')))));
$notificationEmail = env('BACKUP_NOTIFICATION_EMAIL') ?: env('MAIL_FROM_ADDRESS');
$mediaPath = storage_path('app/public');
$mediaPaths = array_values(array_unique(array_filter([$mediaPath, realpath($mediaPath)])));

$failureChannels = $notificationEmail ? ['mail'] : [];
$backupNotifications = [
    BackupHasFailedNotification::class => $failureChannels,
    UnhealthyBackupWasFoundNotification::class => $failureChannels,
    CleanupHasFailedNotification::class => $failureChannels,
    BackupWasSuccessfulNotification::class => [],
    HealthyBackupWasFoundNotification::class => [],
    CleanupWasSuccessfulNotification::class => [],
];

return [

    'backup' => [
        'name' => $backupName,

        'source' => [
            'files' => [
                'include' => $mediaPaths,

                'exclude' => [
                    base_path('.env'),
                    base_path('.env.backup'),
                    base_path('.git'),
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('public/build'),
                    base_path('public/hot'),
                    base_path('bootstrap/cache'),
                    base_path('storage/app/backups'),
                    base_path('storage/app/backup-temp'),
                    base_path('storage/framework'),
                    base_path('storage/logs'),
                    base_path('storage/media-library/temp'),
                    base_path('storage/pail'),
                    base_path('releases'),
                    base_path('.phpunit.result.cache'),
                    base_path('.phpunit.cache'),
                    base_path('auth.json'),
                ],

                'follow_links' => (bool) env('BACKUP_FOLLOW_LINKS', false),

                'ignore_unreadable_directories' => false,

                'relative_path' => base_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => 'sograte-',

            'disks' => $backupDisks ?: ['backups'],

            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => env('BACKUP_ENCRYPTION', 'default'),

        'verify_backup' => true,

        'tries' => 1,

        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => $backupNotifications,

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => $notificationEmail,

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Sograte'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => $backupName,
            'disks' => $backupDisks ?: ['backups'],
            'health_checks' => [
                MaximumAgeInDays::class => 2,
                MaximumStorageInMegabytes::class => (int) env('BACKUP_MAX_STORAGE_MB', 5000),
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => (int) env('BACKUP_MAX_STORAGE_MB', 5000),
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],

];
