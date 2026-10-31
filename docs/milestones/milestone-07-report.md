# Milestone 07 Completion Report - Customer Accounts & Saved Addresses

**Project:** SOGRATE - BBQ & Beef Jerky  
**Date:** 2026-10-31  
**Status:** Ready for acceptance

## Customer Authentication Approach

- Reused the existing `User` model and Laravel `web` session guard for both customers and administrators.
- Added customer-facing authentication routes with distinct route names so administrator Fortify routes remain unchanged.
- Registration creates ordinary users with no roles or permissions.
- Customers cannot access administrative routes without the existing required permissions.
- Existing administrator login, password reset, roles, permissions, and 2FA remain intact.

## Registration, Login, Logout, and Recovery

- Added public `/register`, `/login`, and secure POST `/logout` routes.
- Registration validates input, enforces unique email addresses, hashes passwords, and authenticates the new customer.
- Login uses the existing guard, login throttling, session regeneration, and generic failure messaging.
- Logout invalidates the session and regenerates the CSRF token.
- Customer password recovery reuses Laravel's password broker with a SOGRATE-branded reset notification.
- Password reset requests return a generic response that does not reveal whether an email exists.
- Added email verification routes, resend support, and a SOGRATE-branded verification notification.

## Profile Management

- Added `/account` and `/account/profile`.
- Customers can update name, email, and password.
- Email and password changes require the current password.
- Email changes restart verification and send a new verification link.
- Role, permission, and security fields cannot be modified through profile requests.

## Address Model and CRUD

- Added `CustomerAddress` with recipient, company, address lines, city, state, postal code, country, phone, label, and default flags.
- Postal codes are stored as strings to preserve leading zeros.
- Added create, edit, delete, and listing pages under `/account/addresses`.
- The first address becomes default shipping and billing automatically.
- Setting a default clears that default type on the customer's other addresses.
- Deleting a default address promotes a remaining address predictably.
- Every address operation is scoped to the authenticated owner; cross-customer access returns 404.

## Cart Integration

- Customer registration and login use the existing login event/cart merge behavior.
- Guest carts merge into customer carts with existing variant consolidation and quantity limits.
- Consumed guest carts are removed and repeat login does not duplicate quantities.
- Guest shopping remains fully operational.

## Navigation and Styling

- Public header now shows Sign In/Create Account for guests and My Account/Sign Out for authenticated customers.
- Account dashboard links to profile, addresses, cart, and an order-history placeholder.
- Customer pages use the approved SOGRATE palette, Montserrat typography, Bootstrap forms/cards, and white main content.
- Order history is represented only as a placeholder; no Order models or fake purchases were created.

## Verification and Automated Tests

- Fresh migrations and seeders passed.
- Frontend asset build passed.
- Manually verified registration, customer login, account dashboard, profile page, address creation and listing, logout, guest redirect to customer login, and administrator login/dashboard.
- Full automated suite passed: **633 tests, 1,438 assertions**.
- Pint passed on touched PHP files.

## Blockers

None.

## Nonblocking Follow-Ups

- Checkout, orders, and real order history remain pending Milestone 08.
- Customer-specific 2FA was not introduced; administrator 2FA remains unchanged.
- Address data is ready for future shipping, local delivery, and pickup workflows.
- Marketing preferences, loyalty, and newsletter subscriptions remain out of scope.

## Git Checkpoint

No commit, tag, or push was performed. After acceptance, create checkpoint `sograte-m07` using the established workflow.

## Acceptance Recommendation

Milestone 07 meets its acceptance criteria. Recommend acceptance and authorization to begin Milestone 08.
