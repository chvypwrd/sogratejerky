<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('key', 'site_name')
            ->where('value', 'SOGRATE')
            ->update(['value' => 'Sograte']);

        Cache::forget('settings.all');
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('key', 'site_name')
            ->where('value', 'Sograte')
            ->update(['value' => 'SOGRATE']);

        Cache::forget('settings.all');
    }
};
