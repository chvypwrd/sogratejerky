# Milestone 06 Completion Report - Persistent Cart & Quick Order

**Project:** SOGRATE - BBQ & Beef Jerky  
**Date:** 2026-10-30  
**Status:** Ready for acceptance

## Cart Architecture

- Added database-backed `Cart` and `CartItem` models.
- Authenticated users have at most one cart via a unique user association.
- Guests receive an opaque 64-character token stored in an HTTP-only, SameSite=Lax cookie and a hashed database lookup value.
- Cart item uniqueness is enforced on `(cart_id, product_variant_id)`.
- Prices are always looked up from the current `ProductVariant.price` value.
- Quantities are positive integers capped by centralized cart configuration.

## Guest and Authenticated Persistence

- Guest carts persist across requests and browser sessions through a 30-day encrypted cookie.
- Authenticated carts associate with the existing user model without changing admin authentication.
- Cart ownership checks prevent access to another customer's cart items.
- Guest carts merge into the authenticated cart on login, preserving distinct variants and consolidating matching quantities.
- The consumed guest cart is deleted and its session/cookie token is cleared, making repeated login events idempotent.

## Cart UI and Product Integration

- Product detail pages now include variant selection, quantity, Add to Cart, and cart/Quick Order links.
- The shareable variant URL continues to control the selected variant.
- Added `/cart` with product/variant details, images, SKU, unit price, quantity controls, line subtotal, removal, clear-cart, subtotal, and total item count.
- Unavailable items remain visible with a warning and are excluded from the subtotal.
- Added cart item count to the public header.
- Checkout remains intentionally unimplemented and is explained on the cart page.

## Quick Order

- Added `/quick-order` as a stable guest-and-authenticated entry point suitable for future QR-code use.
- Displays active products and only active variants with SKU, weight, price, and quantity inputs.
- Submissions ignore zero quantities, validate all non-zero values, reject inactive or unknown variants, consolidate matching variants, and add all selected items atomically.
- Successful submission redirects to the cart; no checkout route was introduced.

## Navigation and Homepage

- Added Shop Jerky, Quick Order, and Cart to CMS-managed navigation.
- Converted the homepage Quick Order promotional area into a working Quick Order link.
- Preserved the CMS-managed menu architecture, SOGRATE styling, and white main-content backgrounds.

## Validation and Security

- Server-side quantity validation is applied to every add/update path.
- Accumulated quantities are checked against the configured maximum.
- CSRF protection remains active on all cart mutations.
- Guest cart tokens are never exposed in URLs.
- Cart ownership is enforced for updates and removals.
- Quick Order performs all availability checks before adding items and uses a transaction for batch additions.
- No client-supplied price is trusted.

## Verification and Automated Tests

- Fresh migrations and seeders passed.
- Frontend asset build passed.
- Manually verified adding a variant, cart persistence within a session, cart subtotal, Quick Order, header cart count, navigation links, and homepage Quick Order access.
- Full automated suite passed: **620 tests, 1,382 assertions**.
- Pint passed on touched PHP files.

## Blockers

None.

## Nonblocking Follow-Ups

- Checkout and order creation remain pending Milestone 08.
- Customer accounts remain pending Milestone 07.
- No abandoned-cart cleanup command was added; expired cookies simply no longer resolve to an existing cart.
- Cart item quantities and availability are recalculated at display time using current catalog data.

## Git Checkpoint

No commit, tag, or push was performed. After acceptance, create checkpoint `sograte-m06` using the established workflow.

## Acceptance Recommendation

Milestone 06 meets its acceptance criteria. Recommend acceptance and authorization to begin Milestone 07.
