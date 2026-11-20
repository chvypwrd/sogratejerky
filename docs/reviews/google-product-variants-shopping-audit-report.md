# Google Product Variants & Shopping Visibility Audit

**Date:** 2026-11-20
**Repository commit:** `1ee2f37`
**Status:** Audit complete. No application code, catalog data, checkout, cart, Stripe, prices, or inventory changes were made.

## 1. Scope and Limitations

This audit reviewed:

- Product-page canonical tags and SEO metadata
- Product variant selection and canonicalization
- Product and ProductGroup JSON-LD
- Product sitemap inclusion
- Google Shopping / Merchant Center readiness
- The reported "Duplicate, Google chose different canonical than user" issue

Limitations:

- The local database currently contains `0` products and `0` variants. The 10 production products and their media could not be inspected directly.
- Production `.env`, Google Search Console, and Google Merchant Center are outside this repository and were not accessible.
- Google Rich Results Test was not run because no public production URL is present in the repository and the local `.test` host is not eligible.
- Findings are based on code inspection, current Google Search Central documentation, existing SEO/catalog tests, and transactional fixture checks that were rolled back.

## 2. Canonical and Variant Behavior

### Verified behavior

- Parent product URLs use a self-referencing canonical tag.
  - Source: `resources/views/shop/show.blade.php:9,16`
- URLs containing `?variant={id}` canonicalize to the parent product URL.
- Product variant selection uses active, purchasable variants and selects by query ID.
  - Source: `app/Http/Controllers/PublicShopController.php:90-91`
- Transactional page checks confirmed:
  - Parent URL returned `200` with parent canonical.
  - `?variant=<large>` returned `200` with parent canonical.
  - The selected large variant's SKU and price were rendered instead of the default variant.
- The XML sitemap includes canonical product URLs and excludes `?variant` URLs.
  - Source: `app/Services/SitemapService.php:24,37,44`
- Sitemap product entries are limited to active products with active, priced variants.
- Products without active variants emit `noindex,follow` and are excluded from the catalog and sitemap.

### Behavior requiring attention

- On initial page load, JavaScript immediately rewrites the clean parent URL to `?variant=<default>`.
  - Source: `resources/views/shop/show.blade.php:206-207`
  - This weakens the intended clean-URL state and can create avoidable variant-URL ambiguity.
  - Recommended behavior: only update the query string after the user changes the selection.
- Unknown or inactive variant IDs silently fall back to the first variant with a `200` response instead of redirecting to the canonical URL. Low severity, but it creates arbitrary duplicate query URLs.

## 3. Product and ProductGroup JSON-LD

### Present and structurally correct

- Single-page `ProductGroup` markup includes:
  - `productGroupID`
  - `variesBy: https://schema.org/size`
  - `hasVariant`
  - Parent `url`, name, description, brand, and image when available
  - Nested `Product` variants with `sku`, name, image, and `Offer`
  - Distinct variant `Offer.url` values
  - `price`, `priceCurrency: USD`, `availability`, `itemCondition`, and SKU
- Nested variants use the compact structure recommended by Google. `isVariantOf` and `inProductGroupWithID` are not required in this structure.
- `ProductGroup` is emitted when more than one active variant exists. A single active variant emits individual `Product` markup.

### Gaps confirmed in generated markup

- Variant `Product` nodes omit the `size` property even though `ProductGroup.variesBy` declares size.
- Variant `description` is omitted. Google recommends variant-specific descriptions.
- When a product and its variants have no media, the service emits `"image": null` for each variant.
  - Source: `app/Services/StructuredDataService.php:178`
  - Merchant listings require a product image. The key should be omitted or activation should require images.
- Prices are emitted as JSON strings, such as `"14.99"`, rather than numeric values. This is usually tolerated, but numeric JSON is safer for validation.
- No `shippingDetails`, `hasMerchantReturnPolicy`, GTIN, or MPN are emitted.
- Variant availability is product-wide, not variant-specific. `ProductVariant` has no stock or quantity field.

## 4. Confirmed Defects and Production Risks

### High priority

1. **No Merchant Center feed or Content API integration exists in the repository.**
   - No product feed route, feed file, Merchant Center configuration, or Content API service was found.
   - Google Shopping tab participation requires Google Merchant Center. Structured data alone does not enroll products.
   - An externally managed feed may exist, but it is not represented in this repository and could not be verified.

2. **Production checkout-gate behavior conflicts with visible availability.**
   - `.env.production.example:63` keeps `ALLOW_PRODUCTION_CHECKOUT=false`.
   - When the gate is closed, `CheckoutAvailability` returns false.
     - Source: `app/Services/CheckoutAvailability.php:13`
   - Structured data then marks products `OutOfStock`, but the product page still displays `Availability: Available` and an active "Add to Cart" button.
     - Sources: `app/Services/StructuredDataService.php:167`, `resources/views/shop/show.blade.php:115,126`
   - This violates Google's requirement that structured data agree with visible availability and that merchant-listing pages be purchasable.

3. **Missing product images are a likely Shopping blocker.**
   - The repository's only canonical 10-product legacy seed contains no product images and leaves all products inactive with zero prices.
   - If production activated those products without uploading media, generated variant `image` is `null`.
   - Product images are required for free listings and should be at least 500 x 500.

4. **Shipping, returns, and policy content are not fully represented.**
   - The local baseline has draft shipping and refund policy pages, not published ones.
     - Source: `docs/launch-readiness.md:85-86`
   - No shipping or return markup is emitted.
   - US free listings require shipping costs to be configured in Merchant Center or product data.
   - Return policy information must be available on the website and configured in Merchant Center.

### Medium priority

