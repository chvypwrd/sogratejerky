# Milestone 5 — Reconciliation Report

**Date:** 2026-08-11
**Status:** PASS — Milestone 5 reconciliation complete.

---

## A. Risky Test Resolution

**Why risky:** The original `test_cache_invalidated_on_item_changes` used `Cache::shouldReceive('forget')->atLeast(1)` which mocked the cache facade but made no meaningful assertions about actual cache behavior.

**Fix:** Replaced with 4 separate tests that prove real cache invalidation:
- `test_cache_invalidated_on_item_create` — builds cache, creates item via controller, confirms cache cleared, confirms new data returned
- `test_cache_invalidated_on_item_update` — builds cache, updates item, confirms cache cleared, confirms updated label
- `test_cache_invalidated_on_item_delete` — builds cache, deletes item, confirms cache cleared, confirms empty result
- `test_cache_invalidated_on_reorder` — builds cache, reorders items, confirms cache cleared, confirms new order

Also fixed `test_cache_invalidates_after_update` in SettingsAdminTest with the same real-cache pattern.

**Cache behavior now proven:** create/update/delete/reorder all invalidate, subsequent reads return fresh data.

---

## B. Menu Cache Verification

- **Keys:** `navigation.primary`, `navigation.footer`
- **Value format:** Primitive arrays (no Eloquent models, no Spatie objects, no closures)
- **Lifetime:** `Cache::rememberForever` with explicit invalidation
- **Invalidation:** On MenuItem create/update/delete/reorder via `MenuService::invalidateCache()`
- **Verified by:** 4 dedicated cache tests in MenuAdminTest + 9 public menu tests

---

## C. Settings Cache Verification

- **Key:** `settings.all`
- **Value format:** Primitive arrays (`['group' => ['key' => value]]`)
- **Registry defaults:** Used when DB has no stored value
- **Stored values:** Override defaults
- **Invalidation:** `Cache::forget('settings.all')` on `SettingsService::set()`
- **Unknown keys:** Rejected by registry validation, never persisted
- **Verified by:** `test_cache_invalidates_after_update`, `test_values_persist`, `test_seeder_idempotent`, `test_unknown_setting_key_rejected`

---

## D. Test Results

| Metric | Value |
|--------|-------|
| Tests | **391** |
| Assertions | **827** |
| Passed | **391** |
| Failed | **0** |
| Errors | **0** |
| Risky | **0** |
| Skipped | **0** |

---

## E. Database Verification

```
php artisan migrate:fresh --seed
```
- 17 migrations succeeded
- 40 permissions seeded
- 3 roles seeded
- Primary menu created
- Footer menu created
- 11 settings created
- 1 SiteBranding row created
- No passkeys table
- No credentialed admin seeded
- Repeated seeding verified idempotent

---

## F. Authorization Regression

| Module | Editor | Administrator | Super Admin |
|--------|--------|---------------|-------------|
| Menus | Denied | View/Manage | Bypass |
| Settings | Denied | View/Update | Bypass |
| Redirects | Denied | View/Manage | Bypass |

All permissions enforced. No author bypass. Super Admin centralized via `Gate::before()`.

---

## G. Security Regression

- **Menus:** No raw HTML, no JS URLs, cycles prevented, depth bounded, cross-menu parent prevented, labels escaped, `_blank` safe rel, unavailable content hidden
- **Settings:** Controlled registry, unknown keys rejected, values escaped, unsafe URLs rejected
- **Branding:** JPEG/PNG/WebP only, SVG rejected, upload limits enforced, alt text preserved, unauthorized modification rejected
- **Redirects:** Internal only, self-redirect rejected, loops rejected, 301/302 constrained, inactive not resolved
- **Auth:** Fortify unchanged, registration absent, 2FA unchanged, permission-based

---

## H. Route Audit

- **Total routes:** 90
- **Collisions:** None
- **Registration:** Absent
- **CMS catch-all:** Properly excludes reserved paths
- **All admin routes:** pages, classes, instructors, testimonials, faqs, menus, settings, redirects, security
- **All public routes:** /, /classes, /classes/{slug}, /instructors, /instructors/{slug}, /faq, /{slug}

---

## I. Build

```
npm run build → ✓ built in 29.47s
```

---

## J. Formatting

```
./vendor/bin/pint --test → passed
```

---

## K. Audits

```
composer audit → No security vulnerability advisories found
npm audit → found 0 vulnerabilities
```

---

## L. Git Status

- **Branch:** main
- **Milestone 4:** Not yet committed (M3 commit `784cca7` is latest)
- **Modified files:** 13 (AppServiceProvider, seeders, views, routes, tests)
- **Untracked files:** 91 (M4 + M5 models, controllers, views, migrations, tests, seeders, config, factories, docs)
- **Staged files:** None
- **Working tree:** Contains uncommitted M4 and M5 changes

---

## M. Files Changed During Reconciliation

- `tests/Feature/Cms/MenuAdminTest.php` — Replaced risky cache test with 4 meaningful cache tests
- `tests/Feature/Cms/SettingsAdminTest.php` — Replaced risky cache test with meaningful cache test
- `app/Services/SettingsService.php` — Added try-catch for missing table
- `app/Services/MenuService.php` — Added try-catch for missing table, Pint fixes
- `app/Models/SiteBranding.php` — Added `getOrCreateSafely()`, `hasLogo()`, `getLogoUrl()` safe methods
- `resources/views/layouts/app.blade.php` — Use `getOrCreateSafely()`, null-safe favicon
- `resources/views/components/header.blade.php` — Try-catch wrappers for settings/menu/branding
- `resources/views/components/footer.blade.php` — Try-catch wrappers for settings/menu
- `tests/Feature/Authorization/RolePermissionTest.php` — Updated permission count to 40

---

## N. Deviations

None.

---

## O. Unresolved Issues

None.

---

## P. Status

```
PASS — Milestone 5 reconciliation complete.
```
