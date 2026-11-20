<?php

namespace App\Support;

class CanonicalUrl
{
    public static function base(): string
    {
        $configured = trim((string) config('app.url'));

        return rtrim($configured !== '' ? $configured : url('/'), '/');
    }

    public static function to(string $path): string
    {
        return self::base().'/'.ltrim($path, '/');
    }

    public static function route(string $name, mixed $parameters = []): string
    {
        return self::to(route($name, $parameters, false));
    }
}
