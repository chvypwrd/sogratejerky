# Checkout Token Lifecycle and Completed Order Reuse Fix — Implementation Report

**Date:** 2026-11-01
**Status:** Implemented and verified locally. Not committed or deployed.

## Root Cause

`checkout_token` was generated when entering checkout and stored in the session, but was never rotated or invalidated after a completed payment. After payment, `CartService::consumeForOrder()` removed the ordered items but left the cart row and session token intact.

When a new item was added and checkout was submitted again, `CheckoutPaymentService::start()` found the previous paid or partially-refunded order by the still-current token and returned its confirmation URL instead of starting a new purchase.

The paid-order lookup was also not scoped to the current cart/customer, creating an exposure risk if a checkout token matched another order.

## Implementation

### Checkout identity rotation

- `CheckoutController` now rotates the checkout token when:
  - The existing order is terminal: Paid, Partially Refunded, Refunded, or Cancelled.
  - The existing order belongs to a different cart or customer.
  - The signed payment return confirms the order has completed.
- A rotated token is returned to the browser and written back to the checkout form so subsequent submissions use the new identity.

### Service-level safeguards

- `CheckoutPaymentService` rejects a token that belongs to another cart/customer.
- A terminal order is only returned for the same checkout attempt when the current cart contents match the order.
- Different cart contents cannot retrieve a previous paid order merely because the token matches.

### Preserved behavior

- Signed return and confirmation URLs remain order-number based and continue working.
- Existing paid orders remain accessible through their signed confirmation URLs.
- Stripe return URLs, webhook reconciliation, and Stripe event idempotency are unchanged.
- Legitimate pending/failed retries continue using the same order and PaymentIntent.
- Guest order access and customer order authorization remain unchanged.

### Session and cookie review

- The checkout token is stored only in the session, not in a cookie.
- Guest cart identity uses the existing HttpOnly, SameSite=Lax guest cookie plus a session fallback.
- New guest browser sessions receive distinct cart and checkout identities.
- No session or cookie configuration changes were required.

## Changed Files

- `app/Http/Controllers/CheckoutController.php` — token rotation and checkout-context handling.
- `app/Models/Order.php` — terminal-checkout and checkout-context helpers.
- `app/Services/CheckoutPaymentService.php` — paid-order context and cart-content safeguards.
- `resources/views/checkout/index.blade.php` — updates the hidden token after server-side rotation.
- `tests/Feature/Cart/CheckoutTest.php` — regression coverage for completed checkouts, partial refunds, guest sessions, retries, and cross-customer access.
- `tests/Feature/Payments/StripePaymentTest.php` — verifies token rotation on signed payment return.

## Test Results

```text
php artisan test tests/Feature/Cart tests/Feature/Payments/StripePaymentTest.php tests/Feature/Admin/OrderManagementTest.php
PASS — 57 tests, 417 assertions

vendor\bin\pint app\Models\Order.php app\Http\Controllers\CheckoutController.php app\Services\CheckoutPaymentService.php tests\Feature\Cart\CheckoutTest.php tests\Feature\Payments\StripePaymentTest.php
PASS

php artisan view:cache
PASS — Blade templates cached successfully
```

Coverage added:

- Successful paid checkout followed by a new purchase.
- Partially refunded order followed by a new purchase.
- Completed checkout token forced against different cart contents.
- New guest browser session receives a distinct checkout identity.
- One customer's checkout token cannot retrieve another customer's paid order.
- Direct payment-service rejection of a paid token with different cart contents.
- Existing duplicate-checkout idempotency for pending attempts.
- Signed payment return rotates the completed checkout token.
- Old signed confirmation remains viewable after a new order.

## Deployment Notes

- No migrations are required.
- No Stripe keys, API version, webhook subscriptions, pricing, tax, shipping, or fulfillment changes were made.
- Existing sessions holding a completed checkout token will rotate it on the next checkout page or submission.
- Existing signed confirmation URLs remain valid for their original seven-day lifetime.
- No commit, push, or deployment was performed.
