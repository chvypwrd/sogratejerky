# Milestone 3 — CMS Pages Implementation Report

**Date:** 2026-08-10
**Status:** READY FOR REVIEW — Do NOT commit until reviewed

---

## Quality Gate Summary

| Check | Result |
|-------|--------|
| Tests | **122/122 passed** (294 assertions) |
| Pint | **Passed** |
| Vite Build | **Passed** (17.8s) |

---

## What Was Implemented

### 1. Database Layer
- **`pages` table** — `database/migrations/2026_08_10_190001_create_pages_table.php`
  - Columns: id, author_id (FK→users), title, slug (unique), body, excerpt, featured_image, template, status, robots, meta_title, meta_description, og_image (json), published_at, timestamps, soft_deletes
  - Indexes: status, published_at, slug
- **`redirects` table** — `database/migrations/2026_08_10_190002_create_redirects_table.php`
  - Columns: id, from_path (unique), to_path, status_code, timestamps
- **`media` table** — `database/migrations/2026_08_10_190000_create_media_table.php` (from Spatie stub)
- **Enums:**
  - `App\Enums\PageStatus` — Draft, InReview, Published, Scheduled, Archived
  - `App\Enums\PageTemplate` — Default, FullWidth, Sidebar, Landing
  - `App\Enums\RobotsDirective` — IndexFollow, NoIndexFollow, IndexNoFollow, NoIndexNoFollow

### 2. Models
- **`App\Models\Page`** — implements `HasMedia`, uses `HasSlug`, `InteractsWithMedia`, `SoftDeletes`
  - Traits: HasFactory, HasSlug, InteractsWithMedia, SoftDeletes
  - Relationships: author() → BelongsTo User
  - Scopes: published(), upcoming(), forIndex()
  - Methods: publish(), unpublish(), isPublished(), isPublishedOrScheduled()
  - Accessors: status → PageStatus enum, template → PageTemplate enum, robots → RobotsDirective enum
  - Route key: slug
- **`App\Models\Redirect`** — static findForPath() method

### 3. Services
- **`App\Services\HtmlSanitizer`** — wraps HTMLPurifier (ezyang/htmlpurifier)
  - Uses `HTML.AllowedElements` + `HTML.AllowedAttributes` config (space-separated attribute syntax, comma-separated element syntax)
  - Clean allowlist: h1-h6, p, br, strong, em, b, i, u, s, sub, sup, a, ul, ol, li, blockquote, pre, code, table, thead, tbody, tfoot, tr, th, td, img, div, span, dl, dt, dd, abbr
  - Allowed attributes: href/title/target/rel on a, src/alt/title/width/height on img, colspan/rowspan/scope on th, colspan/rowspan on td, title on abbr, class on div/span
  - Removed: iframe, script, style, object, embed, form, input, mark, figure, figcaption
  - URI schemes: http, https, mailto only
  - CSS: text-align, margin-left/right, font-weight, font-style, color, background-color
- **`App\Services\PageSlugRedirectService`** — handles slug→redirect mapping
  - handleSlugChange() creates 301 redirect from old to new slug
  - resolve() looks up redirect for a given path
  - createRedirect(), deleteRedirect()

### 4. Policies
- **`App\Policies\PagePolicy`** — explicitly registered via `Gate::policy()` in AppServiceProvider
  - viewAny → can('pages.view')
  - view → can('pages.view') OR author
  - create → can('pages.create')
  - update → can('pages.update') OR author
  - delete → can('pages.delete') OR author
  - publish → can('pages.publish')
  - restore/forceDelete → always false (soft deletes only)

### 5. Controllers
- **`App\Http\Controllers\Admin\PageController`** — full CRUD + publish/unpublish
  - Uses `$this->authorize()` from `AuthorizesRequests` trait
  - Sanitizes body via HtmlSanitizer on store/update
  - Handles featured_media file upload via Spatie Media Library
  - Creates slug redirects on slug change
- **`App\Http\Controllers\PublicPageController`** — shows published pages by slug
  - Resolves redirects before showing page
- **`App\Http\Controllers\PreviewController`** — signed URL preview
  - generatePreviewUrl() creates signed, expiring (30 min) URL with cache-stored token
  - Route: `/admin/preview/{token}` with `signed` middleware

### 6. Form Requests
- **`StorePageRequest`** — validates title, body, excerpt, template, status, robots, meta fields, og_image, published_at, featured_media
- **`UpdatePageRequest`** — same validation, uses `update` policy ability

### 7. Routes (in `routes/web.php`)
```php
// Admin CRUD (auth middleware)
GET    /admin/pages            → admin.pages.index    (can:pages.view)
GET    /admin/pages/create     → admin.pages.create   (can:pages.create)
POST   /admin/pages            → admin.pages.store    (can:pages.create)
GET    /admin/pages/{page}/edit → admin.pages.edit
PUT    /admin/pages/{page}     → admin.pages.update
DELETE /admin/pages/{page}     → admin.pages.destroy
POST   /admin/pages/{page}/publish   → admin.pages.publish   (can:publish,page)
POST   /admin/pages/{page}/unpublish → admin.pages.unpublish (can:publish,page)

// Preview (signed)
GET    /admin/preview/{token}  → pages.preview (signed middleware)

// Public catch-all
GET    /{slug}                 → pages.show
  where: ^(?:admin|register|login|password|logout|reset-password|_ignition|sanctum|telescope).+$
```

