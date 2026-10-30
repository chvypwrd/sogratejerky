# Safe and Sound Kickboxing — Milestone 1 Final Report

## A. Environment Detected

| Tool | Version |
|------|---------|
| PHP | 8.3.32 |
| Composer | 2.8.10 |
| Node.js | v25.8.2 |
| npm | 11.11.1 |
| Git | 2.50.1.windows.1 |
| Laravel | 13.24.0 |

## B. Installation Summary

Laravel 13 was installed using the Laravel Installer (`laravel new`) into a temporary directory, then all files were moved to the project root. The installer created `laravel/laravel v13.8.0` which resolved to `laravel/framework v13.24.0`. The `composer.lock` and `package-lock.json` were both generated and are tracked. An application key was generated. The `.env` file was created from `.env.example`.

## C. Packages Added

### Composer (beyond fresh Laravel)
None. No additional Composer packages were installed.

### npm (beyond fresh Laravel)
| Package | Purpose |
|---------|---------|
| `bootstrap@^5.3` | CSS framework, installed from npm (not CDN) |
| `sass` | Sass compiler for Vite |

Removed: `tailwindcss`, `@tailwindcss/vite` (included in fresh Laravel defaults)

## D. Files Created

| File | Purpose |
|------|---------|
| `app/Http/Controllers/HomeController.php` | Homepage controller (invokable) |
| `resources/views/layouts/app.blade.php` | Base Blade layout with accessibility features |
| `resources/views/components/header.blade.php` | Header with responsive Bootstrap navbar |
| `resources/views/components/footer.blade.php` | Footer with dynamic copyright year |
| `resources/views/home.blade.php` | Homepage view |
| `resources/scss/app.scss` | Main Sass entry point |
| `resources/scss/abstracts/_variables.scss` | Design tokens / Sass variables |
| `resources/scss/base/_base.scss` | Base styles, skip-link, focus, reduced-motion |
| `resources/scss/base/_typography.scss` | Typography defaults |
| `resources/scss/components/_buttons.scss` | Brand button components |
| `resources/scss/layout/_header.scss` | Header/navigation styles |
| `resources/scss/layout/_footer.scss` | Footer styles |
| `tests/Feature/HomepageTest.php` | Homepage feature tests (4 tests) |
| `tests/Feature/ApplicationTest.php` | Application health tests (2 tests) |

## E. Files Modified

| File | Changes |
|------|---------|
| `vite.config.js` | Removed Tailwind plugin and fonts; updated input paths to `resources/scss/app.scss` |
| `resources/js/app.js` | Added Bootstrap JS import |
| `routes/web.php` | Replaced closure with `HomeController` |
| `.env` | Set app name, configured MySQL connection |
| `.env.example` | Set app name, configured MySQL defaults, removed SQLite as default |
| `README.md` | Replaced generic Laravel README with project-specific documentation |

## F. Frontend Foundation

- **Bootstrap 5.3**: Installed via npm, compiled through Vite
- **Vite**: Configured with `laravel-vite-plugin`; input: `resources/scss/app.scss` + `resources/js/app.js`
- **Sass**: Variables-first architecture so custom tokens override Bootstrap defaults
- **Blade Layout**: `layouts/app.blade.php` with `@yield('title')`, `@yield('content')`, Vite assets, skip-link
- **Responsive Navigation**: Bootstrap `navbar-expand-lg` with mobile toggler using `data-bs-toggle="collapse"`
- **Accessibility**: Skip-to-content link, `lang="en"`, semantic HTML5, `prefers-reduced-motion`, visible focus styles, heading hierarchy

## G. Database

- **Production config**: MySQL/MariaDB via `DB_CONNECTION=mysql` in `.env.example`
- **Test config**: SQLite in-memory (`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- **Migrations**: Only default Laravel framework migrations (users, cache, jobs)
- **Test isolation**: Tests cannot touch production/development database
- **Clean migration verified**: Default migrations run successfully in test environment

## H. Tests Added

| File | Tests | What it verifies |
|------|-------|-----------------|
| `tests/Feature/HomepageTest.php` | 4 | HTTP 200, contains site name, contains hero headline, uses layout |
| `tests/Feature/ApplicationTest.php` | 2 | App boots successfully, app name is configured |
| **Total** | **8 tests, 11 assertions** | |

## I. Verification Results

| Command | Result |
|---------|--------|
| `php artisan about` | Laravel 13.24.0, PHP 8.3.32, App: Safe and Sound Kickboxing |
| `php artisan route:list` | 4 routes: `GET /` (home), storage routes, `GET /up` |
| `php artisan test` | **8/8 tests passed, 11 assertions** |
| `./vendor/bin/pint --test` | **Passed** |
| `npm run build` | **Built successfully** (227.76 KB CSS, 80.37 KB JS) |
| `composer audit` | **No security vulnerability advisories found** |
| `npm audit` | **found 0 vulnerabilities** |

## J. Security Review

- `.env` is listed in `.gitignore` — not tracked
- `.env.example` contains only safe default values, no real credentials
- `APP_DEBUG=true` in `.env.example` — appropriate for local dev; production should set `false`
- CSRF middleware not disabled
- Framework security middleware intact
- No wildcard CORS configuration
- No raw SQL
- No unsafe file upload
- No public auth/registration
- No debug/test routes exposing sensitive info
- No `phpinfo()` route/file
- No unnecessary packages installed

## K. Git Status

- Repository initialized on `main` branch
- All files are untracked (no commits made yet, per instructions)
- `.gitignore` excludes: `.env`, `vendor/`, `node_modules/`, `public/build/`
- `.gitignore` retains: `composer.lock`, `package-lock.json`

## L. Deviations From Blueprint

None.

## M. Unresolved Issues

- **Sass deprecation warnings**: Bootstrap 5.3's Sass uses deprecated `@import`, `darken()`, `mix()`, and global built-in functions. These are upstream Bootstrap issues and do not affect functionality. They will be resolved when Bootstrap updates to Sass `@use`/`@forward` modules. The build completes successfully.
- **Database**: `php artisan migrate:fresh` was not run because no local MySQL instance was verified to be running. The default framework migrations are present and will work when a database is configured.
- **Git**: No commits made. The working tree is clean with all files untracked, ready for initial commit.

## N. Milestone 1 Status

**PASS — Milestone 1 acceptance criteria satisfied.**
