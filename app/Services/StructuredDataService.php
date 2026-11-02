<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteBranding;

class StructuredDataService
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings, private CheckoutAvailability $checkoutAvailability)
    {
        $this->settings = $settings;
    }

    public function organization(): array
    {
        $data = [
            '@type' => 'Organization',
            'name' => $this->settings->get('general.site_name', config('app.name', 'Sograte')),
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
            'name' => $this->settings->get('general.site_name', config('app.name', 'Sograte')),
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
                'name' => $this->settings->get('general.site_name', config('app.name', 'Sograte')),
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
        $variants = collect($variants);
        $description = $product->seoDescriptionText(500);
        $brand = [
            '@type' => 'Brand',
            'name' => $this->settings->get('general.site_name', config('app.name', 'Sograte')),
        ];
        $availability = $this->checkoutAvailability->enabled() && $product->isAvailable()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
        $primaryImage = $product->getPrimaryImage();
        $primaryImageUrl = $primaryImage?->getUrl('card');

        $variantOffers = $variants->map(function (ProductVariant $variant) use ($product, $availability, $primaryImageUrl) {
            return [
                '@type' => 'Product',
                'name' => $product->name.' - '.$variant->name,
                'sku' => $variant->sku,
                'image' => $variant->getFirstMediaUrl('image', 'card') ?: $primaryImageUrl,
                'offers' => [
                    '@type' => 'Offer',
                    'url' => route('shop.show', ['product' => $product->slug, 'variant' => $variant->id]),
                    'price' => number_format($variant->price / 100, 2, '.', ''),
                    'priceCurrency' => 'USD',
                    'availability' => $availability,
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'sku' => $variant->sku,
                ],
            ];
        })->all();

        if ($variants->count() > 1) {
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'ProductGroup',
                'name' => $product->name,
                'description' => $description,
                'url' => route('shop.show', ['product' => $product->slug]),
                'productGroupID' => $product->slug,
                'brand' => $brand,
                'variesBy' => 'https://schema.org/size',
                'hasVariant' => $variantOffers,
            ];

            if ($primaryImageUrl) {
                $data['image'] = $primaryImageUrl;
            }

            return $data;
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'url' => route('shop.show', ['product' => $product->slug]),
            'brand' => $brand,
        ];

        if ($primaryImageUrl) {
            $data['image'] = $primaryImageUrl;
        }

        if ($variantOffers) {
            $variant = $variants->first();
            $data['sku'] = $variant->sku;
            $data['offers'] = $variantOffers[0]['offers'];
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
