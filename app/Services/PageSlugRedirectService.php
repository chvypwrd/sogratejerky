<?php

namespace App\Services;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PageSlugRedirectService
{
    public function handleSlugChange(Model $page, string $oldSlug, string $prefix = ''): void
    {
        if ($oldSlug === $page->slug) {
            return;
        }

        if ($oldSlug === '' || $oldSlug === null) {
            return;
        }

        $oldPath = $this->normalizePath($prefix.$oldSlug);
        $newPath = $this->normalizePath($prefix.$page->slug);

        if ($oldPath === $newPath) {
            return;
        }

        DB::transaction(function () use ($oldPath, $newPath) {
            $existing = Redirect::where('old_path', $oldPath)->where('active', true)->first();

            if ($existing) {
                $existing->update(['new_path' => $newPath]);
            } else {
                Redirect::create([
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'status_code' => 301,
                    'active' => true,
                ]);
            }
        });
    }

    public function resolve(string $path): ?array
    {
        $path = $this->normalizePath($path);

        $redirect = Redirect::findForPath($path);

        if ($redirect === null) {
            return null;
        }

        return [
            'to' => $redirect->new_path,
            'status' => $redirect->status_code,
        ];
    }

    public function normalizePath(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        $parsed = parse_url($path);

        if ($parsed === false || isset($parsed['host'])) {
            return '/';
        }

        $path = $parsed['path'] ?? '/';

        return $path === '' ? '/' : $path;
    }

    public function createRedirect(string $from, string $to, int $statusCode = 301): Redirect
    {
        return Redirect::create([
            'old_path' => $this->normalizePath($from),
            'new_path' => $this->normalizePath($to),
            'status_code' => $statusCode,
            'active' => true,
        ]);
    }

    public function deleteRedirect(string $from): bool
    {
        $from = $this->normalizePath($from);

        return Redirect::where('old_path', $from)->update(['active' => false]) > 0;
    }
}
