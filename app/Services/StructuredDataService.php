<?php

namespace App\Services;

use App\Models\SiteBranding;

class StructuredDataService
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function organization(): array
    {
        $data = [
            '@type' => 'Organization',
            'name' => $this->settings->get('general.site_name', config('app.name', 'SOGRATE')),
        ];

        $email = $this->settings->get('contact.email');
        if ($email) {
            $data['email'] = $email;
        }

        $phone = $this->settings->get('contact.phone');
        if ($phone) {
            $data['telephone'] = $phone;
        }

        $sameAs = [];
        $facebook = $this->settings->get('social.facebook');
        if ($facebook) {
            $sameAs[] = $facebook;
        }
        $instagram = $this->settings->get('social.instagram');
        if ($instagram) {
            $sameAs[] = $instagram;
        }
        $youtube = $this->settings->get('social.youtube');
        if ($youtube) {
            $sameAs[] = $youtube;
        }
        if ($sameAs) {
            $data['sameAs'] = $sameAs;
        }

        $branding = SiteBranding::getOrCreateSafely();
        if ($branding && $branding->hasLogo()) {
            $data['logo'] = $branding->getLogoUrl();
        }

        $address = $this->settings->get('contact.address');
        if ($address) {
            $data['address'] = $address;
        }

        return $data;
    }

    public function website(): array
    {
        return [
            '@type' => 'WebSite',
            'name' => $this->settings->get('general.site_name', config('app.name', 'SOGRATE')),
            'url' => url('/'),
            'publisher' => [
                '@id' => url('/#organization'),
            ],
        ];
    }

    public function breadcrumbs(string $currentPage, ?string $parentPage = null): array
    {
        $items = [];

        $items[] = [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/'),
        ];

        $position = 2;

        if ($parentPage) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $parentPage,
            ];
            $position++;
        }

        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $currentPage,
        ];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    public function galleryBreadcrumbs(string $galleryTitle): array
    {
        return $this->breadcrumbs($galleryTitle, 'Gallery');
    }

    public function all(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $this->organization() + ['@id' => url('/#organization')],
                $this->website() + ['@id' => url('/#website')],
            ],
        ];
    }
}
