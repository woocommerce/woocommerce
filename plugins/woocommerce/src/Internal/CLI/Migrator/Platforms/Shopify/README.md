# Shopify Platform - Technical Reference

Production-ready Shopify to WooCommerce migration platform. For general usage instructions, see the [CLI Migrator documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/README.md).

## Architecture

| Component | Purpose | File |
|-----------|---------|------|
| **ShopifyPlatform** | Platform registration | [`ShopifyPlatform.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/ShopifyPlatform.php) |
| **ShopifyClient** | API communication (REST + GraphQL) | [`ShopifyClient.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/ShopifyClient.php) |
| **ShopifyFetcher** | Data retrieval (implements [`PlatformFetcherInterface`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformFetcherInterface.php)) | [`ShopifyFetcher.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/ShopifyFetcher.php) |
| **ShopifyMapper** | Data transformation (implements [`PlatformMapperInterface`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformMapperInterface.php)) | [`ShopifyMapper.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/ShopifyMapper.php) |

## API Integration

- **Shopify API Version**: `2025-04`
- **Authentication**: X-Shopify-Access-Token header
- **Data Retrieval**: GraphQL for complex queries, REST for counts
- **Pagination**: Cursor-based with `edges` and `pageInfo`

## Key Features

### Data Fetching

- Comprehensive GraphQL query retrieving products, variants, images, collections, metafields
- Filtering support: status, vendor, product type, date ranges, specific IDs
- Batch processing with configurable limits (default: 50 products per batch)

### Data Mapping

- **Product Types**: Auto-detection (>1 variant = variable product)
- **Weight Conversion**: Automatic unit conversion (grams, kg, pounds, ounces)
- **Pricing Logic**: Smart regular/sale price detection using `compareAtPrice`
- **Stock Management**: Handles Shopify inventory tracking and oversell policies

## Field Mapping Reference

### Basic Product Fields

| WooCommerce | Shopify Source | Transformation |
|-------------|----------------|----------------|
| `name` | `title` | Direct |
| `slug` | `handle` | Direct |
| `description` | `descriptionHtml` | Sanitized HTML |
| `status` | `status` | `ACTIVE` → `publish`, others → `draft` |
| `sku` | `variants[0].sku` | First variant SKU |

### Pricing & Inventory

| WooCommerce | Shopify Source | Logic |
|-------------|----------------|-------|
| `regular_price` | `price` or `compareAtPrice` | Uses `compareAtPrice` if higher than `price` |
| `sale_price` | `price` | Set when `compareAtPrice > price` |
| `manage_stock` | `inventoryItem.tracked` | Direct mapping |
| `stock_quantity` | `inventoryQuantity` | Direct mapping |
| `stock_status` | Calculated | `(quantity > 0 OR oversell) ? 'instock' : 'outofstock'` |

### Physical Properties

| WooCommerce | Shopify Source | Transformation |
|-------------|----------------|----------------|
| `weight` | `inventoryItem.measurement.weight` | Unit conversion to store weight unit |
| `cost_of_goods` | `inventoryItem.unitCost.amount` | Direct mapping |

### Taxonomies

| WooCommerce | Shopify Source | Format |
|-------------|----------------|--------|
| `categories` | `collections` | `[['name' => $title, 'slug' => $handle], ...]` |
| `tags` | `tags` | `[['name' => $tag, 'slug' => sanitize_title($tag)], ...]` |
| `brand` | `vendor` | `['name' => $vendor, 'slug' => sanitize_title($vendor)]` |

### Images

| WooCommerce | Shopify Source | Logic |
|-------------|----------------|-------|
| `images[].src` | `media.image.url` | Direct mapping |
| `images[].alt` | `media.image.altText` | Direct mapping |
| `images[].is_featured` | Calculated | `media.id === featuredMedia.id` |
| `images[].original_id` | `media.id` | For tracking purposes |

### Variable Products

| WooCommerce | Shopify Source | Logic |
|-------------|----------------|-------|
| `is_variable` | Calculated | `count(variants) > 1` |
| `attributes` | `options` | `[['name' => $name, 'options' => $values, 'is_variation' => true], ...]` |
| `variations` | `variants` | Full variant data mapping |

### Variations

Each variation inherits the same pricing, inventory, and weight logic as simple products, plus:

| WooCommerce | Shopify Source | Notes |
|-------------|----------------|-------|
| `variations[].original_id` | `variant.id` | Shopify variant ID |
| `variations[].attributes` | `variant.selectedOptions` | `[$name => $value, ...]` |
| `variations[].image_original_id` | `variant.media[0].id` | First variant image |
| `variations[].menu_order` | `variant.position` | Display order |

## GraphQL Query Structure

The fetcher uses a comprehensive GraphQL query to retrieve all necessary product data:

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

## Selective Field Processing

Process only specific fields for performance:

```php
$mapper = new ShopifyMapper(['fields' => ['title', 'price', 'images']]);
```

**Available Fields**: `title`, `slug`, `description`, `short_description`, `status`, `date_created`, `catalog_visibility`, `category`, `tag`, `price`, `sku`, `stock`, `weight`, `brand`, `images`, `seo`, `attributes`

## Reference Implementation

This Shopify platform serves as the **canonical reference** for implementing new migration platforms. It demonstrates:

- Complete interface implementation
- Dual API strategy (REST + GraphQL)
- Comprehensive field mapping
- Error handling patterns
- Memory-efficient processing
- Extensible architecture

For creating new platforms, use this implementation as a guide while following the patterns established in the [main CLI Migrator documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/README.md).
