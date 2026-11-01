<?php

namespace Tests\Feature\Production;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduledQueueTest extends TestCase
{
    public function test_queue_worker_is_scheduled_every_minute_with_overlap_protection(): void
    {
        $events = collect(app(Schedule::class)->events());

        $queueEvents = $events->filter(fn ($event) => str_contains((string) $event->command, 'queue:work --stop-when-empty'));

        $this->assertCount(1, $queueEvents);

        $event = $queueEvents->first();

        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(10, $event->expiresAt);
        $this->assertStringContainsString('--tries=3', $event->command);
        $this->assertStringContainsString('--timeout=60', $event->command);
    }

    public function test_existing_scheduled_blog_publication_remains_registered(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertCount(1, $events->filter(fn ($event) => str_contains((string) $event->command, 'blog:publish-scheduled')));
    }

    public function test_database_queue_retry_after_exceeds_worker_timeout(): void
    {
        $retryAfter = (int) config('queue.connections.database.retry_after');

        $this->assertGreaterThan(60, $retryAfter);
        $this->assertGreaterThanOrEqual(30, $retryAfter - 60);
    }
}
