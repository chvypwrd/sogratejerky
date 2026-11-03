# Production Test Order Classification Fix — Implementation Report

**Date:** 2026-11-02
**Status:** Implemented and verified locally. Not committed or deployed.

## Problem

Production checkout uses a restricted live Stripe credential, with a separate `STRIPE_TEST_SECRET` configured for historical test-mode verification.

The Mark as Test Order action could still use the normal live-key lookup for historical test-mode PaymentIntents, producing:

`No such payment_intent; a similar object exists in test mode, but a live mode key was used.`

## Root Cause

The test-order classification path did not reliably fall back from the live-key PaymentIntent lookup to the configured read-only test-mode credential. It also did not fully verify that the returned PaymentIntent matched the stored payment record.

## Correction

- Added shared verification logic in `StripeGateway`:
  - Attempts the normal live/current credential first.
  - Falls back to `STRIPE_TEST_SECRET` when the live lookup cannot retrieve the PaymentIntent.
  - Supports restricted `rk_test_` credentials with PaymentIntents Read permission.
- Reused the same verification path in:
  - Admin test-order classification.
  - Historical test-order cleanup preview/eligibility checks.
- Classification now requires:
  - Stripe PaymentIntent `livemode=false`.
  - Matching PaymentIntent ID.
  - Matching payment amount.
  - Matching payment currency.
  - Matching order metadata when present.
- Live-mode, mixed, mismatched, or ambiguous PaymentIntents are rejected.
- Failed verification returns an actionable validation message referencing `STRIPE_TEST_SECRET` and PaymentIntents Read permission.
- Normal checkout, refunds, reconciliation, webhooks, and `STRIPE_SECRET` usage remain unchanged.

## Files Modified

- `app/Http/Controllers/Admin/OrderController.php`
- `app/Services/StripeGateway.php`
- `app/Services/TestOrderCleanupService.php`
- `tests/Feature/Admin/TestOrderCleanupTest.php`
- `tests/Feature/Admin/TestOrderManagementTest.php`

## Regression Coverage

- Marking a historical test-mode order with the read-only test credential while the live gateway is active.
- Restricted `rk_test_` credential acceptance.
- PaymentIntent/payment record mismatch rejection.
- Actionable validation message when both live and test verification fail.
- Existing cleanup, printing, dashboard, and Stripe regression behavior.

## Test Results

```text
Focused order management/cleanup tests: 18 passed, 102 assertions
Full application suite: 789 passed, 2,349 assertions
Laravel Pint: passed
Blade compilation: passed
```

PHPUnit reported 39 risky tests with no failures.

## Deployment Notes

- No migrations required.
- No Stripe key, API version, webhook, checkout, or payment calculation changes.
- `STRIPE_SECRET` remains the live credential for normal operations.
- `STRIPE_TEST_SECRET` is used only for read-only historical test-mode verification.
- No commit, push, or deployment performed.
