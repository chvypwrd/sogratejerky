# Product Variant SEO & Structured Data Corrections

**Date:** 2026-11-20
**Repository commit:** `1ee2f37`
**Status:** Implemented and verified locally. Not committed, pushed, or deployed.

## 1. Implementation Summary

### Product variant URL behavior

- Clean parent product URLs remain unchanged on initial page load.
- The `variant` query parameter is updated only when the customer changes the variant selector.
- Selecting the default variant removes `?variant`, returning to the clean parent URL.
- Direct links containing a valid `?variant={id}` continue to pre-select the correct active variant and display its SKU, price, weight, and image.
- Invalid, inactive, or nonexistent variant IDs redirect to the canonical parent product URL instead of rendering an avoidable duplicate query URL.
- The cart form, quantity selection, Add to Cart flow, and checkout behavior are preserved when the product is purchasable.

### ProductGroup structured data

- Preserved `productGroupID`, `variesBy`, and `hasVariant`.
- Added `size` to each variant `Product` using the existing variant name.
- Added variant-specific descriptions using existing catalog information: product name, variant name, weight, and catalog description.
- Preserved accurate SKU, price, currency, availability, and variant Offer URLs.
- Variant Offer URLs continue to point to the corresponding `?variant={id}` URL.
- The configured canonical hostname is now used for JSON-LD product, group, and offer URLs.
- Null or missing images are omitted instead of emitted as `"image": null`.
- Existing variant images remain preferred, with the primary product image used as fallback.
- No product identifiers, images, or inventory data were invented.

### Availability consistency

- Added `CheckoutAvailability::productIsPurchasable()` as the shared purchasability check.
- Product-page visible availability and JSON-LD availability now use the same logic.
- When checkout is intentionally disabled, the page displays “Unavailable for online purchase” and hides:
  - Add to Cart
  - Quantity input
  - Cart links
  - Quick Order link
- JSON-LD emits `OutOfStock` under the same condition.
- Checkout protections remain unchanged.

### Canonical URLs and sitemap

- Added `App\Support\CanonicalUrl` to generate URLs from the configured `APP_URL`.
- Product canonical URLs continue to reference the clean parent product URL.
- Variant URLs continue to canonicalize to the parent product URL.
- Product Open Graph URLs, JSON-LD URLs, shop/archive canonicals, and XML sitemap entries now use the configured hostname consistently.
- The sitemap continues to list canonical product URLs and excludes `?variant=` URLs.

## 2. Files Changed

- `app/Http/Controllers/PublicShopController.php`
- `app/Services/CheckoutAvailability.php`
- `app/Services/SitemapService.php`
- `app/Services/StructuredDataService.php`
- `app/Support/CanonicalUrl.php` - new
- `resources/views/shop/archive.blade.php`
- `resources/views/shop/index.blade.php`
- `resources/views/shop/show.blade.php`
- `tests/Feature/Shop/ProductPublicTest.php`

No migrations were added.

No changes were made to Stripe, payments, taxes, refunds, checkout processing, product pricing, inventory architecture, or Merchant Center integration.

## 3. Tests Added

Focused coverage added for:

- Clean parent URLs remaining unchanged on initial load
- Valid variant selection
- Invalid and inactive variant redirects
- Correct selected variant price and SKU
- ProductGroup JSON-LD
- Variant `size` and descriptions
- Missing-image handling
- Availability consistency when checkout is disabled
- Canonical URL hostname consistency
- Sitemap inclusion and variant exclusion
- Existing cart and checkout regressions

## 4. Test Results

```text
php artisan test tests\Feature\Seo tests\Feature\Shop tests\Feature\Cart

Result: PASS
Tests: 109
Assertions: 455
Risky: 3 (no failures)
```

```text
vendor\bin\pint --dirty

Result: PASS
```

```text
git diff --check

Result: PASS
```

```text
php artisan view:cache

Result: PASS
INFO Blade templates cached successfully.
```

See `docs/reviews/product-variant-seo-structured-data-corrections-test-results.txt`.

## 5. Deployment Considerations

- Set production `APP_URL` to the canonical HTTPS hostname before rebuilding configuration.
- Clear the cached sitemap after changing the hostname, for example:
  - `php artisan cache:forget sitemap.xml`
- Rebuild production configuration and compiled views during deployment.
- If production checkout remains closed, product pages now correctly show unavailable controls and no Add to Cart interface.
- Open the production checkout gate only after launch authorization.
- Invalid variant URLs return a temporary redirect to the clean parent URL. No permanent redirect is introduced.
- No Merchant Center feed or Content API integration was added. Shopping tab integration remains a separate follow-up.
- No browser-level JavaScript test was added; the clean-URL and variant-selection regression is covered through server-rendered markup tests and source-level assertions.

## 6. Repository State

- No automatic commit, push, or deployment was performed.
- The previously exported audit files remain untracked in `docs/reviews` and were not modified during this implementation.
