# Shopify Platform - Technical Reference

This document provides technical implementation details for the Shopify platform integration within the WooCommerce CLI Migrator. For general usage instructions and setup, see the [CLI Migrator documentation](../../README.md).

## Implementation Overview

The Shopify platform integration provides a complete, production-ready implementation of the WooCommerce CLI Migrator interfaces ([`PlatformFetcherInterface`](../../Interfaces/PlatformFetcherInterface.php) and [`PlatformMapperInterface`](../../Interfaces/PlatformMapperInterface.php)). It supports comprehensive migration of products and data from Shopify stores to WooCommerce using both REST and GraphQL APIs.

## Architecture Components

### ShopifyPlatform (`ShopifyPlatform.php`)

#### Platform Registration Component

- Registers with WooCommerce migrator system via `woocommerce_migrator_platforms` filter (handled by [`PlatformRegistry`](../../Core/PlatformRegistry.php))
- Defines required credentials: `shop_url` and `access_token`
- Links fetcher and mapper implementations to the migration system

### ShopifyClient (`ShopifyClient.php`)

#### API Communication Layer

- **Dual API Support**: REST API for simple operations, GraphQL for complex queries
- **API Version**: Uses Shopify API version `2025-04`
- **Authentication**: Handles X-Shopify-Access-Token header management
- **Error Handling**: Comprehensive error processing with WP_Error integration
- **Request Building**: Automatic URL construction and parameter handling

### ShopifyFetcher (`ShopifyFetcher.php`)

#### Data Retrieval Implementation (`PlatformFetcherInterface`)

- **GraphQL Query**: Comprehensive product query with variants, images, collections, metafields
- **Pagination**: Cursor-based pagination with `edges` and `pageInfo`
- **Filtering**: Support for status, vendor, product type, date ranges, specific IDs
- **Progress Tracking**: Total count retrieval via REST API

### ShopifyMapper (`ShopifyMapper.php`)

#### Data Transformation Implementation (`PlatformMapperInterface`)

- **Product Types**: Simple and variable product mapping
- **Field Processing**: Selective field processing based on constructor arguments
- **Unit Conversion**: Automatic weight unit conversion with fallback logic
- **Data Validation**: Type checking and field validation with defaults

## Technical Implementation Details

### GraphQL Query Structure

The `SHOPIFY_PRODUCT_QUERY` constant defines a comprehensive GraphQL query:

```graphql
query GetShopifyProducts($first: Int!, $after: String, $query: String, $variantsFirst: Int = 100) {
  products(first: $first, after: $after, query: $query) {
    edges {
      cursor
      node {
        id title handle descriptionHtml status createdAt vendor tags
        options(first: 10) { id name position values }
        featuredMedia { ... on MediaImage { id image { url altText } } }
        media(first: 50) { edges { node { ... on MediaImage { id image { url altText } } } } }
        variants(first: $variantsFirst) {
          edges {
            node {
              id price compareAtPrice sku inventoryPolicy inventoryQuantity
              inventoryItem { tracked unitCost { amount } measurement { weight { value unit } } }
              selectedOptions { name value }
              media(first: 1) { edges { node { ... on MediaImage { id image { url altText } } } } }
            }
          }
        }
        collections(first: 20) { edges { node { id handle title } } }
        metafields(first: 20, namespace: "global") { edges { node { namespace key value } } }
      }
    }
    pageInfo { hasNextPage }
  }
}
```

### Data Processing Flow

1. **Fetching**: `ShopifyFetcher::fetch_batch()` executes GraphQL query
2. **Pagination**: Cursor-based pagination using `edges` and `pageInfo`
3. **Mapping**: `ShopifyMapper::map_product_data()` transforms each product
4. **Validation**: Field validation and type conversion during mapping
5. **Output**: Standardized array format for WooCommerce importer

### Key Mapping Logic

**Product Type Detection** (`ShopifyMapper:125`):

```php
private function is_variable_product(object $shopify_product): bool {
    return isset($shopify_product->variants->edges) && count($shopify_product->variants->edges) > 1;
}
```

**Weight Conversion** (`ShopifyMapper:288`):

