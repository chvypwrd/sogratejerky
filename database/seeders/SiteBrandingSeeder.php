<?php

namespace Database\Seeders;

use App\Models\SiteBranding;
use Illuminate\Database\Seeder;

class SiteBrandingSeeder extends Seeder
{
    public function run(): void
    {
        SiteBranding::firstOrCreate([]);
    }
}
