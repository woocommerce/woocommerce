# Feasibility Study: WooCommerce Product Types as Custom Post Types

## Context

WooCommerce products use a single `product` custom post type, with product types (simple, variable, grouped, external) stored as terms in a hidden `product_type` taxonomy. This exploration investigates converting each product type to its own custom post type (e.g., `wc_product_simple`, `wc_product_variable`), leveraging WordPress's native post type features (template hierarchy, block templates, REST endpoints, capabilities) and reducing divergence from the WP data model.

**Precedent:** `product_variation` already exists as a separate post type, proving the architecture can support multiple product post types.

**Key constraint:** Backward compatibility — existing extensions must still work.

---

## Task 1: Save Spec Documentation

Create `agent-os/specs/2026-03-08-product-types-as-cpt/` with:
- **plan.md** — This full plan
- **shape.md** — Shaping notes (scope, decisions, rationale)
- **references.md** — Key files and architectural notes from exploration

---

## Task 2: Create Feature Flag

Register experimental feature in `FeaturesController::init_feature_definitions()`.

- **File:** `src/Internal/Features/FeaturesController.php`
- Flag: `product_type_post_types`, experimental, disabled by default, no UI
- All prototype code gated behind `FeaturesUtil::feature_is_enabled('product_type_post_types')`

---

## Task 3: Register New Post Types

Conditionally register four new post types in `WC_Post_Types::register_post_types()`.

- **File:** `includes/class-wc-post-types.php`
- Post types: `wc_product_simple`, `wc_product_variable`, `wc_product_grouped`, `wc_product_external`
- `capability_type` = `'product'` (reuses existing caps)
- `rewrite` = `false` (custom rewrite rules to share `/product/` slug)
- `has_archive` = `false` (shop page aggregates all types)
- `show_ui` = `false` (no separate admin menu entries)
- `show_in_rest` = `true`
- Add `pre_get_posts` filter to expand `post_type` for product slug resolution

---

## Task 4: Update Taxonomy Registration

Attach all product taxonomies to new post types when flag is on.

- **File:** `includes/class-wc-post-types.php`
- Hook into `woocommerce_taxonomy_objects_*` filters for: `product_cat`, `product_tag`, `product_shipping_class`, `product_visibility`, all `pa_*` attribute taxonomies

---

## Task 5: Update Product Factory Type Resolution

Modify `get_product_type()` to resolve type from post type directly (no taxonomy query).

- **File:** `includes/class-wc-product-factory.php` (line 105-113)
- **File:** `includes/data-stores/class-wc-product-data-store-cpt.php` (line 2109-2131)
- When flag on: map `wc_product_simple` → `'simple'`, etc. — eliminates taxonomy lookup
- Update `get_product_id()` (line 132-146) to accept all product post types

---

## Task 6: Update Data Store Create/Update

Change data store to set post type based on product class type.

- **File:** `includes/data-stores/class-wc-product-data-store-cpt.php`
- `create()` (line 201): hardcodes `post_type => 'product'` — map from `get_type()`
- `update()` (line 338): same hardcoding
- `update_version_and_type()` (line 1127-1139): change from `wp_set_object_terms()` to `post_type` update

---

## Task 7: Update Product Model Classes

Set `$post_type` property on each product class when flag is on.

- **Files:** `includes/class-wc-product-simple.php`, `class-wc-product-variable.php`, `class-wc-product-grouped.php`, `class-wc-product-external.php`
- Follow `WC_Product_Variation` pattern (line 27: `protected $post_type = 'product_variation'`)
- Override in constructor based on feature flag

---

## Task 8: Create `wc_get_product_post_types()` Helper

Centralize post type list for all query locations.

- **File:** `includes/wc-product-functions.php`
- Returns `['product', 'product_variation']` when flag off
- Returns `['wc_product_simple', 'wc_product_variable', 'wc_product_grouped', 'wc_product_external', 'product_variation']` when flag on
- Update ~20 hardcoded `post_type = 'product'` references in `class-wc-product-data-store-cpt.php`

---

## Task 9: Update WC_Product_Query and WC_Query

Replace taxonomy-based type filtering with post_type filtering.

- **File:** `includes/class-wc-product-query.php`
- **File:** `includes/class-wc-query.php` (lines 358, 379: `post_type = 'product'`)
- **File:** `includes/data-stores/class-wc-product-data-store-cpt.php` (`get_wp_query_args()` line 2162)
- `type=simple` maps to `post_type='wc_product_simple'` — no `tax_query` needed
- Shop page queries expand to all product post types

---

## Task 10: Update Conditional Functions

Expand post type checks in frontend conditionals.

- **File:** `includes/wc-conditional-functions.php`
- `is_product()` (line 85): `is_singular(array('product'))` → `is_singular(wc_get_product_post_types())`
- `is_shop()` (line 35): `is_post_type_archive('product')` → expand
- `is_product_taxonomy()` (line 47): `get_object_taxonomies('product')` → expand

---

## Task 11: Backward Compatibility Shim

Create `wc_is_product_post_type($post_type)` helper for third-party code.

- Hook `post_type_link` to ensure consistent URLs
- Document: no transparent shim exists for `get_post_type() === 'product'` — this is a known breakage vector

---

## Task 12: Update REST API V2/V3 Controllers

Update product controllers to query multiple post types.

- **File:** `includes/rest-api/Controllers/Version2/class-wc-rest-products-v2-controller.php` (line 50: `protected $post_type = 'product'`)
- `prepare_objects_query()` (line 341): replace `product_type` taxonomy query with post_type mapping
- Unified `/wc/v3/products` endpoint must return all products

---

