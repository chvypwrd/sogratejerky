<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Product;
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

    public function blogPosting(BlogPost $post): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('blog.show', $post->slug),
            ],
            'url' => route('blog.show', $post->slug),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author_display_name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->settings->get('general.site_name', config('app.name', 'SOGRATE')),
                'url' => url('/'),
            ],
        ];

        $description = $post->seo_description ?: $post->excerpt ?: strip_tags((string) $post->content);
        if ($description) {
            $data['description'] = $description;
        }

        if ($post->published_at) {
            $data['datePublished'] = $post->published_at->toIso8601String();
        }

        if ($post->updated_at) {
            $data['dateModified'] = $post->updated_at->toIso8601String();
        }

        if ($post->hasMedia('featured')) {
            $data['image'] = $post->getFirstMediaUrl('featured', 'card');
        }

        return $data;
    }

    public function product(Product $product, iterable $variants): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'url' => route('shop.show', ['product' => $product->slug]),
        ];

        $description = $product->seo_description ?: $product->short_description ?: strip_tags((string) $product->description);
        if ($description) {
            $data['description'] = $description;
        }

        $image = $product->getPrimaryImage();
        if ($image) {
            $data['image'] = $image->getUrl('card');
        }

        $offers = [];
        foreach ($variants as $variant) {
            $offers[] = [
                '@type' => 'Offer',
                'url' => route('shop.show', ['product' => $product->slug, 'variant' => $variant->id]),
                'price' => number_format($variant->price / 100, 2, '.', ''),
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'sku' => $variant->sku,
                'name' => $variant->name,
            ];
        }

        if ($offers) {
            $data['offers'] = $offers;
        }

        return $data;
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
