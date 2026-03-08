# Findings: Product Types as Custom Post Types

## Overview

This document summarizes the feasibility analysis of converting WooCommerce's product type system from a taxonomy-based approach (`product_type` taxonomy on the `product` post type) to individual custom post types (`wc_product_simple`, `wc_product_variable`, `wc_product_grouped`, `wc_product_external`).

## 1. Prototype Implementation Summary

### What was built

- **Feature flag**: `product_type_post_types` in `FeaturesController` (experimental, disabled by default, no UI)
- **4 new post types**: `wc_product_simple`, `wc_product_variable`, `wc_product_grouped`, `wc_product_external`
- **Helper functions**: `wc_get_product_post_types()`, `wc_product_type_to_post_type()`, `wc_post_type_to_product_type()`, `wc_is_product_post_type()`
- **Updated core systems**: Product Factory, Data Store (CPT), Product Model classes, WC_Query, conditional functions, Template Loader, REST API v2/v3, Store API, Product Query block, Shortcodes
- **WP-CLI migration script**: Forward migration + rollback with batch processing
- **Performance benchmark CLI**: Type resolution, shop queries, product counts, creation speed
- **Backward compatibility**: `post_type_link` filter for URL consistency, `pre_get_posts` expansion

### Files Modified

| File | Changes |
|------|---------|
| `src/Internal/Features/FeaturesController.php` | Feature flag registration |
| `includes/class-wc-post-types.php` | Post type registration, taxonomy attachment, permalink filter, query expansion, Gutenberg filter |
| `includes/class-wc-product-factory.php` | Type resolution from post type, product ID detection |
| `includes/data-stores/class-wc-product-data-store-cpt.php` | Create/update/read/delete, type queries, SQL queries (~15 locations) |
| `includes/class-wc-product-simple.php` | Constructor post_type override |
| `includes/class-wc-product-variable.php` | Constructor post_type override |
| `includes/class-wc-product-grouped.php` | Constructor post_type override |
| `includes/class-wc-product-external.php` | Constructor post_type override |
| `includes/wc-product-functions.php` | Helper functions |
| `includes/class-wc-query.php` | Frontend query expansion |
| `includes/wc-conditional-functions.php` | `is_product()`, `is_shop()` expansion |
| `includes/class-wc-template-loader.php` | Template detection |
| `includes/class-wc-shortcodes.php` | Post type checks and queries |
| `includes/rest-api/Controllers/Version2/class-wc-rest-products-v2-controller.php` | Type filtering, post_type query |
| `src/StoreApi/Utilities/ProductQuery.php` | Type filtering, post_type query |
| `src/Blocks/BlockTypes/ProductQuery.php` | Post type in block queries |
| `includes/class-woocommerce.php` | CLI command registration |

### New Files

| File | Purpose |
|------|---------|
| `src/Internal/ProductTypePostTypes/MigrationCLICommand.php` | WP-CLI migration/rollback |
| `src/Internal/ProductTypePostTypes/PerformanceBenchmarkCLICommand.php` | Performance benchmarks |

## 2. Performance Analysis

### Expected Gains

**Type resolution**: `get_post_type()` is a direct column read from `wp_posts.post_type`, already cached in the WP post cache. The current `get_the_terms()` approach requires a JOIN across `wp_term_relationships`, `wp_term_taxonomy`, and `wp_terms`. On a cold cache, this is measurably slower.

**Shop page queries**: When filtering by type (e.g., "show only simple products"), the current approach uses a `tax_query` which adds SQL JOINs. With per-type post types, this becomes a simple `WHERE post_type = 'wc_product_simple'` — the `post_type` column is indexed.

**Query elimination**: For the common case of "get all products" (shop page, REST API listing), the current system queries `post_type = 'product'` (good), but the proposed system queries `post_type IN ('wc_product_simple', ...)` — which is slightly worse due to the IN clause but eliminates the need for type-specific tax_queries.

### Expected Costs

**Product count overhead**: `wp_count_posts()` is called per post type. With 4 new types, we need 4 calls instead of 1. WordPress caches these individually, so the overhead is per-request cache population, not per-call SQL.

**Post type IN clauses**: All SQL queries that previously used `post_type = 'product'` now use `post_type IN (...)` with 4+ values. This is a minor cost.

**WordPress object cache**: Separate cache groups per post type mean more cache entries. This is neutral to slightly negative for memory.

### Net Assessment

The biggest win is eliminating the `product_type` taxonomy JOIN for type resolution and type-filtered queries. For stores with 100K+ products, this should show measurable improvement in type resolution speed and filtered query performance. The product count overhead is minimal.

