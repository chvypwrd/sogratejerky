<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Enums\RobotsDirective;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'excerpt',
        'template',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots',
        'status',
        'published_at',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeForIndex(Builder $query): Builder
    {
        return $query->with('creator')->whereNull('deleted_at');
    }

    public function getStatusAttribute(): PageStatus
    {
        return PageStatus::from($this->attributes['status'] ?? PageStatus::Draft->value);
    }

    public function getTemplateAttribute(): PageTemplate
    {
        return PageTemplate::from($this->attributes['template'] ?? PageTemplate::Default->value);
    }

    public function getRobotsAttribute(): RobotsDirective
    {
        return RobotsDirective::from($this->attributes['robots'] ?? RobotsDirective::IndexFollow->value);
    }

    public function publish(): void
    {
        $this->update([
            'status' => PageStatus::Published,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => PageStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function isPublishedOrScheduled(): bool
    {
        return $this->status === PageStatus::Published
            && $this->published_at !== null;
    }
}
