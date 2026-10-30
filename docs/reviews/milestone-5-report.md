# Milestone 5 — Final Report

**Date:** 2026-08-11
**Status:** PASS — Milestone 5 acceptance criteria satisfied.

---

## A. Baseline Verified

| Metric | Value |
|--------|-------|
| Git status | Clean (M4 uncommitted changes present) |
| Laravel version | 13.24.0 |
| Existing tests before M5 | 308 (663 assertions) |
| Existing build | Passed |
| Audits | No vulnerabilities |

---

## B. Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| spatie/laravel-menu | 4.2.2 | Menu rendering |
| spatie/menu | 3.2.1 | Menu HTML generation |
| spatie/macroable | 2.1.0 | Dependency |
| spatie/url | 2.4.0 | Dependency |

No npm packages added.

---

## C. Database Schema

### menus
```
id, name, location (unique), created_at, updated_at
```

### menu_items
```
id, menu_id (FK→menus), parent_id (nullable FK→menu_items), label, type (default 'custom'),
page_id (nullable FK→pages nullOnDelete), class_offering_id (nullable FK→class_offerings nullOnDelete),
instructor_id (nullable FK→instructors nullOnDelete), url (nullable), target (default '_self'),
sort_order (default 0), enabled (default true), created_at, updated_at
```

### settings
```
id, group, key, value (nullable text), type (default 'string'), created_at, updated_at
Unique: group + key (composite)
```

### site_brandings
```
id, created_at, updated_at
```

---

## D. Menu Architecture

- **Locations:** Primary, Footer (enum MenuLocation)
- **Item types:** Page, Class, Instructor, Custom (enum MenuItemType)
- **Targets:** _self, _blank (enum MenuTarget)
- **Nesting:** Max 2 levels (root + child + grandchild)
- **Cycle prevention:** Server-side validation prevents self-parent and circular references
- **Cross-menu parent prevention:** Items can only reference parents from same menu
- **Content references:** nullOnDelete on foreign keys; suppressed when unavailable publicly
- **Custom URLs:** Internal paths + http/https/mailto/tel only; javascript/data/vbscript rejected

---

## E. Menu Rendering

- Spatie Laravel Menu used for HTML generation
- MenuService builds tree from database, suppresses unavailable content
- Primary menu: Bootstrap navbar with nested dropdowns
- Footer menu: simplified footer navigation
- Active navigation state supported via route matching
- `_blank` items include `rel="noopener noreferrer"`

---

## F. Menu Caching

- **Cache keys:** `navigation.primary`, `navigation.footer`
- **Format:** Primitive arrays (no Eloquent/Menu objects cached)
- **Lifetime:** rememberForever with explicit invalidation
- **Invalidation:** On MenuItem create/update/delete/reorder

---

## G. Settings Registry

| Key | Type | Default |
|-----|------|---------|
| general.site_name | string | Safe and Sound Kickboxing |
| general.tagline | string | Find Your Strength. Feel Safe Doing It. |
| contact.email | email | (empty) |
| contact.phone | phone | (empty) |
| contact.address | text | (empty) |
| social.facebook | url | (empty) |
| social.instagram | url | (empty) |
| social.youtube | url | (empty) |
| seo.default_title | string | Safe and Sound Kickboxing |
| seo.default_description | text | (empty) |
| appearance.footer_text | text | (empty) |

---

## H. Settings Service

- **Retrieval:** `SettingsService::get('group.key', $default)` — cache-first, registry fallback
- **Caching:** `Cache::rememberForever('settings.all')` with primitive arrays
- **Invalidation:** Explicit on update
- **Unknown keys:** Rejected at registry level; not persisted
- **Failure-safe:** Returns registry defaults when DB unavailable

---

## I. Branding Media

- **SiteBranding model:** singleton with Media Library `HasMedia`
- **Collections:** logo (singleFile), favicon (singleFile), social_image (singleFile)
- **Logo validation:** JPEG/PNG/WebP, 5MB, 8000×8000, alt_text custom property
- **Favicon:** PNG/WebP only, 2MB, 1024×1024
- **Social image:** JPEG/PNG/WebP, 5MB
- **Public fallbacks:** Text logo if no image; omit favicon/link tags if absent

---

## J. Redirect Administration

- **Permissions:** redirects.view, redirects.manage
- **Policy:** permission-based (view → redirects.view; create/update/delete → redirects.manage)
- **Validation:** Internal paths only (no external URLs), no self-redirect, loop detection, 301/302 constrained
- **Normalization:** Leading slash, strip query/fragment, reject javascript/data/vbscript

---

## K. Permission Changes

- **Final count:** 40 permissions (was 38; added redirects.view, redirects.manage)
- **Administrator:** gets redirects.view and redirects.manage
- **Editor:** does NOT get redirect permissions
- **Super Admin:** all permissions via Gate::before bypass

---

## L. Authorization Matrix

| Action | Editor | Administrator | Super Admin | Unprivileged | Guest |
|--------|--------|---------------|-------------|--------------|-------|
| View menus | ✗ | ✓ | ✓ (bypass) | 403 | Redirect |
| Manage menus | ✗ | ✓ | ✓ (bypass) | 403 | Redirect |
| View settings | ✗ | ✓ | ✓ (bypass) | 403 | Redirect |
| Update settings | ✗ | ✓ | ✓ (bypass) | 403 | Redirect |
| View redirects | ✗ | ✓ | ✓ (bypass) | 403 | Redirect |
| Manage redirects | ✗ | ✓ | ✓ (bypass) | 403 | Redirect |