```php
private function get_converted_weight($weight, $weight_unit): ?float {
    // Uses WooCommerce wc_get_weight() when available
    // Fallback to manual conversion using WEIGHT_CONVERSION_FACTORS
}
```

**Status Mapping** (`ShopifyMapper:135`):

```php
private function get_woo_product_status(object $shopify_product): string {
    return 'ACTIVE' === $shopify_product->status ? 'publish' : 'draft';
}
```

## API Integration Details

### Client Architecture (`ShopifyClient.php`)

**Credential Management**:

```php
private function get_credentials() {
    $credentials = $this->credential_manager->get_credentials('shopify');
    // Maps shop_url and access_token to domain and access_token
}
```

**REST API URLs** (`ShopifyClient:115`):

```php
private function build_rest_url(string $domain, string $path, array $query_params): string {
    $api_version = '2025-04';
    return "{$shop_url}/admin/api/{$api_version}{$path}";
}
```

**GraphQL API URLs** (`ShopifyClient:197`):

```php
private function build_graphql_url(string $domain): string {
    $api_version = '2025-04';
    return "{$shop_url}/admin/api/{$api_version}/graphql.json";
}
```

### Error Handling Implementation

**API Response Processing** (`ShopifyClient:165`):

- HTTP status code validation (>=300 triggers error)
- JSON decode error handling
- GraphQL-specific error checking
- Detailed error messages with context

**Fetcher Error Recovery** (`ShopifyFetcher:191`):

```php
if (is_wp_error($response_data)) {
    \WP_CLI::warning('Failed to fetch products via GraphQL: ' . $response_data->get_error_message());
    return ['items' => [], 'cursor' => null, 'has_next_page' => false];
}
```

### Performance Optimization

**Batch Configuration**:

- Default batch size: 50 products (optimized for API limits)
- Variants per product: 100 (configurable via `variantsFirst` parameter)
- Media items: 50 per product, 1 per variant

**Memory Management**:

- Streaming product processing
- Cursor-based pagination prevents memory accumulation
- JSON response processing with immediate transformation

### Filter Implementation (`ShopifyFetcher:255`)

**GraphQL Query Building**:

```php
private function build_graphql_query_string(array $args): string {
    // Supports: status, product_type, vendor, handle, created_after, created_before, ids
    // Returns formatted GraphQL query string
}
```

**REST API Count Filters** (`ShopifyFetcher:338`):

```php
private function build_count_query_params(array $args): array {
    // Maps filter arguments to Shopify REST API parameters
    // Handles date ranges, status, vendor filtering
}
```

## Extension and Customization

### Selective Field Processing

The mapper supports selective field processing through constructor arguments:

```php
// Process only specific fields
$mapper = new ShopifyMapper(['fields' => ['title', 'price', 'images']]);
```

**Implementation** (`ShopifyMapper:339`):

```php
private function should_process(string $field_key): bool {
    return empty($this->fields_to_process) || in_array($field_key, $this->fields_to_process, true);
}
```

### Weight Conversion System

**Conversion Process**:

1. Map Shopify unit using `WEIGHT_UNIT_MAP`
2. Check WooCommerce store weight unit setting
3. Use `wc_get_weight()` if available, else manual conversion
4. Return converted weight or null if invalid

## Detailed Data Mapping Implementation

### Mapper Processing Flow (`map_product_data`)

The mapper processes Shopify products through these steps:

1. **Product Type Detection** (`is_variable_product:125`): `>1 variant = variable product`
2. **Basic Field Mapping** (`map_basic_product_fields:353`): Core product information
3. **Simple Product Data** (`map_simple_product_data:410`): For single-variant products only
4. **Image Processing** (`map_product_images:584`): Gallery and featured images
5. **Metafields & SEO** (`map_metafields:615`): Custom fields and SEO data
6. **Variable Product Data** (`map_variable_product_data:482`): Attributes and variations

## Field Mapping Reference

### Complete Shopify to WooCommerce Field Mapping

