<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Setting;
use App\Policies\BlogPostPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\MenuItemPolicy;
use App\Policies\MenuPolicy;
use App\Policies\PagePolicy;
use App\Policies\RedirectPolicy;
use App\Policies\SettingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerRateLimiters();

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            return null;
        });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(BlogPost::class, BlogPostPolicy::class);
        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::policy(MenuItem::class, MenuItemPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Redirect::class, RedirectPolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('blog-comments', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
