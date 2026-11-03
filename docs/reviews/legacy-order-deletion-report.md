# Legacy Order Deletion With Optional Test Override — Implementation Report

**Date:** 2026-11-02
**Status:** Implemented and verified locally. Not committed or deployed.

## Implementation Summary

Removed the `is_test_order=true` prerequisite from legacy order deletion while preserving local-only safety protections.

### Deletion paths

- **Delete Order** for explicitly classified test orders.
- **Legacy Test Order Deletion** for unclassified legacy orders.

Both paths require:

- `orders.refund` permission.
- Exact order-number confirmation.
- One order at a time.
- No Stripe API interaction.

### Legacy eligibility

- Inspects locally stored order, payment, and refund records.
- Automatically blocks orders with any locally recorded `stripe_livemode=true` payment.
- Allows deletion when local payment evidence is known test mode.
- Requires an explicit override and written reason when payment provenance is unknown.
- Displays a prominent warning when payment mode cannot be independently verified.
- Never silently classifies an ambiguous order as a test order.

### Database cleanup

Within a database transaction, deletion removes:

- Order items
- Payment refunds
- Payments
- Order activities
- Order record

Shared customer accounts, users, products, variants, and unrelated records are preserved.

### Audit trail

The `deleted_test_orders` record includes:

- Original order ID and number
- Deletion timestamp
- Administrator ID and name
- Original order total
- Record counts
- Verification basis
- Local payment evidence
- Legacy override reason when applicable
- PaymentIntent tombstones for late-webhook protection

### Removed obsolete workflow

Removed the Stripe-dependent cleanup service, cleanup routes, cleanup views, and cleanup-specific tests.

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

## Migration

Added to `deleted_test_orders`:

- `local_payment_evidence` JSON
- `override_reason` text

## Test Results

```text
Focused order/deletion/Stripe tests: 53 passed, 429 assertions
Full application suite: 788 passed, 2,388 assertions
Laravel Pint: passed
Blade compilation: passed
```

Coverage includes:

- Classified test-order deletion.
- Unclassified legacy deletion with local test-mode evidence.
- Ambiguous legacy deletion requiring override and reason.
- Live-mode payment rejection.
- Exact order-number confirmation.
- Complete dependent-record cleanup.
- Shared customer/product preservation.
- Audit evidence and override records.
- No Stripe API calls.
- Existing checkout, refund, fulfillment, reporting, and payment regressions.

PHPUnit reported 39 risky tests with no failures.

## Deployment Considerations

- Run the new migration before using legacy deletion.
- No Stripe configuration, checkout, refund, reconciliation, or webhook behavior changed.
- Deletion is local-only and never contacts Stripe.
- No bulk deletion or automatic cleanup is provided.
- No commit, push, or deployment performed.
