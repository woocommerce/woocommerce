# References: Product Types as Custom Post Types

## Critical Files (plugins/woocommerce/)

### Post Type & Taxonomy Registration
- `includes/class-wc-post-types.php` — `register_post_types()` (line 321), `register_taxonomies()` (line 41)
  - `product_variation` registered at line 472 — precedent for separate product post type
  - `gutenberg_can_edit_post_type()` at line 760 — hardcodes `'product'`
  - Taxonomy `product_type` registered at line 55 with `apply_filters('woocommerce_taxonomy_objects_product_type', array('product'))`
  - Taxonomy `product_cat` at line 89, `product_tag` at line 133, `product_shipping_class` at line 178
  - All use `apply_filters("woocommerce_taxonomy_objects_{$name}", array('product'))` pattern — extensible

### Product Factory
- `includes/class-wc-product-factory.php`
  - `get_product_type()` (line 105): delegates to data store, returns type string
  - `get_product_id()` (line 132): hardcodes `get_post_type($post->ID) === 'product'` check
  - `get_product_classname()` (line 78): maps type string to class name via `woocommerce_product_class` filter

### Data Store (CPT)
- `includes/data-stores/class-wc-product-data-store-cpt.php`
  - `create()` — hardcodes `'post_type' => 'product'`
  - `update()` — same
  - `get_product_type()` — uses `get_the_terms($id, 'product_type')` taxonomy lookup
  - `update_version_and_type()` — uses `wp_set_object_terms()` to set type
  - `get_wp_query_args()` — builds WP_Query args, type filtering via tax_query
  - ~20 hardcoded `'product'` post type references throughout

### Product Model Classes
- `includes/abstracts/abstract-wc-product.php` — base class, `$post_type = 'product'`
- `includes/class-wc-product-simple.php` — no explicit `$post_type` override
- `includes/class-wc-product-variable.php` — no explicit `$post_type` override
- `includes/class-wc-product-grouped.php` — no explicit `$post_type` override
- `includes/class-wc-product-external.php` — no explicit `$post_type` override
- `includes/class-wc-product-variation.php` — `$post_type = 'product_variation'` (line 27) — **the precedent**

### Query Classes
- `includes/class-wc-product-query.php` — product query abstraction
- `includes/class-wc-query.php` — frontend query handler, `post_type = 'product'` at lines ~358, 379

### Conditional Functions
- `includes/wc-conditional-functions.php`
  - `is_product()` — `is_singular(array('product'))`
  - `is_shop()` — `is_post_type_archive('product')`
  - `is_product_taxonomy()` — `get_object_taxonomies('product')`

### Product Functions
- `includes/wc-product-functions.php` — `wc_get_product()`, `wc_get_products()`, `wc_get_product_types()`

### Template Loader
- `includes/class-wc-template-loader.php` — `is_singular('product')` at multiple locations

### REST API
- `includes/rest-api/Controllers/Version2/class-wc-rest-products-v2-controller.php`
  - `$post_type = 'product'` (line 50)
  - `prepare_objects_query()` — type filtering via taxonomy

### Store API
- `src/StoreApi/Utilities/ProductQuery.php`
  - `'post_type' => 'product'` (line 41)
  - Type filtering via `product_type` taxonomy (lines 53-63)

### Block Integration
- `src/Blocks/BlockTypes/ProductQuery.php` — `'post_type' => 'product'`

### Shortcodes
- `includes/class-wc-shortcodes.php` — `post_type` checks and query args

### Feature Flag
- `src/Internal/Features/FeaturesController.php` — `init_feature_definitions()` for registering new flags
- `src/Utilities/FeaturesUtil.php` — `feature_is_enabled()` for checking flags

### Import/Export
- `includes/export/class-wc-product-csv-exporter.php`
- `includes/import/class-wc-product-csv-importer.php`

## Architectural Notes

### WordPress Post Type Limits
- Post type names: max 20 characters
- `post_type` column in `wp_posts`: varchar(20)
- All proposed names fit within limit

### Existing `product_variation` Pattern
The `product_variation` post type demonstrates that WooCommerce already supports multiple product-related post types:
- Registered with `capability_type => 'product'`
- `public => false`, `rewrite => false`
- Referenced throughout codebase with explicit checks

### Taxonomy Filter Pattern
All taxonomy registrations use `apply_filters("woocommerce_taxonomy_objects_{$taxonomy}", array('product'))`.
This existing filter pattern is the hook point for attaching taxonomies to new post types.

### Feature Flag Pattern
Features are registered in `FeaturesController::init_feature_definitions()` with:
```php
$this->add_feature_definition(
    'product_type_post_types',
    __( 'Product type post types', 'woocommerce' ),
    array(
        'is_experimental'              => true,
        'enabled_by_default'           => false,
        'disable_ui'                   => true,
        'default_plugin_compatibility' => 'incompatible',
    )
);
```

### ProductType Enum
- `src/Enums/ProductType.php` — defines `SIMPLE`, `GROUPED`, `EXTERNAL`, `VARIABLE`, `VARIATION` constants
- Used throughout modern code instead of string literals
