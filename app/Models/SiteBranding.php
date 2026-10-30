<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteBranding extends Model implements HasMedia
{
    use InteractsWithMedia;

    public static function getOrCreateSafely(): ?self
    {
        try {
            if (! Schema::hasTable('site_brandings')) {
                return null;
            }

            return static::firstOrCreate([], []);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function hasLogo(): bool
    {
        try {
            return $this->hasMedia('logo');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getLogoUrl(): string
    {
        try {
            return $this->getFirstMediaUrl('logo');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