## 3. Backward Compatibility Assessment

### Critical Breakage Vector: `get_post_type() === 'product'`

**There is no transparent shim for this.** Any code (core, extension, or custom) that checks `get_post_type($id) === 'product'` will break for migrated products.

This affects:
- Third-party extensions doing direct post type checks
- Custom theme code checking product post types
- WP hooks that fire per-post-type (e.g., `save_post_product`)
- Admin code that uses `$typenow === 'product'`
- WordPress SEO plugins that register special handling for the `product` post type

### Catalog of Hardcoded References in WooCommerce Core

The prototype updated ~50 locations across 17 files. Additional hardcoded references exist in:
- Admin classes (product editor, list tables)
- Analytics/reporting code
- Webhooks (`woocommerce_webhook_topic_hooks` for `product.created`, etc.)
- Email classes
- Widget code
- Various utility functions

A full production implementation would need to audit and update 100+ additional files.

### Third-Party Extension Impact

**WooCommerce Subscriptions** (by WooCommerce):
- Registers `subscription` and `variable-subscription` product types via `product_type_selector` filter
- Uses `woocommerce_product_class` filter for class mapping
- Would need to register its own post types (`wc_product_subscription`, etc.)
- The extensibility API (Task 22) would need to support this

**WooCommerce Product Bundles** (by WooCommerce):
- Registers `bundle` product type
- Similar pattern to Subscriptions

**Common extension patterns**:
1. `add_filter('product_type_selector', ...)` — still works for taxonomy-based type
2. `add_filter('woocommerce_product_class', ...)` — still works
3. `get_post_type($id) === 'product'` — **BREAKS**
4. `$query->set('post_type', 'product')` — works (caught by `pre_get_posts` filter)

### WordPress Core Limitations

**Shared slug rewriting**: WordPress doesn't natively support multiple post types sharing the same URL slug prefix. The prototype uses `rewrite = false` and a custom `post_type_link` filter, but this is fragile:
- `get_permalink()` works (via our filter)
- URL resolution works (via `pre_get_posts` expansion)
- But `is_post_type_archive()` only works for the `product` post type natively

**`is_post_type_archive()`**: This WP function checks the queried post type. When we expand the query to include multiple post types, `is_post_type_archive('product')` returns false because the queried object is now an array. The prototype handles this in `WC_Query` and `is_shop()`, but third-party code may not.

## 4. Product Type Switching Investigation (Task 21)

When types are post types, switching from Simple to Variable means changing `post_type` via `wp_update_post()`. This works:
- WordPress allows changing `post_type` on existing posts
- Post ID remains stable
- All post meta is preserved
- The URL stays the same (our permalink filter handles it)

Concerns:
- `wp_update_post()` fires `transition_post_status` and `save_post_{post_type}` hooks — the post_type in the hook name changes, which could break extensions listening to `save_post_product`
- Object cache for the post must be invalidated (`clean_post_cache()`)
- The `woocommerce_product_type_changed` action fires correctly (our updated `update_version_and_type()` handles this)
- Variation deletion when switching from Variable to Simple still works (handled by WC_Product_Variable)

## 5. Third-Party Product Types Investigation (Task 22)

### Current Extension Mechanism

Extensions register types via two filters:
1. `product_type_selector` — adds type to the product editor type dropdown
2. `woocommerce_product_class` — maps type string to PHP class

### Proposed Extensibility API

When per-type post types are enabled, extensions would also need:
1. Register a custom post type (e.g., `wc_product_subscription`)
2. Add it to `wc_product_post_types` filter
3. Add mappings to `wc_product_type_to_post_type_map` and `wc_post_type_to_product_type_map` filters

Example:
```php
add_filter( 'wc_product_post_types', function( $types ) {
    $types[] = 'wc_product_subscription';
    return $types;
});

add_filter( 'wc_product_type_to_post_type_map', function( $map ) {
    $map['subscription'] = 'wc_product_subscription';
    return $map;
});

add_filter( 'wc_post_type_to_product_type_map', function( $map ) {
    $map['wc_product_subscription'] = 'subscription';
    return $map;
});
```

This is more complex than the current mechanism but provides full integration with the post type system.

## 6. Import/Export Compatibility (Task 23)

### CSV Import (`class-wc-product-csv-importer.php`)

The CSV importer uses `wc_get_product_object_type()` and then calls `$product->save()`, which goes through the data store. Since we updated the data store's `create()` method to use the correct post type, **imports should work** — the importer creates the product object by type, and our model classes set the correct `$post_type` in their constructors.

