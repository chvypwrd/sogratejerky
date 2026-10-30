<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Setting;
use App\Models\SiteBranding;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        $registry = config('site-settings');
        $settings = $this->settingsService->all();

        $branding = SiteBranding::firstOrCreate([], []);

        return view('admin.settings.index', compact('registry', 'settings', 'branding'));
    }

    public function populate(): RedirectResponse
    {
        if (app()->isProduction()) {
            abort(404);
        }

        $this->authorize('settings.update');

        // Create pages
        $pageData = [
            ['title' => 'About Us', 'slug' => 'about-us', 'body' => '<p>We are SOGRATE, a Texas-made BBQ and beef jerky brand.</p>', 'status' => 'published', 'template' => 'default', 'robots' => 'index,follow'],
            ['title' => 'Our Jerky', 'slug' => 'our-jerky', 'body' => '<p>SOGRATE beef jerky is made in Texas in small batches with serious flavor.</p>', 'status' => 'published', 'template' => 'default', 'robots' => 'index,follow'],
            ['title' => 'Pricing', 'slug' => 'pricing', 'body' => '<p>Pricing and availability information is coming soon.</p>', 'status' => 'draft', 'template' => 'default', 'robots' => 'index,follow'],
        ];
        foreach ($pageData as $d) {
            Page::create($d + ['created_by' => 1, 'updated_by' => 1, 'sort_order' => 0]);
        }

        // Create galleries
        $galData = [
            ['title' => 'Made in Texas', 'slug' => 'made-in-texas', 'description' => 'Photos from our Texas kitchen and production process.', 'active' => true],
            ['title' => 'Behind the Scenes', 'slug' => 'behind-the-scenes', 'description' => 'A look at how SOGRATE is made.', 'active' => true],
            ['title' => 'Events', 'slug' => 'events', 'description' => 'Highlights from recent events.', 'active' => true],
        ];
        foreach ($galData as $d) {
            Gallery::create($d + ['created_by' => 1, 'sort_order' => 0, 'robots' => 'index,follow']);
        }

        // Create testimonials
        $testData = [
            ['name' => 'Jessica M.', 'quote' => 'SOGRATE jerky is seriously good. I keep coming back for more.', 'attribution' => 'Customer since 2024', 'active' => true],
            ['name' => 'Amanda R.', 'quote' => 'The flavor is incredible. Best jerky I have had in a long time.', 'attribution' => 'Customer', 'active' => true],
            ['name' => 'Rachel K.', 'quote' => 'I ordered a bundle for a cookout and everyone asked where it came from.', 'attribution' => 'Customer since 2023', 'active' => true],
            ['name' => 'Danielle W.', 'quote' => 'Great flavor, great quality. I look forward to every order.', 'attribution' => 'Customer', 'active' => true],
            ['name' => 'Maria S.', 'quote' => 'You can taste the care that goes into every batch. Highly recommend!', 'attribution' => 'Customer since 2024', 'active' => true],
            ['name' => 'Taylor B.', 'quote' => 'Finally found a jerky that delivers real Texas flavor.', 'attribution' => 'Customer', 'active' => true],
        ];
        foreach ($testData as $d) {
            Testimonial::create($d + ['created_by' => 1, 'sort_order' => 0, 'featured' => false]);
        }

        // Create FAQs
        $faqData = [
            ['question' => 'Where is SOGRATE made?', 'answer' => '<p>SOGRATE is a Texas-made BBQ and beef jerky brand.</p>', 'category' => 'General', 'active' => true],
            ['question' => 'How should I store my jerky?', 'answer' => '<p>Keep it sealed and store it in a cool, dry place.</p>', 'category' => 'Storage', 'active' => true],
            ['question' => 'How can I contact SOGRATE?', 'answer' => '<p>Use the contact page to reach the SOGRATE team.</p>', 'category' => 'General', 'active' => true],
            ['question' => 'Do you offer gift bundles?', 'answer' => '<p>Gift options are coming soon. Contact us for current availability.</p>', 'category' => 'Gifts', 'active' => true],
            ['question' => 'Can I find SOGRATE locally?', 'answer' => '<p>Contact us for current availability in your area.</p>', 'category' => 'Availability', 'active' => true],
            ['question' => 'Are ingredients listed?', 'answer' => '<p>Ingredient details are provided on SOGRATE packaging.</p>', 'category' => 'Ingredients', 'active' => true],
        ];
        foreach ($faqData as $d) {
            Faq::create($d + ['created_by' => 1, 'sort_order' => 0, 'active' => true]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Sample content created: 3 pages, 3 galleries, 6 testimonials, 6 FAQs.');
    }

    public function reset(): RedirectResponse
    {
        if (app()->isProduction()) {
            abort(404);
        }

        $this->authorize('settings.update');

        \Artisan::call('migrate:fresh', ['--seed' => true]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@sogratejerky.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password1234'),
                'email_verified_at' => now(),
            ]
        );

        $role = Role::where('name', 'Super Admin')->first();
        if ($role && ! $admin->hasRole('Super Admin')) {
            $admin->assignRole($role);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Database reset and re-seeded. Login: admin@sogratejerky.test / password1234');
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $validated = $request->validated();
        $settingsData = $validated['settings'] ?? [];
        $registry = config('site-settings');

        $keyToGroup = [];
        foreach ($registry as $group => $settings) {
            foreach (array_keys($settings) as $key) {
                $keyToGroup[$key] = $group;
            }
        }

        foreach ($settingsData as $key => $value) {
            if (isset($keyToGroup[$key])) {
                $this->settingsService->set("{$keyToGroup[$key]}.{$key}", $value);
            }
        }

        $branding = SiteBranding::firstOrCreate([], []);

        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            ]);
            $branding->clearMediaCollection('logo');
            $branding->addMediaFromRequest('logo')
                ->toMediaCollection('logo');
        } elseif ($request->boolean('remove_logo')) {
            $branding->clearMediaCollection('logo');
        }

        if ($request->hasFile('favicon')) {
            $request->validate([
                'favicon' => ['required', 'image', 'mimes:jpeg,png,webp,ico', 'max:2048'],
            ]);
            $branding->clearMediaCollection('favicon');
            $branding->addMediaFromRequest('favicon')
                ->toMediaCollection('favicon');
        } elseif ($request->boolean('remove_favicon')) {
            $branding->clearMediaCollection('favicon');
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Settings updated successfully.');
    }
}