5. **Canonical host and scheme are not forced.**
   - Product canonical and Open Graph URLs use `route()` based on the current request host.
   - The sitemap uses `config('app.url')`.
     - Source: `app/Services/SitemapService.php:24`
   - There is no forced HTTPS or canonical-host redirect.
   - If production is reachable through HTTP/HTTPS or www/non-www variants, Google can see conflicting canonical and sitemap signals.

6. **The clean product URL is immediately rewritten to a variant URL.**
   - This weakens the intended single-page canonical model and may contribute to Search Console reporting "Duplicate, Google chose different canonical than user."

7. **Variant markup lacks `size` and variant descriptions.**
   - This is not necessarily a hard failure for a food product, but it reduces variant clarity and may generate Rich Results warnings.

8. **No inventory source exists.**
   - Availability is derived from active/price/checkout state, not stock quantity.
   - Active variants are effectively `InStock` until manually deactivated.
   - Merchant Center requires availability to match actual purchasability.

## 5. Google Shopping / Merchant Center Readiness

| Requirement | Status |
|---|---|
| Crawlable, canonical product pages | Satisfied |
| Product canonicalization for variants | Satisfied |
| Sitemap includes canonical products and excludes variants | Satisfied |
| Product/Offer structured data | Partially satisfied |
| ProductGroup grouping with ID and variation properties | Present, but variant `size` missing |
| Unique SKU and positive USD price | Satisfied by model rules |
| Accurate per-variant availability | Conditional / no inventory source |
| Required product image | Production images unverified; `null` fallback confirmed |
| Shipping settings or shipping data | Not in repository; Merchant Center configuration required |
| Return policy published and configured | Draft locally; website and Merchant Center verification required |
| Purchasable checkout | Production gate unverified; template closes it |
| Merchant Center product-data source | Not found in repository |
| GTIN/MPN or identifier exception | Not present; Merchant Center decision required |
| Merchant Center account, verification, and taxes | External; not verifiable here |

## 6. "Duplicate, Google chose different canonical than user"

The affected URL cannot be identified without the exact sample URL from Search Console. Diagnosis from code alone is therefore conditional.

Recommended investigation:

1. Open the Search Console sample URL and compare User-declared canonical with Google-selected canonical.
2. If the sample contains `?variant=`, the declared canonical should be the clean parent URL. The current code does this correctly.
3. If Google selected a different host or protocol, investigate HTTP/HTTPS and www/non-www canonical-host enforcement.
4. If Google selected a `?variant=` URL instead of the parent, Google may be treating the preselected variant as materially different content. Removing the automatic base-URL-to-variant rewrite and keeping clean parent URLs in internal links is the targeted response.
5. If the sample is a category, tag, or filter URL rather than a product URL, the issue is separate from variant canonicalization.
6. Confirm production `APP_URL`, canonical URL, internal links, and sitemap all use the same HTTPS hostname.

This Search Console status is not automatically a ranking problem, but it should be resolved if Google-selected URLs differ from Merchant Center landing URLs.

## 7. Recommended Targeted Corrections

1. Verify the actual production environment:
   - `APP_URL` is the canonical HTTPS hostname.
   - Checkout gate is open if products are genuinely purchasable.
   - Merchant Center has a product data source and free-listing status is enabled.

2. Make availability consistent:
   - Use one authoritative purchasability state for visible availability, Add to Cart, checkout, and JSON-LD.
   - If checkout is closed, show products as unavailable rather than `Available` with working cart controls.

3. Ensure every active product and variant has an image:
   - Require at least one primary or variant image before activation.
   - Omit `image` when absent rather than outputting `null`.
   - Validate image resolution and Merchant Center image policies.

4. Improve variant JSON-LD:
   - Add `size` to each variant `Product`.
   - Add a variant-specific description.
   - Consider numeric prices instead of strings.
   - Align `productGroupID` with the Merchant Center `item_group_id`.

5. Add Merchant Center shipping and return configuration:
   - Configure US shipping settings or emit `shippingDetails`.
   - Publish the shipping and refund/return policy pages.
   - Add merchant-level or offer-level return policy as appropriate.

6. Stop rewriting clean parent URLs to `?variant=` on initial load. Only update the query after a user changes the selector.

7. Enforce one canonical hostname:
   - Force HTTPS.
   - Redirect www/non-www variants.
   - Make canonical, Open Graph URL, internal links, and sitemap use the same URL source.

8. Add regression tests for:
   - Parent and `?variant` canonical behavior.
   - ProductGroup `productGroupID`, `variesBy`, `hasVariant`, variant `size`, Offer URL, and price.
   - Sitemap exclusion of variant URLs.
   - Visible availability matching JSON-LD when checkout is closed.
   - Missing-image handling.

9. After deployment, run representative production product and variant URLs through Google's Rich Results Test and URL Inspection.

## 8. Verification

See `docs/reviews/google-product-variants-shopping-audit-test-results.txt`.

Summary:

- Focused SEO and catalog tests: `71 passed, 204 assertions, 3 risky`
- Relevant Pint check: passed
- Blade compilation: passed
- Full-repository Pint check: fails on pre-existing unrelated formatting issues outside this audit scope
- Transactional product page and JSON-LD checks: passed, with no persistent catalog changes
- No commit, push, or deployment performed

## 9. References

- Google Product Variant Structured Data: https://developers.google.com/search/docs/appearance/structured-data/product-variants
- Google Merchant Listing Structured Data: https://developers.google.com/search/docs/appearance/structured-data/merchant-listing
- Google Ecommerce URL Structure: https://developers.google.com/search/docs/specialty/ecommerce/designing-a-url-structure-for-ecommerce-sites
- Share Product Data With Google: https://developers.google.com/search/docs/specialty/ecommerce/share-your-product-data-with-google
- Google Merchant Center Free Listings: https://support.google.com/merchants/answer/13889434
