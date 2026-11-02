<?php

namespace Tests\Feature\Production;

use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Tests\TestCase;

class BackupConfigurationTest extends TestCase
{
    public function test_local_backup_disk_is_private_and_outside_the_public_directory(): void
    {
        $disk = config('filesystems.disks.backups');

        $this->assertSame('local', $disk['driver']);
        $this->assertSame(storage_path('app/backups'), $disk['root']);
        $this->assertSame('private', $disk['visibility']);
        $this->assertStringStartsNotWith(public_path(), $disk['root']);
    }

    public function test_all_spatie_backup_notification_types_are_registered(): void
    {
        $notifications = config('backup.notifications.notifications');

        foreach ([
            BackupHasFailedNotification::class,
            UnhealthyBackupWasFoundNotification::class,
            CleanupHasFailedNotification::class,
            BackupWasSuccessfulNotification::class,
            HealthyBackupWasFoundNotification::class,
            CleanupWasSuccessfulNotification::class,
        ] as $notification) {
            $this->assertArrayHasKey($notification, $notifications);
        }
    }

    public function test_full_backup_includes_application_files_and_excludes_sensitive_or_generated_paths(): void
    {
        $source = config('backup_full.backup.source.files');

        $this->assertContains(base_path(), $source['include']);
        $this->assertContains(base_path('vendor'), $source['exclude']);
        $this->assertContains(base_path('node_modules'), $source['exclude']);
        $this->assertContains(base_path('.env'), $source['exclude']);
        $this->assertContains(base_path('public/build'), $source['exclude']);
        $this->assertContains(base_path('storage/app/backups'), $source['exclude']);
        $this->assertFalse(config('backup_full.backup.verify_backup'));
    }

    public function test_scheduler_registers_database_media_full_cleanup_and_monitor_tasks(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('backup:run --only-db', $output);
        $this->assertStringContainsString('backup:run --only-files', $output);
        $this->assertStringContainsString('backup:run --config=backup_full', $output);
        $this->assertStringContainsString('backup:clean', $output);
        $this->assertStringContainsString('backup:monitor', $output);
    }
}