| WooCommerce Field | Shopify Source | Data Type | Transformation Logic | Notes |
|-------------------|----------------|-----------|---------------------|-------|
| **Basic Product Information** | | | | |
| `name` | `title` | string | Direct mapping | Product title |
| `slug` | `handle` | string | Direct mapping | URL-friendly identifier |
| `description` | `descriptionHtml` | string | `sanitize_product_description()` | HTML content, sanitized |
| `short_description` | `descriptionPlainSummary` | string | Direct mapping | Plain text summary |
| `status` | `status` | string | `ACTIVE` → `publish`, others → `draft` | Publication status |
| `original_product_id` | `id` | string | `basename($shopify_product->id)` | Shopify product ID |
| `original_url` | `onlineStoreUrl` | string | Direct mapping | Original Shopify URL |
| **Date Fields** | | | | |
| `date_created_gmt` | `createdAt` | datetime | Direct mapping | Creation timestamp |
| `date_modified_gmt` | `updatedAt` | datetime | Direct mapping | Last update timestamp |
| `date_published_gmt` | `publishedAt` | datetime | Direct mapping | Publication timestamp |
| **Visibility & Status** | | | | |
| `catalog_visibility` | `onlineStoreUrl` | string | `null` → `hidden`, exists → `visible` | Store visibility |
| `available_for_sale` | `availableForSale` | boolean | Direct mapping | Sale availability flag |
| **Product Classification** | | | | |
| `brand` | `vendor` | object | `['name' => $vendor, 'slug' => sanitize_title($vendor)]` | Brand/manufacturer |
| `product_type` | `productType` | object | `['name' => $type, 'slug' => sanitize_title($type)]` | Product category type |
| `is_gift_card` | `isGiftCard` | boolean | Direct mapping | Gift card detection |
| `requires_subscription` | `requiresSellingPlan` | boolean | Direct mapping | Subscription requirement |
| **Pricing (Simple Products)** | | | | |
| `regular_price` | `variants[0].price` or `compareAtPrice` | decimal | Compare logic determines regular vs sale | Base price |
| `sale_price` | `variants[0].price` (if compare exists) | decimal | Set when `compareAtPrice > price` | Discounted price |
| **Inventory (Simple Products)** | | | | |
| `sku` | `variants[0].sku` | string | Direct mapping | Stock keeping unit |
| `manage_stock` | `variants[0].inventoryItem.tracked` | boolean | Direct mapping | Inventory tracking flag |
| `stock_quantity` | `variants[0].inventoryQuantity` | integer | Direct mapping | Available quantity |
| `stock_status` | Calculated | string | `(quantity > 0 \|\| oversell) ? 'instock' : 'outofstock'` | Stock availability |
| **Physical Properties** | | | | |
| `weight` | `variants[0].inventoryItem.measurement.weight` | decimal | Unit conversion via `get_converted_weight()` | Weight with unit conversion |
| `cost_of_goods` | `variants[0].inventoryItem.unitCost.amount` | decimal | Direct mapping | Product cost |
| **Taxonomies** | | | | |
| `categories` | `collections.edges[].node` | array | `[['name' => $title, 'slug' => $handle], ...]` | Product collections |
| `tags` | `tags[]` | array | `[['name' => $tag, 'slug' => sanitize_title($tag)], ...]` | Product tags |
| **Images** | | | | |
| `images` | `media.edges[].node.image` | array | Complex object with featured detection | Gallery images |
| `images[].src` | `image.url` | string | Direct mapping | Image URL |
| `images[].alt` | `image.altText` | string | Direct mapping | Alt text |
| `images[].is_featured` | Calculated | boolean | `id === featuredMedia.id` | Featured image flag |
| `images[].original_id` | `id` | string | Direct mapping | Shopify media ID |
| **Variable Product Data** | | | | |
| `is_variable` | Calculated | boolean | `count(variants.edges) > 1` | Variable product detection |
| `attributes` | `options[]` | array | `[['name' => $name, 'options' => $values, ...], ...]` | Product attributes |
| `variations` | `variants.edges[].node` | array | Complex mapping for each variant | Product variations |
| **Variation Fields** | | | | |
| `variations[].original_id` | `variants[].id` | string | `basename($variant->id)` | Variant ID |
| `variations[].attributes` | `variants[].selectedOptions` | object | `[$name => $value, ...]` | Variant attributes |
| `variations[].regular_price` | `variants[].price` or `compareAtPrice` | decimal | Same logic as simple products | Variant pricing |
| `variations[].sale_price` | `variants[].price` (conditional) | decimal | Same logic as simple products | Variant sale price |
| `variations[].sku` | `variants[].sku` | string | Direct mapping | Variant SKU |
| `variations[].manage_stock` | `variants[].inventoryItem.tracked` | boolean | Direct mapping | Variant inventory tracking |
| `variations[].stock_quantity` | `variants[].inventoryQuantity` | integer | Direct mapping | Variant stock |
| `variations[].stock_status` | Calculated | string | Same logic as simple products | Variant stock status |
| `variations[].weight` | `variants[].inventoryItem.measurement.weight` | decimal | Unit conversion | Variant weight |
| `variations[].cost_of_goods` | `variants[].inventoryItem.unitCost.amount` | decimal | Direct mapping | Variant cost |
| `variations[].image_original_id` | `variants[].media.edges[0].node.id` | string | First media item ID | Variant image |
| `variations[].menu_order` | `variants[].position` | integer | Direct mapping | Variant display order |
| **Metafields & SEO** | | | | |
| `metafields` | `metafields.edges[].node` | object | `[namespace_key => value, ...]` | Custom fields |
| `global_title_tag` | `seo.title` | string | Direct mapping | SEO title |
| `global_description_tag` | `seo.description` | string | Direct mapping | SEO description |