## Task 13: Update Store API ProductQuery

Replace taxonomy filtering with post type filtering.

- **File:** `src/StoreApi/Utilities/ProductQuery.php` (line 41: `'post_type' => 'product'`)
- Lines 53-63: type filtering via `product_type` taxonomy → post_type mapping

---

## Task 14: Document Auto-Generated REST Endpoints

With `show_in_rest = true`, WordPress creates `/wp/v2/wc_product_simple` etc.

- Document what these look like and whether they're useful
- Check for conflicts with WooCommerce's own REST API

---

## Task 15: Update Template Loader

Expand post type checks in template loading.

- **File:** `includes/class-wc-template-loader.php`
- `is_singular('product')` at lines 162, 215, 265 → expand
- Document: with separate post types, WP auto-provides `single-wc_product_variable.php` hierarchy
- Quantify how much template loader code could be eliminated

---

## Task 16: Update Block Editor Integration

Expand post type checks in Gutenberg integration.

- **File:** `includes/class-wc-post-types.php` (line 760: `gutenberg_can_edit_post_type`)
- **File:** `src/Blocks/BlockTypes/ProductQuery.php` (line 266: `'post_type' => 'product'`)
- Test native block template support per post type

---

## Task 17: Update Shortcodes

Expand post type checks in shortcode queries.

- **File:** `includes/class-wc-shortcodes.php`
- Lines 343, 393: `in_array($product_data->post_type, array('product', 'product_variation'))`
- Lines 548, 601: `'post_type' => 'product'`

---

## Task 18: Build Migration Script (WP-CLI)

Create forward + rollback migration command.

- Migration: `UPDATE wp_posts SET post_type = 'wc_product_simple' WHERE post_type = 'product' AND ID IN (...)`
- Handle implicit simple products (no `product_type` term assigned)
- Preserve taxonomy terms for rollback
- Invalidate WordPress object caches post-migration
- `wc_product_meta_lookup` table should work as-is (joins on ID, no post_type column)

---

## Task 19: Build Performance Benchmarks

Measure with 1K, 10K, 100K products:

1. **Type resolution speed:** `get_the_terms()` (current) vs `get_post_type()` (proposed)
2. **Shop page query speed:** `tax_query` on `product_type` vs `post_type` filtering (eliminates JOIN)
3. **Product count overhead:** 4× `wp_count_posts()` calls vs 1
4. **Memory:** separate per-type caches vs single cache

---

## Task 20: Write Findings Document

Comprehensive document covering:

1. Performance data from Task 19
2. Backward compatibility assessment (catalog of hardcoded `'product'` references)
3. Third-party extension impact (Subscriptions, Product Bundles patterns)
4. WordPress core limitations (shared slug rewriting, `is_post_type_archive()`)
5. Migration complexity and risk for large stores
6. Go/no-go recommendation with rationale

---

## Task 21: Investigate Product Type Switching

Test changing type from Simple → Variable when types are post types.

- Currently: changes taxonomy term. With post types: changes `post_type` field.
- `wp_update_post()` allows it, but test cache invalidation, URL changes, hook firing
- Variation deletion behavior must still work

---

## Task 22: Investigate Third-Party Product Types

WooCommerce Subscriptions registers `subscription`, `variable-subscription` types.

- Current mechanism: `product_type_selector` filter + `woocommerce_product_class` filter
- New requirement: registration API for extensions to register their own post types
- Design the extensibility API

---

## Task 23: Test Import/Export Compatibility

WooCommerce CSV import/export uses `post_type = 'product'`.

- Test CSV import creates products with correct new post types
- Test CSV export includes products across all post types
- Test REST API import compatibility

---

## Task 24: Test WooCommerce Blocks Product Collection

Product Collection block uses `ProductQuery` with hardcoded post type.

- Test Product Collection block renders all product types
- Test layered navigation / filtering by type
- Test product grid/list blocks

---

## Key Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| URL slug collision across post types | High | Custom rewrite rules in Task 3 |
| Third-party `get_post_type() === 'product'` breakage | Critical | No transparent shim possible — document in findings |
| WordPress admin count fragmentation | Medium | Hook `wp_count_posts` to aggregate |
| Extension registration API design | High | Task 22 designs the API |
| Large store migration (millions of products) | Medium | Batch processing in WP-CLI command |

## Critical Files

- `includes/class-wc-post-types.php` — post type + taxonomy registration
- `includes/class-wc-product-factory.php` — type resolution + class mapping
- `includes/data-stores/class-wc-product-data-store-cpt.php` — data persistence (~20 hardcoded refs)
- `includes/abstracts/abstract-wc-product.php` — base product class
- `includes/class-wc-product-query.php` — product queries
- `includes/wc-conditional-functions.php` — `is_product()`, `is_shop()`, etc.
- `includes/class-wc-query.php` — frontend query handler
- `includes/class-wc-template-loader.php` — template overrides
- `includes/wc-product-functions.php` — `wc_get_product_types()` etc.
- `src/StoreApi/Utilities/ProductQuery.php` — Store API queries
- `includes/rest-api/Controllers/Version2/class-wc-rest-products-v2-controller.php` — REST API
- `src/Internal/Features/FeaturesController.php` — feature flag registration

## Verification

1. Enable feature flag, run migration script on test store
2. Verify product CRUD: create, read, update, delete for each product type
3. Verify shop page shows all products
4. Verify single product pages render correctly
5. Verify REST API `/wc/v3/products` returns all products
6. Verify Store API product queries work
7. Verify product type switching (Simple ↔ Variable)
8. Verify CSV import/export round-trip
9. Run performance benchmarks and document results
10. Verify rollback migration restores original state
