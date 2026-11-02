# Safe Historical Test Order Cleanup — Implementation Report

**Date:** 2026-11-01
**Status:** Implemented and verified locally. Not committed or deployed.

## Implementation Summary

### Test-mode verification

- Added `payments.stripe_livemode` to cache Stripe PaymentIntent mode locally.
- New payments store `livemode` during PaymentIntent creation and reconciliation.
- Test-order marking records verified test-mode evidence on payment records.
- Historical payments without local livemode evidence can be verified through a read-only Stripe API lookup.
- Added optional `STRIPE_TEST_SECRET` support for read-only verification of historical test-mode PaymentIntents without changing the production checkout key.
- Deletion is refused for:
  - Live-payment orders.
  - Mixed test/live payment orders.
  - Orders without Stripe payment records.
  - Missing or inconclusive livemode evidence.
  - PaymentIntents already recorded as deleted.

### Admin cleanup action

- Added **Orders → Test Order Cleanup** dry-run page.
- Lists classified test orders, eligibility, verification basis, and affected record counts.
- Added **Delete Test Order** to eligible test-order detail pages.
- Permanent deletion requires typing the exact order number.
- The action is restricted to administrators with `orders.refund` permission.
- Deletion never calls Stripe refund or cancellation APIs.

### Database cleanup

Deletes only records owned by the verified test order:

- Order items
- Payments
- Payment refunds
- Order activities
- Order record

Preserves:

- Shared users/customers
- Products and variants
- Carts
- Global Stripe event records
- Unrelated financial data

The deletion runs in a database transaction with row locking.

### Audit and webhook safety

- Added `deleted_test_orders` audit records containing:
  - Original order ID and order number
  - Original total and placed date
  - Verification basis
  - Record counts
  - Operator ID/name
  - Deletion timestamp
- Added `deleted_test_order_payment_intents` tombstones for deleted PaymentIntent IDs.
- Late test-mode webhooks for deleted PaymentIntents are marked processed and ignored.
- Late webhooks cannot recreate deleted orders or modify live orders.

### Reporting and customer history

Deleted test orders are removed from:

- Admin order listings
- Customer order history
- Reorder actions
- Dashboard revenue and sales trends
- Best-selling product reports
- Fulfillment queues

## Migrations

Added and applied locally:

- `database/migrations/2026_11_01_000006_add_stripe_livemode_to_payments_table.php`
- `database/migrations/2026_11_01_000007_create_deleted_test_order_audit_tables.php`

## Files Modified

- `app/Http/Controllers/Admin/OrderController.php`
- `app/Models/Payment.php`
- `app/Models/DeletedTestOrder.php` — new
- `app/Models/DeletedTestOrderPaymentIntent.php` — new
- `app/Services/CheckoutPaymentService.php`
- `app/Services/PaymentReconciler.php`
- `app/Services/StripeGateway.php`
- `app/Services/TestOrderCleanupService.php` — new
- `config/services.php`
- `.env.example`
- `.env.production.example`
- `routes/web.php`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/orders/test-cleanup/index.blade.php` — new
- `resources/views/admin/orders/test-cleanup/preview.blade.php` — new
- `tests/Support/FakeStripeGateway.php`
- `tests/Feature/Admin/TestOrderCleanupTest.php` — new

## Test Results

```text
Focused cleanup/order/dashboard/print tests: 22 passed, 150 assertions
Full application suite: 781 passed, 2,316 assertions
Laravel Pint: passed
Blade compilation: passed
```

PHPUnit reported 39 risky tests with no failures.

## Safe Production Rollout Procedure

1. Back up the production database.
2. Deploy the code.
3. Run `php artisan migrate --force`.
4. If historical test orders lack local livemode evidence, configure a test-mode secret key:
   - `STRIPE_TEST_SECRET=sk_test_...`
   - This is used only for read-only verification and must not replace the production checkout `STRIPE_SECRET`, `STRIPE_KEY`, or webhook configuration.
5. Rebuild configuration:
   - `php artisan config:clear`
   - `php artisan config:cache`
6. Open **Admin → Orders → Test Order Cleanup** and review the dry-run eligibility list and affected counts.
7. Delete a single test order first and verify:
   - Order and dependent records are gone.
   - Shared customer/product records remain.
   - Audit record exists.
   - No Stripe refund or cancellation occurred.
8. Continue with additional individual cleanup only if verified.
9. Remove `STRIPE_TEST_SECRET` after cleanup if it is no longer needed.
10. Do not run bulk deletion. Each deletion requires exact order-number confirmation.
11. Stripe test-mode payment and refund objects remain in Stripe; only local SOGRATE database records are removed.

No commit, push, or deployment performed.