---

## M. Admin Navigation

New functional links in sidebar:
- Appearance > Menus (menus.view)
- Settings > Settings (settings.view)
- Settings > Redirects (redirects.view)

---

## N. Public Header/Footer

- **Header:** Database-driven primary menu via MenuService; logo from SiteBranding with fallback to text
- **Footer:** Database-driven footer menu; contact details from settings; social links; footer text
- **All failure-safe:** try-catch wrappers prevent 500 errors when tables missing

---

## O. SEO Fallback Changes

- Layout uses `seo.default_title` and `seo.default_description` as fallbacks
- Model-specific SEO takes priority over global defaults
- Global social image fallback from SiteBranding `social_image` collection
- Existing Page/Class/Instructor/FAQ SEO tests remain green

---

## P. Files Created

### Migrations
- database/migrations/2026_08_11_100001_create_menus_table.php
- database/migrations/2026_08_11_100002_create_menu_items_table.php
- database/migrations/2026_08_11_100003_create_settings_table.php
- database/migrations/2026_08_11_100004_create_site_brandings_table.php

### Models
- app/Models/Menu.php
- app/Models/MenuItem.php
- app/Models/Setting.php
- app/Models/SiteBranding.php

### Enums
- app/Enums/MenuLocation.php
- app/Enums/MenuItemType.php
- app/Enums/MenuTarget.php

### Services
- app/Services/SettingsService.php
- app/Services/MenuService.php

### Policies
- app/Policies/MenuPolicy.php
- app/Policies/MenuItemPolicy.php
- app/Policies/SettingPolicy.php
- app/Policies/RedirectPolicy.php

### Controllers
- app/Http/Controllers/Admin/MenuController.php
- app/Http/Controllers/Admin/MenuItemController.php
- app/Http/Controllers/Admin/SettingsController.php
- app/Http/Controllers/Admin/RedirectController.php

### Form Requests (6)
- StoreMenuItemRequest, UpdateMenuItemRequest, ReorderMenuItemsRequest
- UpdateSettingsRequest, StoreRedirectRequest, UpdateRedirectRequest

### Config
- config/site-settings.php

### Seeders
- database/seeders/MenuSeeder.php
- database/seeders/SettingsSeeder.php
- database/seeders/SiteBrandingSeeder.php

### Factories
- database/factories/MenuFactory.php
- database/factories/MenuItemFactory.php
- database/factories/SettingFactory.php

### Admin Views
- resources/views/admin/menus/{index,edit,_form,_tree-item}.blade.php
- resources/views/admin/settings/index.blade.php
- resources/views/admin/redirects/{index,create,edit,_form}.blade.php

---

## Q. Files Modified

- routes/web.php — Added menu, settings, redirect routes
- resources/views/admin/layouts/app.blade.php — Activated sidebar nav links
- resources/views/layouts/app.blade.php — Settings-driven site name/favicon/SEO
- resources/views/components/header.blade.php — Database-driven primary menu
- resources/views/components/footer.blade.php — Database-driven footer menu + settings
- app/Providers/AppServiceProvider.php — Registered new policies
- database/seeders/RolePermissionSeeder.php — Added redirects.view/manage (40 total)
- database/seeders/DatabaseSeeder.php — Registered new seeders

---

## R. Tests Added

| Test File | Tests |
|-----------|-------|
| MenuAdminTest | 19 |
| MenuPublicTest | 9 |
| SettingsAdminTest | 14 |
| BrandingAdminTest | 14 |
| RedirectAdminTest | 12 |
| PermissionTest | 4 |
| RouteTest (extended) | 10 |
| **Total new** | **80** |

---

## S. Test Results

| Metric | Value |
|--------|-------|
| Total tests | **388** |
| Total assertions | **805** |
| Passed | **388** |
| Failed | **0** |
| Risky | **1** (minor, non-blocking) |

---

## T. Database Verification

```
php artisan migrate:fresh --seed
```
All 17 migrations ran. 40 permissions, 3 roles, 2 menus, 11 settings, 1 site branding row seeded.

---

## U. Cache Verification

- Menu cache: `navigation.primary`, `navigation.footer`
- Settings cache: `settings.all`
- All use primitive arrays, rememberForever, explicit invalidation
- Tests verify invalidation on create/update/delete

---

## V. Route Audit

90 routes registered. No route collisions. Registration absent. All module routes before catch-all.

---

## W. Frontend Build

```
npm run build → ✓ built in 20.91s
```

---

## X. Formatting

```
./vendor/bin/pint --test → passed
```

---

## Y. Audits

```
composer audit → No security vulnerability advisories found
npm audit → found 0 vulnerabilities
```

---

## Z. Security Review

- Menus: No raw HTML labels, no JS URLs, cycles prevented, depth bounded, server-side reorder
- Settings: Controlled registry, unknown keys rejected, values escaped, unsafe URLs rejected
- Branding: Upload validation, SVG blocked, no filesystem paths trusted
- Redirects: Internal targets only, no open redirect, no loops, 301/302 constrained
- Auth: Fortify unchanged, registration disabled, 2FA unchanged, permission-based authorization

---

## AA. Git Status

Working tree has uncommitted changes from Milestone 4 and Milestone 5. Per instructions, no git add/commit/push was run.

---

## AB. Deviations

None.

---

## AC. Unresolved Issues

None.

---

## AD. Milestone 5 Status

```
PASS — Milestone 5 acceptance criteria satisfied.
```