### CSV Export (`class-wc-product-csv-exporter.php`)

The exporter uses `wc_get_products()` which goes through `WC_Product_Query`. Since we updated the query to expand post types, **exports should work** — all product types will be included in the query results.

### REST API Import

Same as CSV — the REST API controllers use `wc_get_product()` and the data store for CRUD, both of which are updated.

## 7. Block Product Collection (Task 24)

The Product Collection block uses `ProductQuery.php` (updated in this prototype) and the `ProductQuery` block type class (also updated). The block should:
- Render products from all types
- Support type filtering via block attributes
- Work with layered navigation (taxonomy queries work because we attach taxonomies to all post types)

## 8. Auto-Generated REST Endpoints (Task 14)

With `show_in_rest = true`, WordPress auto-creates:
- `/wp/v2/wc_product_simple`
- `/wp/v2/wc_product_variable`
- `/wp/v2/wc_product_grouped`
- `/wp/v2/wc_product_external`

These endpoints:
- Return raw WP_Post data (not WooCommerce-formatted product data)
- Could conflict with WooCommerce's own `/wc/v3/products` endpoint in developer confusion
- Could be useful for headless setups that want per-type endpoints
- Should probably be disabled or documented as "not the canonical API"

Recommendation: Set `show_in_rest` to `true` but add `rest_controller_class` to use a custom controller that returns 404 or redirects to the WC API.

## 9. Migration Complexity

### Forward Migration

- Direct SQL update: `UPDATE wp_posts SET post_type = 'wc_product_simple' WHERE post_type = 'product' AND ...`
- Products without a `product_type` term → treated as simple (same as current behavior)
- `wc_product_meta_lookup` table is unaffected (joins on ID, no post_type column)
- Object cache must be flushed post-migration
- Batch processing prevents timeout on large stores

### Rollback

- Single SQL: `UPDATE wp_posts SET post_type = 'product' WHERE post_type IN ('wc_product_simple', ...)`
- Taxonomy terms are preserved (the prototype keeps setting them for rollback compatibility)
- Clean rollback, no data loss

### Risk for Large Stores

- 1M products: ~2 minutes for the SQL UPDATE (batch processed)
- Lock contention: the UPDATE locks rows, could cause brief downtime
- Recommendation: perform during maintenance window
- `wc_product_meta_lookup` joins are unaffected, so product search continues to work during migration

## 10. Go/No-Go Recommendation

### Arguments For

1. **Performance**: Eliminates taxonomy JOIN for type resolution (measurable on 10K+ stores)
2. **WordPress alignment**: Native template hierarchy, REST API, capabilities per type
3. **Precedent**: `product_variation` already works this way
4. **Query simplification**: Type filtering becomes a WHERE clause instead of a JOIN
5. **Future-proof**: Block editor, Full Site Editing, and WordPress trends all key off post type

### Arguments Against

1. **Breaking change**: `get_post_type() === 'product'` breaks everywhere — no transparent shim possible
2. **Extension ecosystem**: Every extension with product post type checks needs updating
3. **Marginal gains**: The performance improvement, while measurable, may not justify the ecosystem disruption
4. **Complexity**: 4× post types means 4× `wp_count_posts()`, 4× rewrite rules, 4× cache groups
5. **Shared slug hack**: WordPress wasn't designed for multiple post types sharing a URL slug prefix

### Recommendation: **NO-GO for near-term, CONDITIONAL GO for long-term**

The technical implementation is feasible and the prototype demonstrates it works. However, the backward compatibility cost is too high for the current extension ecosystem. The recommended path:

1. **Ship the feature flag (disabled)** — lets early adopters and extension developers test
2. **Publish the extensibility API** — let Subscriptions, Product Bundles, etc. add support
3. **Add `wc_is_product_post_type()` to the public API** — encourage extensions to use it instead of direct `get_post_type()` comparison
4. **Deprecation cycle** — warn about `get_post_type() === 'product'` checks over 2-3 major releases
5. **Enable by default** — after the ecosystem has had time to adapt (12-18 months)

## Appendix: Post Type Name Compliance

| Post Type | Length | Max (20) | Valid |
|-----------|--------|----------|-------|
| `wc_product_simple` | 17 | 20 | Yes |
| `wc_product_variable` | 19 | 20 | Yes |
| `wc_product_grouped` | 18 | 20 | Yes |
| `wc_product_external` | 19 | 20 | Yes |

All names fit within WordPress's 20-character `post_type` column limit.
