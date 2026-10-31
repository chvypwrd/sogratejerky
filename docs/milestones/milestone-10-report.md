# Milestone 10 Completion Report - Stripe Payment Integration

**Project:** SOGRATE - BBQ & Beef Jerky  
**Date:** 2026-10-31  
**Status:** Ready for acceptance, with live Stripe test-mode validation still required before launch

## Stripe Integration Approach

- Installed the official `stripe/stripe-php` SDK (`^22.1`, installed v22.1.0).
- Configured Stripe through `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, and an explicit API version (`2026-10-28.endive`, matching the installed SDK).
- Added a `CheckoutAvailability` gate with `CHECKOUT_ENABLED` and `ALLOW_PRODUCTION_CHECKOUT`.
- Production checkout remains disabled by default, and production also requires a configured webhook signing secret.
- Added `config/commerce.php` and the Stripe block in `config/services.php`; no credentials are committed.
- Uses Stripe.js and the embedded Payment Element. Card data is never collected in ordinary SOGRATE form fields.

## Checkout and PaymentIntents

- Replaced the previous pending-order-only checkout submission with a JSON payment-initialization flow.
- Checkout recalculates authoritative totals from the cart, saved/new address, fulfillment method, shipping/delivery rules, pickup location, and zero-rated tax calculator before payment.
- PaymentIntents are created server-side with the exact validated order total and USD currency.
- PaymentIntent metadata includes the local order ID and order number.
- Payment records store PaymentIntent ID, customer ID, amount, currency, status, errors, paid/canceled timestamps, and links to refunds.
- The active unpaid payment attempt is reused when compatible.
- Changed checkout details with a changed total safely cancel and replace a non-processing unpaid attempt.
- Processing PaymentIntents are not replaced; the customer is asked to wait for completion.
- A succeeded PaymentIntent found before order refresh is reconciled without altering the paid order or creating a new charge.
- Stable operation-specific idempotency keys and the existing checkout-token idempotency prevent duplicate orders and duplicate active PaymentIntents.
- Database transactions are not held open during Stripe API calls.

## Checkout UI and Customer Payments

- Added the Payment Element to the one-page checkout after contact, fulfillment, address, and review steps.
- The browser submits checkout details first, receives only the client secret and customer-session client secret, then mounts the Payment Element.
- Detail fields are locked after the PaymentIntent is prepared to prevent amount/address drift.
- Loading, processing, declined, authentication, network, and retry states are handled with recoverable messages.
- Guests can pay without creating an account and receive the existing secure signed confirmation access.
- Registered customers can pay without saving a card.
- Saving a card is optional and requires an explicit checkbox; `setup_future_usage` is only sent when that consent is given.
- Stripe Customers are associated with the correct application user and reused across checkouts.

## Saved Payment Methods

- Added `/account/payment-methods` using Stripe CustomerSession-supported saved-card display in the Payment Element.
- Added account management to list safe card metadata (brand, last four, expiration) and remove cards through Stripe.
- Cross-customer removal is rejected by verifying the Stripe Customer owner before detachment.
- Guests cannot save or manage site-level saved cards.

## Webhook Reconciliation

- Added `POST /stripe/webhook` using the raw body and configured signing secret.
- Invalid signatures return HTTP 400.
- Stripe event IDs are stored uniquely in `stripe_events`; processed duplicates are ignored and failed processing can be retried.
- Handles `payment_intent.succeeded`, `payment_intent.processing`, `payment_intent.payment_failed`, `payment_intent.canceled`, `refund.created`, and `refund.updated`.
- Validates PaymentIntent amount, currency, order association, and refund-to-payment relationships.
- Successful payment sets payment to Paid and order to Placed, records the paid timestamp, appends order activity, consumes the ordered cart quantities, and sends the paid-order notification once.
- Processing and failed states remain unfulfillable and do not send a paid-order confirmation.
- Stale/out-of-order failure or processing events cannot regress an already-paid order.
- The browser return page retrieves and reconciles server-verified Stripe status; it never marks an order paid from the URL alone.

## Refunds

- Added admin full and partial refunds using the original verified PaymentIntent.
- Partial refund amounts use integer cents and enforce the remaining refundable balance.
- Multiple partial refunds are supported without exceeding the captured amount.
- Refund request keys provide idempotency and prevent duplicate submissions.
- Refund records store Stripe refund ID, amount, currency, status, reason, administrator, failure details, and completion timestamp.
- Refund outcomes update payment/order status only after Stripe verification; submitted pending refunds do not falsely mark the order refunded.
- Refund history and remaining refundable balance are displayed in admin order details.
- Paid orders must be fully refunded before the existing cancellation action can cancel an unfulfilled order. Cancellation itself never changes payment state.
- No returns, exchanges, inventory restocking, or automated refunds were added.

## Notifications and Fulfillment

- Activated the existing order-status notification after verified payment success.
- Paid confirmation includes order reference, amount, fulfillment method, item snapshots, and next-step guidance.
- Duplicate successes do not resend the paid confirmation.
- Fulfillment remains administrator-controlled; payment success makes an order eligible but does not automatically ship, deliver, or mark it picked up.
- Existing pickup, local delivery, shipping, cancellation, and fulfillment activity behavior remains intact.

## Security and Configuration

- No raw card details, CVCs, or Stripe secrets are stored or exposed.
- Only the publishable key and Stripe client/customer-session secrets required by Stripe.js are returned to the browser.
- Webhook processing is CSRF-exempt but signature-verified.
- Production checkout remains gated until `ALLOW_PRODUCTION_CHECKOUT=true`, Stripe credentials, an HTTPS production domain, and a verified webhook endpoint are configured.
- Tax remains intentionally zero-rated. This is not represented as a legally approved production tax policy.

## Verification

- Fresh migrations and seeders passed.
- Frontend asset build passed; only pre-existing Sass deprecation warnings were emitted.
- Full automated suite passed: **669 tests, 1,720 assertions**.
- Pint passed on touched PHP files.
- Focused Stripe tests cover server-authoritative amounts, PaymentIntent association, idempotency, guest success, card saving consent, payment retry, webhook signatures, duplicate and out-of-order events, browser return reconciliation, cart consumption, notification frequency, saved-card ownership, partial/full refunds, refund limits, and paid cancellation.

## External Validation Still Required

- Real Stripe test-mode payment flows were not executed because no Stripe test keys or Stripe CLI forwarding were available in this environment.
- Before launch, verify with Stripe test cards: successful payment, declined payment, authentication redirect, interrupted/retried payment, saved-card consent and reuse, signed webhook delivery, duplicate webhook delivery, and refund webhook delivery.
- Production webhook readiness must be proven against the deployed HTTPS domain.

## Blockers

No code-level blockers remain for the completed test-mode milestone. Live Stripe test-mode and production webhook validation remain launch prerequisites.

## Nonblocking Follow-Ups

- Add operational monitoring/alerting for failed webhook receipts and queue failures.
- Confirm the final production tax policy before enabling live sales.
- Stripe Connect, subscriptions, wallets, carrier APIs, labels, automated fulfillment, returns, and advanced fraud tooling remain out of scope.

## Git Checkpoint

No commit, tag, or push was performed. After acceptance, create checkpoint `sograte-m10` using the established workflow.

## Acceptance Recommendation

Milestone 10 meets its implementation and automated-test acceptance criteria. Recommend acceptance, with the explicitly noted live Stripe test-mode/webhook validation required before production launch.
