# Assessment: Add Webflow as a Provider to the WooCommerce CLI Migrator (Products)

## Context

The WooCommerce CLI Migrator (`plugins/woocommerce/src/Internal/CLI/Migrator/`) currently ships a single provider — **Shopify**. It is wired through a clean filter-based platform registry, with per-platform `Fetcher`, `Mapper`, `Client`, and `Platform` classes plus shared core (`PlatformRegistry`, `CredentialManager`, `WooCommerceProductImporter`, `ProductsController`).

The goal is to add a **Webflow** provider so users can run `wp wc migrate products --platform=webflow` and import their Webflow eCommerce catalog into WooCommerce, using the Webflow Data API ([List Products & SKUs](https://developers.webflow.com/data/reference/ecommerce/products/list)). This unlocks Webflow as a migration source alongside Shopify.

## What already exists (reuse, don't rebuild)

Shared infrastructure that the Webflow provider just plugs into:

- `src/Internal/CLI/Migrator/Runner.php:70` — registers built-in platforms; will need one line to init `WebflowPlatform::init()`.
- `src/Internal/CLI/Migrator/Core/PlatformRegistry.php` — discovers platforms via `woocommerce_migrator_platforms` filter.
- `src/Internal/CLI/Migrator/Core/CredentialManager.php` — credential prompting/storage (used as-is).
- `src/Internal/CLI/Migrator/Core/ProductsController.php` — orchestration: batching, resume, filters, dry-run.
- `src/Internal/CLI/Migrator/Core/WooCommerceProductImporter.php` — converts the **standardized mapper output** to WC products. Webflow mapper must produce the same array shape (`name`, `slug`, `description`, `regular_price`, `sale_price`, `sku`, `stock_quantity`, `weight`, `images[]`, `is_variable`, `attributes[]`, `variations[]`, `original_product_id`, …).
- `src/Internal/CLI/Migrator/Interfaces/PlatformFetcherInterface.php` and `PlatformMapperInterface.php` — the contract.

The Shopify implementation under `src/Internal/CLI/Migrator/Platforms/Shopify/` is the reference: `ShopifyPlatform.php` (registration), `ShopifyClient.php` (HTTP), `ShopifyFetcher.php` (pagination + batch query), `ShopifyMapper.php` (normalize to standard shape).

## Webflow API specifics that shape the work

- **Auth**: Bearer token (Site Access Token or OAuth). Endpoint: `GET https://api.webflow.com/v2/sites/{site_id}/products` (API v2). Need both `site_id` and `access_token` as credentials.
- **Shape**: `List Products & SKUs` returns each product with an embedded `product` field-data object **and** an array of `skus` (variants). Webflow’s model already nests SKUs in the product response, so a single endpoint call yields products + variants (no second roundtrip per product, unlike Shopify’s GraphQL nesting).
- **Pagination**: `offset` + `limit` (max 100 per page). No cursor — `has_next_page` is derived from `pagination.total` vs `offset + count`. Fetcher will translate this to the cursor-shaped return of `PlatformFetcherInterface` by using stringified offsets as “cursor”.
- **Variants/SKUs**: Webflow variant options live in `product.fieldData['sku-properties']` (array of `{ id, name, enum: [{ id, name, slug }] }`). Each SKU's `sku-values` maps property id → enum id. Mapper must resolve ids → human names for WC attributes.
- **Pricing**: SKU has `price.value` (minor units, e.g. cents) and `compare-at-price`. Mapper must divide by 100 and respect currency code from `price.unit`.
- **Inventory**: `inventory.type` is `infinite` or `finite`, with `quantity` when finite.
- **Images**: SKU has `main-image` and `more-images` (already-hosted URLs). Product has `more-images` for product-level gallery.
- **Categories** (confirmed): `fieldData.category` is an **array of CMS item ids** referencing the auto-provisioned Ecommerce Categories collection. There is **no `/products/categories` endpoint**. Idiomatic resolution:
  1. `GET /v2/sites/{site_id}/collections` once, find the collection where `slug === "category"` (or `displayName === "Categories"`).
  2. `GET /v2/collections/{collection_id}/items` paginated → cache `id → {name, slug}`.
  3. Per-product, resolve `fieldData.category[]` via that cache; emit flat WC `product_cat` terms.
  Webflow Ecommerce categories are **flat (no parent field)** — every category maps to a top-level WC category, no hierarchy logic needed. Collection ids are per-site; never hardcode. Missing/archived ids → log + skip the category, don't fail the product.
- **Weight**: SKU has `weight` with unit (`oz`, `lb`, `g`, `kg`) — reuse the conversion approach from `ShopifyMapper::get_converted_weight()`.
- **Rate limits**: 60 req/min on the v2 API. Client must surface `429`s and back off; Shopify client doesn’t need this (GraphQL cost-based) so this is genuinely new logic.

## Files to add

```
plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Webflow/
├── WebflowPlatform.php   # registers via filter; credentials: site_id, access_token
├── WebflowClient.php     # rest_request(), Bearer auth, 429 backoff, JSON decode
├── WebflowFetcher.php    # implements PlatformFetcherInterface; offset→cursor mapping
└── WebflowMapper.php     # implements PlatformMapperInterface; produces standard array
```

And the one-line wire-up in `src/Internal/CLI/Migrator/Runner.php:70` to call `WebflowPlatform::init()`.

Tests mirror the Shopify structure under `tests/php/src/Internal/CLI/Migrator/Platforms/Webflow/` with a `Fixtures/MockWebflowData.php` containing canned API responses (simple product, variable product with SKUs, product with multi-option variants, infinite inventory, sale price, category resolution).

## Scoping (rough sizing)

- **`WebflowClient` (~150 lines)**: small — single REST verb, Bearer auth, retry on 429. ~½ day.
- **`WebflowFetcher` (~150 lines)**: offset-based pagination, total count from `pagination.total`, filter args mapping (status, ids, created_after). ~½ day.
- **`WebflowMapper` (~500–700 lines)**: the bulk of the work — Webflow’s SKU-property model is unlike Shopify’s `selectedOptions`, so attribute resolution (`sku-properties` enum lookups → option name + variation `attributes`), money/weight unit conversion, image dedup between product gallery and SKU images, category resolution via second collection. ~2 days.
- **`WebflowPlatform` (~50 lines)** + Runner wire-up: trivial. ~½ day.
- **Tests + fixtures**: mirror Shopify’s tests; the mapper test is the biggest. ~1–1.5 days.
- **Manual end-to-end against a real Webflow site** + README in `Platforms/Webflow/`: ~½ day.

**Total: ~5 engineering days** for a feature-parity-with-Shopify Products migration, single PR. Orders/customers are out of scope (Shopify doesn’t ship those either yet through this controller).

## Known unknowns / risks

- **OAuth vs Site Token**: Site tokens are simpler; OAuth needs a registered app + refresh handling. Recommend starting with Site Access Tokens only and adding OAuth later behind a separate credential flow.
- **Variant images** (confirmed): The importer's per-variation image flow is well-defined and reusable. `WooCommerceProductImporter::handle_product_images()` (around line 1041) downloads each entry in the product-level `images[]` and builds a `migration_data['images_mapping']` keyed by the image's `original_id` → WP `attachment_id`. When importing variations (around line 816–821), it looks up `variation.image_original_id` in that map and calls `$variation->set_image_id($attachment_id)`. **Implication for the Webflow mapper**: every SKU image we want attached as a variation image must *also* be included in the product-level `images[]` array (with the same `original_id`), otherwise the lookup misses. We'll dedup product-gallery + SKU `main-image` URLs into one images array, assign stable `original_id`s, and reference them from each variation's `image_original_id`. Featured image is whichever entry has `is_featured: true` (product `more-images[0]` or the first SKU's main image). Cap of 50 images per product is enforced by the importer.
- **SEO fields and meta**: Webflow has `seo-title` / `seo-description` at the product level — should map to Yoast/RankMath-friendly meta if `seo` field is in the requested fields list (matches Shopify behavior).

## Mapper output shape (confirmed against `WooCommerceProductImporter`)

The Webflow mapper must emit exactly the same standardized shape the Shopify mapper emits:

```php
[
  'name', 'slug', 'description', 'short_description', 'status',
  'is_variable' => bool,
  'regular_price', 'sale_price', 'sku', 'manage_stock', 'stock_quantity', 'stock_status',
  'weight', 'tax_status',
  'categories' => [ ['name','slug'], ... ],         // resolved via category cache
  'tags' => [...],
  'images' => [ ['original_id','src','alt','is_featured'], ... ],   // deduped product+SKU images
  'attributes' => [ ['name','options'=>[],'position','is_visible'=>true,'is_variation'=>true], ... ],
  'variations' => [
    [
      'original_id', 'sku',
      'regular_price', 'sale_price',
      'manage_stock', 'stock_quantity', 'stock_status',
      'weight', 'tax_status',
      'attributes' => [ 'Color' => 'Red', 'Size' => 'M' ],   // attr name => term value
      'image_original_id' => string|null,                    // MUST key into images[].original_id
      'menu_order',
    ], ...
  ],
  'original_product_id', 'meta_data',
]
```

Importer specifics worth knowing (file: `Core/WooCommerceProductImporter.php`):

- Variations: parent product is saved before children (line 579 area); each variation gets independent `set_manage_stock` / `set_stock_quantity` / `set_stock_status` (line 806–808).
- SKU uniqueness is intentionally bypassed during import via `add_filter('wc_product_has_unique_sku', '__return_false', 999)` (line 534, 801). Webflow SKUs collide cleanly.
- Global attribute taxonomies (`pa_*`) are auto-created and terms inserted (line 633–699); attribute names are `sanitize_title()`'d so very long Webflow property names get truncated.
- Featured vs gallery: line 1048–1052 — first image with `is_featured: true` → `set_image_id()`; rest → `set_gallery_image_ids()`.
- Variation attribute lookup is case-sensitive against the taxonomy map with a lowercase fallback (line 739–740) — Webflow enum names should be passed through as-is.

## Verification

1. Add Webflow Site Access Token credentials: `wp wc migrate setup --platform=webflow`.
2. `wp wc migrate list` — confirm Webflow appears.
3. `wp wc migrate products --platform=webflow --limit=5 --dry-run` — confirm fetch + map without writes.
4. `wp wc migrate products --platform=webflow --limit=5` — confirm 5 products created in WC with correct prices, images, variations.
5. PHPUnit: `pnpm test:php:env -- --filter Webflow` (mirrors Shopify suite, runs against fixtures).
6. Lint: `pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes`.
7. PHPStan: `composer exec -- phpstan analyse src/Internal/CLI/Migrator/Platforms/Webflow --memory-limit=2G`.
