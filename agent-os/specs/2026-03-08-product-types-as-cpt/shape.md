# Shape: Product Types as Custom Post Types

## Scope

**In scope:**
- Register `wc_product_simple`, `wc_product_variable`, `wc_product_grouped`, `wc_product_external` as separate CPTs
- Gate everything behind `product_type_post_types` feature flag (experimental, disabled by default)
- Update core product CRUD, queries, factory, conditional functions, REST API, Store API
- Build WP-CLI migration + rollback script
- Performance benchmarks comparing taxonomy-based vs post-type-based type resolution
- Document backward compatibility impact and go/no-go recommendation

**Out of scope:**
- Production-ready implementation (this is a feasibility prototype)
- Admin UI changes (new post types have `show_ui = false`)
- Changes to the block-based product editor
- Actual migration of live stores

## Key Decisions

### Why separate post types instead of the current taxonomy approach?

1. **Performance:** `get_post_type()` is a direct column read; `get_the_terms()` requires a JOIN. For shop pages with hundreds of products, eliminating the taxonomy JOIN for type resolution is measurable.
2. **WordPress alignment:** WP's template hierarchy, REST API, and capabilities system all key off post type. Using taxonomy for type forces WooCommerce to reimplement what WP provides natively.
3. **Precedent:** `product_variation` already exists as a separate post type, proving the pattern works.

### Why `wc_product_*` naming?

- WordPress post types have a 20-character limit
- `wc_product_simple` (17 chars), `wc_product_variable` (19 chars), `wc_product_grouped` (18 chars), `wc_product_external` (19 chars) — all fit
- `wc_` prefix avoids collision with other plugins
- Consistent with existing `product_variation` convention (though that lacks the `wc_` prefix for historical reasons)

### Why `capability_type = 'product'`?

Reuses existing product capabilities. No changes needed to user roles or permissions.

### Why `rewrite = false`?

All product types must share the `/product/` slug prefix. Custom rewrite rules handle slug resolution across post types. This avoids URL breakage.

### Why `show_ui = false`?

The admin product list page should show all products together, not separate lists per type. The existing product admin UI continues to work against the `product` post type conceptually.

## Rationale

The biggest risk is third-party extension breakage. Any code doing `get_post_type($id) === 'product'` will break. There is no transparent shim for this — it's a fundamental change to the data model.

The feasibility study quantifies this risk:
- How many places in core WooCommerce hardcode `'product'`?
- What patterns do major extensions (Subscriptions, Product Bundles) use?
- Is the performance gain worth the compatibility cost?

## Architecture Notes

### Type Resolution Flow (Current)
```
wc_get_product($id)
  → WC_Product_Factory::get_product_type($id)
    → WC_Data_Store::load('product')->get_product_type($id)
      → get_the_terms($id, 'product_type')
        → SQL JOIN on wp_term_relationships
```

### Type Resolution Flow (Proposed)
```
wc_get_product($id)
  → WC_Product_Factory::get_product_type($id)
    → get_post_type($id)
      → Direct column read from wp_posts.post_type (already cached)
    → Map 'wc_product_simple' → 'simple'
```

### Query Flow (Current)
```
WC_Product_Query(['type' => 'simple'])
  → tax_query: [['taxonomy' => 'product_type', 'terms' => 'simple']]
  → SQL JOIN on wp_term_relationships + wp_terms
```

### Query Flow (Proposed)
```
WC_Product_Query(['type' => 'simple'])
  → post_type: 'wc_product_simple'
  → Direct WHERE clause on wp_posts.post_type (indexed)
```
