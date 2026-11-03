# Administrative Order Deletion With Legacy Test Override — Implementation Report

**Date:** 2026-11-02
**Status:** Implemented and verified locally. Not committed or deployed.

## Implementation Summary

Replaced the Stripe-dependent cleanup workflow with local-only administrative order deletion.

### Deletion paths

1. **Delete Order** for orders explicitly marked `is_test_order`.
2. **Legacy Test Order Deletion** for unclassified legacy orders.

Both paths:

- Require `orders.refund` permission.
- Require the exact order number.
- Delete one order at a time.
- Redirect to the order list with a success message.
- Never contact Stripe or process refunds/cancellations.

### Legacy override

- The legacy path does not require `is_test_order=true`.
- It inspects locally stored payments and refunds.
- Deletion is automatically blocked when any payment has `stripe_livemode=true`.
- When payment mode is unknown, a prominent warning states that the application cannot independently verify the payment mode.
- Ambiguous legacy deletion requires:
  - An explicit override checkbox.
  - A written override reason.
  - The exact order number.
- Ambiguous orders are never automatically classified as test orders.

### Database cleanup

Within a database transaction, deletion removes:

- Order items
- Payment refunds
- Payments
- Order activities
- Order record

Shared customers, users, products, variants, and unrelated records are preserved.

### Audit trail

The `deleted_test_orders` record includes:

- Original order ID and order number
- Deletion timestamp
- Administrator ID and name
- Original order total
- Record counts
- Verification basis
- Local payment evidence
- Legacy override reason when applicable
- Locally stored PaymentIntent IDs for late-webhook tombstoning

### Removed obsolete workflow

Removed:

- `TestOrderCleanupService`
- Test-order cleanup routes
- Cleanup index and preview views
- Cleanup-specific test suite

Existing Stripe test-order classification remains unchanged.

## Files Changed

- `app/Http/Controllers/Admin/OrderController.php`
- `app/Models/DeletedTestOrder.php`
- `app/Services/OrderDeletionService.php` — new
- `app/Services/TestOrderCleanupService.php` — removed
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/orders/test-cleanup/` — removed
- `routes/web.php`
- `database/migrations/2026_11_02_000001_add_legacy_deletion_evidence_to_deleted_test_orders_table.php` — new
- `tests/Feature/Admin/OrderDeletionTest.php` — new
- `tests/Feature/Admin/TestOrderCleanupTest.php` — removed

## Migration Requirements

Added:

- `local_payment_evidence` JSON column
- `override_reason` text column

## Test Results

```text
Focused order/deletion/Stripe tests: 53 passed, 429 assertions
Full application suite: 788 passed, 2,388 assertions
Laravel Pint: passed
Blade compilation: passed
```

Coverage includes:

- Deletion of an already-classified test order.
- Deletion of an unclassified legacy order with known local test-mode evidence.
- Explicit override and reason for ambiguous legacy orders.
- Live-mode payment rejection.
- Exact order-number confirmation.
- Complete cleanup of dependent records.
- Shared customer/product preservation.
- Audit evidence and override records.
- No Stripe API calls.
- Existing checkout, refund, fulfillment, reporting, and payment regressions.

PHPUnit reported 39 risky tests with no failures.

## Deployment Considerations

- Run the new migration before using legacy deletion.
- No Stripe credentials, checkout, refund, reconciliation, or webhook behavior changed.
- Deletion is local-only and never contacts Stripe.
- Each deletion is individually confirmed.
- No bulk deletion or automatic cleanup is provided.
- No commit, push, or deployment performed.
