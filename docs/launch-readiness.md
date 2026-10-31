# SOGRATE Launch Readiness

**Last updated:** 2026-10-31  
**Software baseline:** Milestone 12 readiness work and setup addendum, pre-checkpoint  
**Public launch status:** **NO-GO**

**Classification:** **Pass** = verified; **Pending** = needs configuration, credentials, operator action, or external approval; **Blocker** = must be resolved before public launch.

## 1. Infrastructure

| Gate | Status | Evidence / action |
|---|---|---|
| Laravel application boots | Pass | Automated suite and local application checks passed. |
| Migrations current | Pass | All migrations ran successfully; local restore verification passed. |
| Production host and document root | Pending | Host, PHP-FPM/web server, MySQL, and `public/` document root must be configured. |
| Production domain, DNS, HTTPS | Pending | Configure canonical HTTPS domain and valid certificate. |
| Production `.env` and APP_KEY | Pending | Use `.env.production.example`; generate and preserve a stable production key. |
| Secure sessions/cookies | Pending | Set `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true` on production. |
| Writable storage/cache and storage link | Pending | Verify `storage/`, `bootstrap/cache/`, and `public/storage` on the host. |
| Deployment/rollback procedure | Pass | Documented in `docs/production-readiness.md`. |
| Backup and restore procedure | Pass | Procedure documented in docs/production-readiness.md; local non-destructive restore passed. |
| Production backup schedule/off-host restore drill | Pending | Configure recurring backups, off-host storage, retention, and a production restore drill. |
| Administrator account seeding | Pass | `AdminUserSeeder` uses environment credentials, refuses customer promotion, preserves existing passwords/roles/2FA, and is not part of `DatabaseSeeder`. Production execution remains pending. |
| Standard About/FAQ content and navigation | Pass | About is seeded published; known FAQs are active, the unapproved refund FAQ is inactive, and About/Contact menu links are seeded. Policy footer links appear only after publication. |

## 2. Security

| Gate | Status | Evidence / action |
|---|---|---|
| `APP_ENV=production`, `APP_DEBUG=false` | Pending | Production environment must be verified after configuration. |
| CSRF and authorization boundaries | Pass | Existing feature tests pass; customer/admin ownership remains enforced. |
| Admin 2FA available | Pass | Fortify 2FA and admin security flows remain in place; production administrator setup pending. |
| Stripe webhook signature verification | Pass | Code and focused tests cover invalid signatures and idempotent receipts. |
| Checkout idempotency and server totals | Pass | Automated checkout/payment tests pass. |
| No raw card data or exposed secrets | Pass | Stripe.js/Payment Element only; local secrets remain untracked. |
| Dependency security audit | Pass | `composer audit` and `npm audit` report no remaining vulnerabilities after dependency updates. |
| Production readiness command | Pass | `app:production-check` extended for environment, storage link, session, queue, failed jobs, Stripe, and checkout gate checks. |
| Production checkout gate | Pass | `ALLOW_PRODUCTION_CHECKOUT=false` is the example default and the application gate is tested. |
| Production security smoke test | Pending | Verify headers, private files, admin permissions, and logs after deployment. |

## 3. Stripe and Webhooks

| Gate | Status | Evidence / action |
|---|---|---|
| Stripe test publishable/secret keys | Pass | Test credentials exist locally; live keys are not active in the app. |
| Stripe test API connectivity | Pass | Read-only Stripe test API call succeeded; no charge was initiated. |
| Stripe test-mode successful payment | Pending | Run a real Stripe test-card purchase in a browser. |
| Declined/authentication/retry flows | Pending | Run Stripe test scenarios for decline, 3DS, retry, and interrupted payment. |
| Guest and registered checkout | Pending | Verify against real Stripe test mode for both customer types. |
| Saved-card consent and reuse | Pending | Verify save consent, reuse, and removal with Stripe test mode. |
| Full/partial refunds and duplicate prevention | Pending | Verify live test-mode Stripe refund responses and webhook reconciliation. |
| Test webhook destination and signing secret | Pending | Configure an HTTPS test destination or Stripe CLI; populate the real signing secret. |
| Live Stripe keys and live webhook | Pending | Requires owner approval, live-mode credentials, HTTPS callback, and successful test-mode signoff. |
| Production checkout activation | Blocker | Keep closed until tax, regulatory, Stripe, and operational gates are complete. |