### 8. Views
- **Admin:** index, create, edit, _form (shared partial with 2-column layout)
- **Public:** show.blade.php, preview.blade.php (with preview banner)
- **Layout:** Added `@yield('meta')` to `layouts/app.blade.php` for SEO output
- **Sidebar:** Pages link now active/highlighted when on admin.pages.* routes

### 9. Tests (65 new, 122 total)

| Test File | Tests | Coverage |
|-----------|-------|----------|
| HtmlSanitizerTest | 10 | Safe HTML, XSS removal, event handlers, empty/null, allowed/disallowed elements & attrs, nested |
| PageCrudTest | 13 | Auth check, permissions, CRUD ops, slug generation, body sanitization |
| PagePublicationTest | 9 | Draft/scheduled visibility, publish/unpublish methods, scopes |
| PageSlugRedirectTest | 7 | Slug change redirects, resolve, create, delete, public redirect, same-slug noop |
| PagePreviewTest | 6 | Valid/unsigned/expired/nonexistent URLs, draft preview, signed middleware |
| PageMediaTest | 5 | Featured media CRUD, count accuracy, upload on create/update |
| PageSeoTest | 8 | Title, meta description, robots, og:title/description/image, defaults |
| PageAuthorizationTest | 7 | Super Admin, Admin, Editor roles, author access, publish permission |

---

## Key Design Decisions

1. **HTMLPurifier config** — Uses separate `HTML.AllowedElements` and `HTML.AllowedAttributes` arrays (not the `HTML.Allowed` string format which has parsing issues with attribute specs)
2. **Policy registration** — Explicitly registered via `Gate::policy(Page::class, PagePolicy::class)` in AppServiceProvider (auto-discovery was unreliable)
3. **Gate ability naming** — Controller uses `$this->authorize('update', $page)` (standard policy method names), not `Gate::authorize('pages.update', $page)` (which looks for `pagesUpdate` on the policy)
4. **No responsive images** — Removed `withResponsiveImages()` from controller to avoid WidthCalculator binding issues
5. **Catch-all route** — Regex constraint excludes admin/auth paths to prevent 405 on POST /register
6. **Soft deletes** — Pages use SoftDeletes; delete test uses `assertSoftDeleted()`

---

## Items Not Yet Implemented (Deferred)

1. **Rich text editor** — Not yet installed (was step 13 in the prompt)
2. **Redirect admin UI** — Redirects are created automatically but no admin interface yet
3. **Media collection cleanup** — No scheduled job for orphaned media

---

## Files Created/Modified

### New Files
- `app/Enums/PageStatus.php`
- `app/Enums/PageTemplate.php`
- `app/Enums/RobotsDirective.php`
- `app/Models/Page.php`
- `app/Models/Redirect.php`
- `app/Services/HtmlSanitizer.php`
- `app/Services/PageSlugRedirectService.php`
- `app/Http/Controllers/Admin/PageController.php`
- `app/Http/Controllers/PublicPageController.php`
- `app/Http/Controllers/PreviewController.php`
- `app/Http/Requests/Admin/StorePageRequest.php`
- `app/Http/Requests/Admin/UpdatePageRequest.php`
- `app/Policies/PagePolicy.php`
- `config/media-library.php` (copied from vendor)
- `config/sluggable.php` (copied from vendor)
- `database/migrations/2026_08_10_190000_create_media_table.php`
- `database/migrations/2026_08_10_190001_create_pages_table.php`
- `database/migrations/2026_08_10_190002_create_redirects_table.php`
- `database/factories/PageFactory.php`
- `resources/views/admin/pages/index.blade.php`
- `resources/views/admin/pages/create.blade.php`
- `resources/views/admin/pages/edit.blade.php`
- `resources/views/admin/pages/_form.blade.php`
- `resources/views/pages/show.blade.php`
- `resources/views/pages/preview.blade.php`
- `tests/Feature/Cms/HtmlSanitizerTest.php`
- `tests/Feature/Cms/PageCrudTest.php`
- `tests/Feature/Cms/PagePublicationTest.php`
- `tests/Feature/Cms/PageSlugRedirectTest.php`
- `tests/Feature/Cms/PagePreviewTest.php`
- `tests/Feature/Cms/PageMediaTest.php`
- `tests/Feature/Cms/PageSeoTest.php`
- `tests/Feature/Cms/PageAuthorizationTest.php`

### Modified Files
- `app/Http/Controllers/Controller.php` — Added `AuthorizesRequests` trait
- `app/Providers/AppServiceProvider.php` — Added `Gate::policy()` registration
- `resources/views/admin/layouts/app.blade.php` — Pages nav link active state
- `resources/views/layouts/app.blade.php` — Added `@yield('meta')`
- `routes/web.php` — Added admin CRUD, preview, and catch-all routes
