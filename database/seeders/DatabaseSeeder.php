<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            LaunchContentSeeder::class,
            MenuSeeder::class,
            SettingsSeeder::class,
            SiteBrandingSeeder::class,
        ]);
    }
}
