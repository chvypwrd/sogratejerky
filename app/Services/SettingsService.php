<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected string $cacheKey = 'settings.all';

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        $parts = explode('.', $key);
        $value = $all;

        foreach ($parts as $part) {
            if (! isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $registry = config('site-settings');
        $parts = explode('.', $key);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Setting key must be in 'group.key' format.");
        }

        [$group, $settingKey] = $parts;

        if (! isset($registry[$group][$settingKey])) {
            throw new \InvalidArgumentException("Setting '{$group}.{$settingKey}' is not registered.");
        }

        Setting::setValue($group, $settingKey, $value);

        Cache::forget($this->cacheKey);
    }

    public function group(string $group): array
    {
        $all = $this->all();

        return $all[$group] ?? [];
    }

    public function all(): array
    {
        return Cache::rememberForever($this->cacheKey, function () {
            $registry = config('site-settings');

            try {
                $dbSettings = Setting::pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                $dbSettings = [];
            }

            $result = [];

            foreach ($registry as $group => $settings) {
                $result[$group] = [];
                foreach ($settings as $key => $config) {
                    $result[$group][$key] = $dbSettings[$key] ?? $config['default'];
                }
            }

            return $result;
        });
    }

    public function invalidateCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
