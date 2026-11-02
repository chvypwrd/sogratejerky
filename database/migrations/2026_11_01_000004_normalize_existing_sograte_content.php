<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $targets = [
            'settings' => ['value'],
            'pages' => ['title', 'body'],
            'products' => ['name', 'short_description', 'description', 'seo_title', 'seo_description'],
            'product_categories' => ['name', 'description', 'seo_title', 'seo_description'],
            'product_tags' => ['name'],
            'blog_posts' => ['title', 'excerpt', 'body', 'seo_title', 'seo_description'],
            'blog_categories' => ['name', 'description'],
            'blog_tags' => ['name'],
            'faqs' => ['question', 'answer'],
            'testimonials' => ['name', 'quote', 'attribution'],
            'galleries' => ['title', 'description'],
        ];

        foreach ($targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $rows = DB::table($table)
                    ->select(['id', $column])
                    ->where($column, 'like', '%SOGRATE%')
                    ->get();

                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$column => str_replace('SOGRATE', 'Sograte', (string) $row->{$column})]);
                }
            }
        }

        Cache::forget('settings.all');
    }

    public function down(): void {}
};
