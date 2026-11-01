<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginRedirectService
{
    public function response(Request $request): RedirectResponse
    {
        $user = $request->user() ?? Auth::user();

        if (! $user instanceof User) {
            return redirect()->route('home');
        }

        $default = $this->defaultDestination($user);
        $intended = $request->session()->get('url.intended');

        if (is_string($intended) && $intended !== '' && ! $this->isSafeAuthorizedIntended($request, $user, $intended)) {
            $request->session()->forget('url.intended');

            return redirect()->to($default);
        }

        return redirect()->intended($default);
    }

    public function defaultDestination(User $user): string
    {
        return $this->canUseAdmin($user)
            ? route('admin.dashboard')
            : route('account.dashboard');
    }

    private function canUseAdmin(User $user): bool
    {
        return $user->can('dashboard.view');
    }

    private function isSafeAuthorizedIntended(Request $request, User $user, string $intended): bool
    {
        $parts = parse_url($intended);

        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = isset($parts['host']) ? strtolower($parts['host']) : null;
        $requestHost = strtolower($request->getHost());
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($host && $host !== $requestHost && $host !== $appHost) {
            return false;
        }

        $path = $parts['path'] ?? '';

        if ($path === '' || ! str_starts_with($path, '/')) {
            return false;
        }

        if (($path === '/admin' || str_starts_with($path, '/admin/')) && ! $this->canUseAdmin($user)) {
            return false;
        }

        return true;
    }
}
