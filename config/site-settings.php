<?php

return [
    'general' => [
        'site_name' => ['type' => 'string', 'label' => 'Site Name', 'default' => 'SOGRATE', 'validation' => 'required|string|max:255'],
        'tagline' => ['type' => 'string', 'label' => 'Tagline', 'default' => 'Texas Made. Seriously Good Jerky.', 'validation' => 'nullable|string|max:255'],
    ],
    'contact' => [
        'email' => ['type' => 'email', 'label' => 'Email', 'default' => '', 'validation' => 'nullable|email|max:255'],
        'phone' => ['type' => 'phone', 'label' => 'Phone', 'default' => '', 'validation' => 'nullable|string|max:50'],
        'address' => ['type' => 'text', 'label' => 'Address', 'default' => '', 'validation' => 'nullable|string|max:1000'],
        'notification_email' => ['type' => 'email', 'label' => 'Notification Email', 'default' => '', 'validation' => 'nullable|email|max:255'],
    ],
    'social' => [
        'facebook' => ['type' => 'url', 'label' => 'Facebook URL', 'default' => '', 'validation' => 'nullable|url|max:2048'],
        'instagram' => ['type' => 'url', 'label' => 'Instagram URL', 'default' => '', 'validation' => 'nullable|url|max:2048'],
        'youtube' => ['type' => 'url', 'label' => 'YouTube URL', 'default' => '', 'validation' => 'nullable|url|max:2048'],
    ],
    'seo' => [
        'default_title' => ['type' => 'string', 'label' => 'Default Title', 'default' => 'SOGRATE | BBQ & Beef Jerky', 'validation' => 'nullable|string|max:255'],
        'default_description' => ['type' => 'text', 'label' => 'Default Description', 'default' => 'SOGRATE is a Texas-made BBQ and beef jerky brand. Seriously good jerky, made in Texas.', 'validation' => 'nullable|string|max:500'],
        'google_site_verification' => ['type' => 'string', 'label' => 'Google Site Verification', 'default' => '', 'validation' => 'nullable|string|max:255'],
    ],
    'appearance' => [
        'footer_text' => ['type' => 'text', 'label' => 'Footer Text', 'default' => '', 'validation' => 'nullable|string|max:1000'],
    ],
];