### Weight Conversion Logic

| Shopify Unit | Standard Unit | Conversion Factor to kg | Conversion Factor to lb |
|--------------|---------------|-------------------------|-------------------------|
| `GRAMS` | `g` | 0.001 | 0.00220462 |
| `KILOGRAMS` | `kg` | 1 | 2.20462 |
| `POUNDS` | `lb` | 0.453592 | 1 |
| `OUNCES` | `oz` | 0.0283495 | 0.0625 |

**Weight Conversion Process**:

1. Map Shopify unit using `WEIGHT_UNIT_MAP`
2. Get WooCommerce store weight unit setting
3. Use `wc_get_weight()` if available, else manual conversion
4. Return converted weight or null if invalid

### Pricing Logic

**Simple Products & Variations**:

```php
if ($compareAtPrice && $compareAtPrice > $price) {
    'regular_price' => $compareAtPrice,
    'sale_price' => $price
} else {
    'regular_price' => $price,
    'sale_price' => null
}
```

### Stock Status Logic

```php
$manage_stock = $inventoryItem->tracked;
$stock_quantity = $inventoryQuantity ?? 0;
$allow_oversell = $manage_stock && 'CONTINUE' === $inventoryPolicy;
$stock_status = ($stock_quantity > 0 || $allow_oversell) ? 'instock' : 'outofstock';
```

**Enhanced Status Fields** (`map_enhanced_status:149-162`):

```php
'date_published_gmt' => $shopify_product->publishedAt, // if exists
'available_for_sale' => $shopify_product->availableForSale, // if exists
```

**Product Classification** (`map_product_classification:171-210`):

```php
'product_type' => ['name' => $productType, 'slug' => sanitize_title($productType)],
'standard_category' => ['name' => $category->name, 'slug' => sanitize_title($category->name)],
'is_gift_card' => $shopify_product->isGiftCard,
'requires_subscription' => $shopify_product->requiresSellingPlan,
```

### Variant and Pricing Logic

**Simple Product Data** (`map_simple_product_data:410-472` - first variant only):

```php
// Pricing logic (lines 417-423)
if ($variant->compareAtPrice && $variant->compareAtPrice > $variant->price) {
    'sale_price' => $variant->price,
    'regular_price' => $variant->compareAtPrice
} else {
    'sale_price' => null,
    'regular_price' => $variant->price
}

// Inventory logic (lines 431-436)
$manage_stock = $variant->inventoryItem->tracked,
'manage_stock' => $manage_stock,
'stock_quantity' => $variant->inventoryQuantity ?? 0,
$allow_oversell = $manage_stock && 'CONTINUE' === $variant->inventoryPolicy,
'stock_status' => ($stock_quantity > 0 || $allow_oversell) ? 'instock' : 'outofstock',

// Additional fields
'sku' => $variant->sku,
'weight' => $this->get_converted_weight($weight_data->value, $weight_data->unit),
'cost_of_goods' => $variant->inventoryItem->unitCost->amount,
'original_variant_id' => basename($variant->id),
```

