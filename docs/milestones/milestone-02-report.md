# Milestone 02 Completion Report - Remove Classes and Instructors

**Project:** SOGRATE - BBQ & Beef Jerky  
**Date:** 2026-10-30  
**Status:** Ready for acceptance

## Scope Completed

Removed the legacy Classes and Instructors business modules while preserving the CMS, authentication, 2FA, authorization, menus, media, SEO, and production foundation. No ecommerce or blog features were added.

## Files and Modules Removed

- ClassOffering and Instructor models.
- Public and administrative controllers for Classes and Instructors.
- Store/Update request classes for Classes and Instructors.
- ClassOffering and Instructor policies and factories.
- Public Classes/Instructors views, admin views, cards, and related SCSS.
- Exclusive Classes/Instructors tests and module redirect tests.
- Classes/Instructors database migrations and menu item relationships.

## Database and Menu Changes

- Removed `class_offerings` and `instructors` tables by deleting their migrations and rebuilding the clean schema.
- Removed `class_offering_id` and `instructor_id` from `menu_items`.
- Removed eight `classes.*` / `instructors.*` permissions, leaving 36 permissions.
- Removed Classes and Instructors from admin navigation, menu item types, menu validation, URL resolution, seed data, and menu rendering.
- Preserved generic page, gallery, home, and custom menu items, including ordering and nesting.

## Homepage, Sitemap, and CMS

- Removed Classes/Instructors queries and homepage sections.
- Preserved testimonials, FAQs, shared layout, settings, and transitional SOGRATE content.
- Removed Classes/Instructors sitemap URL generation.
- Removed `classes` and `instructors` from the reserved CMS page slug list; those slugs can now resolve as normal CMS pages.
- Updated the development sample-content action to create SOGRATE pages, galleries, testimonials, and FAQs only.

## Verification Performed

| Check | Result |
|-------|--------|
| Fresh migrations and seeders | Passed |
| Obsolete tables present | 0 |
| Obsolete `menu_items` columns present | 0 |
| Obsolete permissions present | 0 |
| Obsolete named routes | Not registered |
| `/classes` and `/instructors` | 404 when no CMS page exists |
| Homepage | 200; no Classes/Instructors content |
| Representative CMS page | 200 |
| Admin login, dashboard, menus, settings | 200 |
| Removed admin classes route | 404 |
| Sitemap | 200; no obsolete URLs |
| Generic menu rendering | Passed |
| Frontend asset build | Passed |
| Pint on touched PHP files | Passed |
| Full automated suite | Passed: 572 tests, 1,193 assertions |

## Blockers

None.

## Nonblocking Follow-Ups

- The homepage remains visually transitional until Milestone 03.
- Orphaned source media files may remain in local, gitignored storage, but no database records reference them.
- No cleanup migration was added because this is a pre-launch project with no production data. The existing Milestone 01 development database was rebuilt with `migrate:fresh --seed`.

## Git Checkpoint

No commit, tag, or push was performed. After acceptance, create checkpoint `sograte-m02` using the established workflow.

## Acceptance Recommendation

Milestone 02 meets its acceptance criteria. Recommend acceptance and authorization to begin Milestone 03.
