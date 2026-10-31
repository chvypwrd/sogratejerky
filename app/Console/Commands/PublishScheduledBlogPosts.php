<?php

namespace App\Console\Commands;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PublishScheduledBlogPosts extends Command
{
    protected $signature = 'blog:publish-scheduled';

    protected $description = 'Publish scheduled blog posts whose publication time has arrived';

    public function handle(): int
    {
        $count = 0;

        BlogPost::query()
            ->scheduledDue()
            ->orderBy('id')
            ->chunkById(100, function ($posts) use (&$count) {
                foreach ($posts as $post) {
                    $post->update(['status' => BlogPostStatus::Published]);
                    $count++;
                }
            });

        Cache::forget('sitemap.xml');

        $this->info("Published {$count} scheduled blog post(s).");

        return self::SUCCESS;
    }
}