**Variable Product Data** (`map_variable_product_data:482-575`):

```php
// Attributes (lines 487-496)
'attributes' => [
    'name' => $option->name,
    'options' => $option->values,
    'position' => $option->position,
    'is_visible' => true,
    'is_variation' => true
]

// Variations (lines 502-571 - same pricing/inventory logic as simple products)
'variations' => [
    'original_id' => basename($variant->id),
    'attributes' => [$selectedOption->name => $selectedOption->value],
    'image_original_id' => $variant->media->edges[0]->node->id,
    'menu_order' => $variant->position,
    // + all pricing, inventory, weight fields
]
```

### Image Processing (`map_product_images:584-606`)

```php
$featured_media_id = $shopify_product->featuredMedia->id,

'images' => [
    'original_id' => $media_node->id,
    'src' => $media_node->image->url,
    'alt' => $media_node->image->altText ?? null,
    'is_featured' => ($media_node->id === $featured_media_id)
]
```

### Metafields and SEO (`map_metafields:615-630`)

```php
// Metafields (lines 618-623)
$key = sprintf('%s_%s', $field_node->namespace, $field_node->key),
'metafields' => [$key => $field_node->value],

// SEO fields (map_seo_fields:219-231)
'global_title_tag' => $shopify_product->seo->title,
'global_description_tag' => $shopify_product->seo->description,
```

### Weight Conversion System (`get_converted_weight:288-320`)

**Conversion Logic**:

1. Validate weight > 0 and unit exists
2. Map Shopify unit using `WEIGHT_UNIT_MAP` (GRAMS→g, KILOGRAMS→kg, etc.)
3. Get WooCommerce store weight unit (`woocommerce_weight_unit`)
4. Handle 'lbs' → 'lb' normalization
5. Use `wc_get_weight()` if available, else manual conversion using `WEIGHT_CONVERSION_FACTORS`

**Conversion Factors** (`WEIGHT_CONVERSION_FACTORS:46-71`):

```php
'kg' => ['kg' => 1, 'g' => 1000, 'lb' => 2.20462, 'oz' => 35.274],
'g' => ['kg' => 0.001, 'g' => 1, 'lb' => 0.00220462, 'oz' => 0.035274],
'lb' => ['kg' => 0.453592, 'g' => 453.592, 'lb' => 1, 'oz' => 16],
'oz' => ['kg' => 0.0283495, 'g' => 28.3495, 'lb' => 0.0625, 'oz' => 1],
```

### Selective Field Processing

**Default Fields** (`get_default_product_fields:638-657`):

```php
['title', 'slug', 'description', 'short_description', 'status', 'date_created',
 'catalog_visibility', 'category', 'tag', 'price', 'sku', 'stock', 'weight',
 'brand', 'images', 'seo', 'attributes']
```

**Processing Check** (`should_process:339-343`):

```php
private function should_process(string $field_key): bool {
    return empty($this->fields_to_process) || in_array($field_key, $this->fields_to_process, true);
}
```

## Development Reference

This implementation serves as the **canonical reference** for WooCommerce CLI Migrator platform development. For creating new platform integrations, see the [main CLI Migrator documentation](../../README.md).

### Key Implementation Patterns

- Complete [`PlatformFetcherInterface`](../../Interfaces/PlatformFetcherInterface.php) implementation with cursor-based pagination
- Complete [`PlatformMapperInterface`](../../Interfaces/PlatformMapperInterface.php) implementation with comprehensive field mapping
- Dual API approach (REST + GraphQL) for optimal performance
- Comprehensive error handling with `WP_Error`
- Memory-efficient batch processing
- Extensible architecture with constructor options

### File Structure

```text
Shopify/
├── ShopifyPlatform.php     # Platform registration
├── ShopifyClient.php       # API communication layer
├── ShopifyFetcher.php      # Data retrieval (PlatformFetcherInterface)
├── ShopifyMapper.php       # Data transformation (PlatformMapperInterface)
└── README.md               # Technical documentation
```
