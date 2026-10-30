<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'general.site_name' => 'SOGRATE',
            'general.tagline' => 'Texas Made. Seriously Good Jerky.',
            'contact.email' => '',
            'contact.phone' => '',
            'contact.address' => '',
            'contact.notification_email' => '',
            'social.facebook' => '',
            'social.instagram' => '',
            'social.youtube' => '',
            'seo.default_title' => 'SOGRATE | BBQ & Beef Jerky',
            'seo.default_description' => 'SOGRATE is a Texas-made BBQ and beef jerky brand. Seriously good jerky, made in Texas.',
            'seo.google_site_verification' => '',
            'appearance.footer_text' => 'Texas Made. Seriously Good Jerky.',
        ];

        foreach ($settings as $fullKey => $value) {
            $parts = explode('.', $fullKey);

            Setting::firstOrCreate(
                ['group' => $parts[0], 'key' => $parts[1]],
                ['value' => $value]
            );
        }
    }
}
