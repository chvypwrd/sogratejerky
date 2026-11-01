# Post-M12 Pre-Production Polish Report

**Project:** SOGRATE - BBQ & Beef Jerky  
**Date:** 2026-10-31  
**Starting commit:** `3e842d3` (accepted checkpoint `sograte-m12`)
**Status:** Ready for acceptance  
**Public launch status:** **NO-GO** (unchanged)

## Changes Implemented

### Login Redirects

- Added `LoginRedirectService` for role-aware default destinations and safe intended redirect handling.
- Administrators with `dashboard.view` default to `/admin`; ordinary customers default to `/account`.
- Customer and Fortify login responses now share the same redirect logic.
- Authorized intended URLs are preserved through Laravel's existing `redirect()->intended()` flow.
- Unauthorized admin intended URLs for customers are discarded and fall back to `/account`.
- External/non-local intended URLs are rejected.
- Password-confirmation and two-factor completion responses use the same safe role-aware logic.
- Existing session regeneration, throttling, 2FA, and login behavior remain intact.

### FAQ Accordion

- All FAQ answers now start collapsed.
- All accordion items share `#faq-accordion` as the Bootstrap parent, so opening one closes the previously open item.
- Buttons retain accessible `aria-expanded`, `aria-controls`, and heading semantics.
- No additional JavaScript was introduced.

### Contact Form Email

- Confirmed contact submissions are stored durably before notification dispatch.
- Confirmed the existing `NewContactMessageNotification` is dispatched to a configurable recipient:
  `contact.notification_email`, then `contact.email`, then `mail.from.address`.
- Corrected the notification subject encoding to `New contact message - SOGRATE`.
- Preserved CSRF, validation, honeypot, timing, duplicate protection, and failure logging.
- Added focused `Notification::fake()` coverage for the configured recipient.

Actual external email delivery is not verified locally because production SMTP has not been configured. Production requires a working SMTP/API mail transport, verified sender, and SPF/DKIM/DMARC readiness.

### Seeding and Navigation

- Confirmed `DatabaseSeeder` creates About, policy drafts, starter FAQs, primary navigation, and footer navigation.
- About is published; Privacy, Terms, Shipping, and Refund pages remain drafts.
- Contact and FAQ reuse their existing public routes.
- Fixed `MenuSeeder` idempotency: menu seeding previously relied on globally unique generated slugs, which caused duplicate footer items on repeated runs. Menu lookups now use stable menu/page/URL/type identity.
- Fresh seed produces 5 CMS pages, 6 FAQs, and 24 menu items.
- Repeated seeding remains at 5 pages, 6 FAQs, and 24 menu items without duplicates.
- Existing page labels/content and publication states are not overwritten.
- `AdminUserSeeder` remains explicit-only and is not part of `DatabaseSeeder`.

## Verification

- Focused authentication tests pass for admin/customer defaults, protected-route intended URLs, unauthorized admin intended URLs, external URL rejection, and 2FA preservation.
- FAQ Bootstrap behavior was verified in a headless browser at mobile width: 5 items loaded collapsed; opening one expanded it; opening another closed the first; clicking the open item closed it.
- Contact notification dispatch and configurable recipient tests pass with `Notification::fake()`.
- Fresh `migrate:fresh --seed` and repeated `db:seed` passed with stable content/menu counts.
- Focused re-verification passed: **76 tests, 204 assertions**.
- Full test suite passed: **702 tests, 1,850 assertions**.
- Blade compilation passed.
- Frontend production build passed.
- Pint passed on touched PHP files.

## Remaining Production Configuration

- Configure production SMTP/API mail transport, verified sender, and DNS authentication.
- Configure `contact.notification_email` or `contact.email` in site settings.
- Verify real contact email delivery and queue processing.
- Remove temporary `ADMIN_SEED_*` values after administrator creation.
- Complete the existing launch gates: tax/regulatory approval, approved catalog, fulfillment rates/zones/pickup setup, published legal policies, Stripe test/live validation, production HTTPS/webhook configuration, and owner authorization.

## Acceptance

The four requested refinements are complete and verified. Recommend acceptance.

No deployment, live checkout, real payment, or additional Git commit/tag/push was performed.
