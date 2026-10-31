<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'is_active',
        'is_featured',
        'sort_order',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('sitemap.xml'));
        static::deleted(fn () => Cache::forget('sitemap.xml'));
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function availableVariants(): HasMany
    {
        return $this->variants()->available()->ordered();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_product');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getStartingPriceAttribute(): ?int
    {
        $price = $this->availableVariants->min('price');

        return $price === null ? null : (int) $price;
    }

    public function getStartingPriceFormattedAttribute(): ?string
    {
        return $this->starting_price === null
            ? null
            : '$'.number_format($this->starting_price / 100, 2);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->availableVariants->isNotEmpty();
    }

    public function seoDescriptionText(int $limit = 160): string
    {
        $source = $this->seo_description
            ?: $this->short_description
            ?: strip_tags((string) $this->description);

        $source = trim(preg_replace('/\s+/', ' ', html_entity_decode((string) $source, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($source === '') {
            $source = 'Shop '.$this->name.' from SOGRATE, Texas-made beef jerky.';
        }

        return Str::limit($source, $limit);
    }

    public function getPrimaryImage(): ?Media
    {
        $primary = $this->getFirstMedia('primary_image');

        if ($primary) {
            return $primary;
        }

        $variant = $this->relationLoaded('availableVariants')
            ? $this->availableVariants->first()
            : $this->availableVariants()->first();

        return $variant?->getFirstMedia('image');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeCatalog(Builder $query): Builder
    {
        return $query->active()->whereHas('availableVariants');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('primary_image')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->nonQueued();

        $this->addMediaConversion('card')
            ->width(800)
            ->height(600)
            ->nonQueued();
    }
}