## 4. Tax and Regulatory Compliance

| Gate | Status | Evidence / action |
|---|---|---|
| Approved sales-tax policy | Blocker | TaxCalculator remains zero-rated. Texas food/jerky tax treatment, shipping tax, interstate sales, and nexus require qualified confirmation. |
| Approved meat inspection/processing model | Blocker | Confirm USDA/Texas inspection, licensing, and permitted retail/wholesale model. |
| Approved product labels | Blocker | Confirm ingredients, allergens, net weight, responsible business identity, nutrition treatment, lot/best-by requirements, and claims. |
| Permitted shipping destinations | Blocker | Establish approved intrastate/interstate destinations and configure checkout restrictions. |
| Recall/food-safety procedures | Pending | Operator must document food-safety and recall procedures. |

The application does not establish regulatory compliance merely because these checkboxes exist.

## 5. Products and Fulfillment

| Gate | Status | Evidence / action |
|---|---|---|
| Approved production catalog | Blocker | Current development database has no launch catalog. Load approved names, variants, SKUs, weights, prices, images, descriptions, and SEO content. |
| Demo content removed/deactivated | Pending | Leave only approved production products and CMS content. |
| Shipping methods and rates | Blocker | Configure only commercially approved destinations, rates, packaging, and carrier expectations. |
| Local delivery ZIP zones and fees | Blocker | Configure approved operating area and fees. |
| Pickup locations and instructions | Blocker | Configure real locations, contacts, hours/instructions, and fees. |
| Fulfillment not offered unless operable | Blocker | Disable methods the business cannot actually perform. |

## 6. Customer Policies and Content

| Gate | Status | Evidence / action |
|---|---|---|
| Privacy Policy | Blocker | Draft CMS page seeded; owner/legal approval and publication required. |
| Terms and Conditions | Blocker | Draft CMS page seeded; owner/legal approval and publication required. |
| Shipping Policy | Blocker | Draft CMS page seeded; food/perishability, destination, and handling terms require approval and publication. |
| Refund and Cancellation Policy | Blocker | Draft CMS page seeded; refund eligibility, time limits, and damaged-order handling require approval and publication. |
| Contact information | Pending | Verify production phone/email/address and contact form delivery. |
| Legal review | Blocker | Owner/counsel approval required; do not invent binding policy text. |

## 7. Email and Background Operations

| Gate | Status | Evidence / action |
|---|---|---|
| Production mail transport and sender | Pending | Configure SMTP/API provider, verified sender, SPF/DKIM/DMARC. |
| Registration verification and password-reset email | Pending | Send real production/test messages through the selected transport. |
| Order and fulfillment notifications | Pending | Verify templates, queue processing, and no duplicate sends. |
| Queue worker | Pending | Deploy and supervise `queue:work`; review failed jobs. |
| Scheduler | Pending | Activate cron for `schedule:run`; verify scheduled blog publication. |
| Logging and webhook failure visibility | Pending | Confirm daily logs, retention, and Stripe event failure monitoring. |

## 8. Final Purchase Validation

| Gate | Status | Evidence / action |
|---|---|---|
| Controlled live or Stripe-test purchase | Blocker | Complete only after legal, tax, catalog, fulfillment, email, and owner authorization gates pass. |
| Webhook reconciliation and order visibility | Blocker | Confirm real signed delivery, order dashboard state, customer email, and fulfillment eligibility. |
| Refund procedure | Blocker | Verify authorized test refund and customer communication. |
| Final owner launch authorization | Blocker | Explicit owner approval required before enabling production checkout. |

## Go/No-Go Rule

**NO-GO for public launch.** The engineering baseline is accepted for Milestone 12, but production checkout must remain disabled until every critical gate above is Pass.

Milestone 12 acceptance does not authorize deployment, live checkout, real charges, or public sales.
