# Milestone 02 Removal Inventory - Classes and Instructors

This inventory identifies the retained Safe and Sound legacy business modules that Milestone 02 should remove. Do not remove them in Milestone 01.

## Database

- Drop tables: `class_offerings`, `instructors`.
- Remove `menu_items.class_offering_id` and `menu_items.instructor_id` foreign keys and columns.
- Delete `menu_items` rows whose `type` is `class` or `instructor`, plus related media records and stored files.
- Remove `classes.*` and `instructors.*` permissions and role assignments.
- Remove legacy sample data created by the development populate action.

## Models and Policies

- `app/Models/ClassOffering.php`
- `app/Models/Instructor.php`
- `app/Policies/ClassOfferingPolicy.php`
- `app/Policies/InstructorPolicy.php`

## Controllers, Requests, and Enums

- `app/Http/Controllers/ClassOfferingController.php`
- `app/Http/Controllers/InstructorController.php`
- `app/Http/Controllers/Admin/ClassOfferingController.php`
- `app/Http/Controllers/Admin/InstructorController.php`
- `app/Http/Requests/Admin/StoreClassOfferingRequest.php`
- `app/Http/Requests/Admin/UpdateClassOfferingRequest.php`
- `app/Http/Requests/Admin/StoreInstructorRequest.php`
- `app/Http/Requests/Admin/UpdateInstructorRequest.php`
- Class and Instructor values in `app/Enums/MenuItemType.php`

## Routes

- Public: `/classes`, `/classes/{slug}`, `/instructors`, `/instructors/{slug}`.
- Admin: `/admin/classes/*`, `/admin/instructors/*`.
- Remove related names from the public page slug exclusion pattern.
- update sitemap generation and any route-name references.

## Views and Assets

- `resources/views/classes/`
- `resources/views/instructors/`
- `resources/views/components/class-card.blade.php`
- `resources/views/components/instructor-card.blade.php`
- `resources/views/admin/classes/`
- `resources/views/admin/instructors/`
- Classes/Instructors entries in `resources/views/admin/menus/_form.blade.php`.

## Admin and Seed Data

- Remove Classes and Instructors entries from `resources/views/admin/layouts/app.blade.php`.
- Remove `classes.*` and `instructors.*` from `database/seeders/RolePermissionSeeder.php`.
- Remove Classes and Instructors menu items from `database/seeders/MenuSeeder.php`.
- Remove ClassOffering and Instructor sample records from `Admin\SettingsController::populate()`.
- Remove `ClassOfferingFactory` and `InstructorFactory`.

## Cross-Cutting References

- `app/Http/Controllers/HomeController.php`
- `resources/views/home.blade.php`
- `app/Services/MenuService.php`
- `app/Services/SitemapService.php`
- `app/Models/MenuItem.php`
- `database/migrations/2026_08_11_100002_create_menu_items_table.php` for columns
- Tests under `tests/Feature/Cms/Class*`, `tests/Feature/Cms/Instructor*`, and `tests/Feature/Public/ClassesThemeTest.php`, `InstructorsThemeTest.php`; update homepage, menu, sitemap, and route tests that reference these modules.

## Retention Note

Pages, testimonials, FAQs, galleries, contact, menus, settings, redirects, SEO, media, authentication, 2FA, roles, and security tooling remain part of the SOGRATE foundation.
