<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\ClassOffering;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Instructor;
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
            ['title' => 'Our Classes', 'slug' => 'our-classes', 'body' => '<p>Explore our range of kickboxing classes designed for all skill levels.</p>', 'status' => 'published', 'template' => 'default', 'robots' => 'index,follow'],
            ['title' => 'Pricing', 'slug' => 'pricing', 'body' => '<p>Affordable membership plans for every budget.</p>', 'status' => 'draft', 'template' => 'default', 'robots' => 'index,follow'],
        ];
        foreach ($pageData as $d) {
            Page::create($d + ['created_by' => 1, 'updated_by' => 1, 'sort_order' => 0]);
        }

        // Create class offerings
        $classData = [
            ['title' => 'Beginner Kickboxing', 'slug' => 'beginner-kickboxing', 'short_description' => 'Perfect for newcomers', 'description' => '<p>Learn the fundamentals in a supportive environment.</p>', 'level' => 'Beginner', 'duration' => '60 min', 'active' => true],
            ['title' => 'Advanced Striking', 'slug' => 'advanced-striking', 'short_description' => 'Take your skills further', 'description' => '<p>Advanced techniques for experienced practitioners.</p>', 'level' => 'Advanced', 'duration' => '90 min', 'active' => true],
            ['title' => 'Self-Defense Workshop', 'slug' => 'self-defense-workshop', 'short_description' => 'Practical self-defense skills', 'description' => '<p>Learn practical self-defense in a safe space.</p>', 'level' => 'All Levels', 'duration' => '120 min', 'active' => true],
        ];
        foreach ($classData as $d) {
            ClassOffering::create($d + ['created_by' => 1, 'sort_order' => 0, 'robots' => 'index,follow']);
        }

        // Create instructors
        $instData = [
            ['name' => 'Sarah Johnson', 'slug' => 'sarah-johnson', 'title' => 'Head Coach', 'short_bio' => '10+ years of kickboxing experience.', 'bio' => '<p>Sarah is a certified kickboxing instructor passionate about women\'s fitness.</p>', 'active' => true],
            ['name' => 'Mike Chen', 'slug' => 'mike-chen', 'title' => 'Senior Instructor', 'short_bio' => 'Former competitive fighter.', 'bio' => '<p>Mike brings intensity and technique to every class.</p>', 'active' => true],
            ['name' => 'Lisa Park', 'slug' => 'lisa-park', 'title' => 'Coach', 'short_bio' => 'Specializes in beginner classes.', 'bio' => '<p>Lisa creates a welcoming environment for new students.</p>', 'active' => true],
        ];
        foreach ($instData as $d) {
            Instructor::create($d + ['created_by' => 1, 'sort_order' => 0, 'robots' => 'index,follow']);
        }

        // Create galleries
        $galData = [
            ['title' => 'Class Photos', 'slug' => 'class-photos', 'description' => 'Photos from our classes and events.', 'active' => true],
            ['title' => 'Facility Tour', 'slug' => 'facility-tour', 'description' => 'Take a look at our training space.', 'active' => true],
            ['title' => 'Events', 'slug' => 'events', 'description' => 'Highlights from recent events.', 'active' => true],
        ];
        foreach ($galData as $d) {
            Gallery::create($d + ['created_by' => 1, 'sort_order' => 0, 'robots' => 'index,follow']);
        }

        // Create testimonials
        $testData = [
            ['name' => 'Jessica M.', 'quote' => 'SOGRATE jerky is seriously good. I keep coming back for more.', 'attribution' => 'Customer since 2024', 'active' => true],
            ['name' => 'Amanda R.', 'quote' => 'The instructors are incredibly supportive. Best decision I ever made.', 'attribution' => 'Student', 'active' => true],
            ['name' => 'Rachel K.', 'quote' => 'I was nervous about starting kickboxing, but the welcoming environment made it easy.', 'attribution' => 'Member since 2023', 'active' => true],
            ['name' => 'Danielle W.', 'quote' => 'Great classes, great people. I look forward to every session.', 'attribution' => 'Student', 'active' => true],
            ['name' => 'Maria S.', 'quote' => 'This gym truly cares about its members. Highly recommend!', 'attribution' => 'Member since 2024', 'active' => true],
            ['name' => 'Taylor B.', 'quote' => 'Finally found a place where I feel safe and challenged.', 'attribution' => 'Student', 'active' => true],
        ];
        foreach ($testData as $d) {
            Testimonial::create($d + ['created_by' => 1, 'sort_order' => 0, 'featured' => false]);
        }

        // Create FAQs
        $faqData = [
            ['question' => 'Do I need experience to join?', 'answer' => '<p>No experience is needed! Our beginner classes are designed for all fitness levels.</p>', 'category' => 'Classes', 'active' => true],
            ['question' => 'What should I bring to my first class?', 'answer' => '<p>Just bring water, comfortable workout clothes, and a positive attitude. We provide gloves and wraps.</p>', 'category' => 'Classes', 'active' => true],
            ['question' => 'How much does a membership cost?', 'answer' => '<p>We offer flexible membership plans starting at $49/month. Visit our pricing page for details.</p>', 'category' => 'Pricing', 'active' => true],
            ['question' => 'Where is SOGRATE made?', 'answer' => '<p>SOGRATE is a Texas-made BBQ and beef jerky brand.</p>', 'category' => 'General', 'active' => true],
            ['question' => 'What are the class hours?', 'answer' => '<p>Classes run Monday through Saturday, 6am-8pm. Check our schedule for specific class times.</p>', 'category' => 'Schedule', 'active' => true],
            ['question' => 'Do I need my own equipment?', 'answer' => '<p>We provide all necessary equipment including gloves and wraps for your first class.</p>', 'category' => 'Equipment', 'active' => true],
            ['question' => 'Can I try a class before joining?', 'answer' => '<p>Absolutely! We offer a free trial class for all new members.</p>', 'category' => 'General', 'active' => true],
            ['question' => 'Is parking available?', 'answer' => '<p>Yes, we have free parking available for all members during class hours.</p>', 'category' => 'General', 'active' => true],
        ];
        foreach ($faqData as $d) {
            Faq::create($d + ['created_by' => 1, 'sort_order' => 0, 'active' => true]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Sample content created: 3 pages, 3 classes, 3 instructors, 3 galleries, 6 testimonials, 8 FAQs.');
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
