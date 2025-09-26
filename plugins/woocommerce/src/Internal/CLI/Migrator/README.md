# WooCommerce CLI Migrator

A command-line tool for migrating products from external e-commerce platforms to WooCommerce.

## Available Commands

| Command | Description | Example |
|---------|-------------|---------|
| `setup` | Configure platform credentials | `wp wc migrate setup [--platform=shopify]` |
| `products` | Migrate products from source platform | `wp wc migrate products --platform=shopify` |
| `list` | List all registered migration platforms | `wp wc migrate list` |
| `reset` | Reset platform credentials | `wp wc migrate reset [--platform=shopify]` |

## Basic Usage

1. **Configure platform credentials:**

   ```bash
   wp wc migrate setup
   ```

2. **Migrate products:**

   ```bash
   # Get product count
   wp wc migrate products --count
   
   # Migrate all products
   wp wc migrate products
   
   # Migrate with limit and batch size
   wp wc migrate products --limit=100 --batch-size=25
   ```

## Products Command Options

### Basic Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `--platform` | Source platform (default: shopify) | `shopify` |
| `--limit` | Maximum products to migrate | `100` |
| `--batch-size` | Products per batch (max 250) | `25` |
| `--count` | Show total product count only | `--count` |

### Filtering Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `--status` | Filter by product status | `active`, `archived`, `draft` |
| `--product-type` | Filter by product type | `"T-Shirt"`, `"single"` |
| `--vendor` | Filter by vendor/brand | `"My Brand"` |
| `--ids` | Specific product IDs | `"123,456,789"` |

**Filtering Examples:**

```bash
# Count products with filters
wp wc migrate products --count --status=active
wp wc migrate products --count --product-type="T-Shirt"

# Migrate with filters
wp wc migrate products --status=active --limit=50
wp wc migrate products --vendor="My Brand" --limit=25
```

### Field Selection Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `--fields` | Include only specific fields | `"name,price,sku"` |
| `--exclude-fields` | Exclude specific fields | `"images,metafields"` |

**Field Selection Examples:**

```bash
# Migrate only specific fields
wp wc migrate products --fields=name,price,sku --limit=50

# Exclude heavy fields
wp wc migrate products --exclude-fields=images,metafields --limit=100
```

### Execution Control Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `--resume` | Resume previous migration | `--resume` |
| `--skip-existing` | Skip existing products | `--skip-existing` |
| `--dry-run` | Preview without importing | `--dry-run` |
| `--verbose` | Show detailed output | `--verbose` |
| `--assign-default-category` | Assign default category to uncategorized products | `--assign-default-category` |

**Advanced Examples:**

```bash
# Preview migration
wp wc migrate products --dry-run --verbose --limit=10

# Resume previous migration
wp wc migrate products --resume

# Skip existing products
wp wc migrate products --skip-existing --limit=200

# Assign default category to uncategorized products
wp wc migrate products --assign-default-category --limit=100
```

## Architecture

### Components

- **Platform Registry** ([`PlatformRegistry.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/PlatformRegistry.php)) - Manages registered migration platforms
- **Credential Manager** ([`CredentialManager.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/CredentialManager.php)) - Handles platform credentials
- **Products Controller** ([`ProductsController.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/ProductsController.php)) - Orchestrates product migration
- **WooCommerce Product Importer** ([`WooCommerceProductImporter.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/WooCommerceProductImporter.php)) - Imports data to WooCommerce

### Platform Interface

Each platform must implement:

- **PlatformFetcherInterface** ([`PlatformFetcherInterface.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformFetcherInterface.php)) - Data retrieval
- **PlatformMapperInterface** ([`PlatformMapperInterface.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformMapperInterface.php)) - Data transformation

## Supported Platforms

| Platform | Status | Location |
|----------|--------|----------|
| **Shopify** | ✅ Production Ready | [`Platforms/Shopify/`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/) |

## Creating a New Platform

To add support for new platforms, create them as **external WordPress plugins** that register themselves using the `woocommerce_migrator_platforms` filter.

### 1. Create Plugin Structure

```text
your-platform-migrator/
├── your-platform-migrator.php     # Main plugin file
├── src/
│   ├── YourPlatformFetcher.php
│   └── YourPlatformMapper.php
```

### 2. Register Platform

In your main plugin file:

```php
<?php
/**
 * Plugin Name: Your Platform Migrator
 * Description: Migrate from Your Platform to WooCommerce
 */

add_action('init', function() {
    if (!class_exists('WooCommerce')) return;
    
    add_filter('woocommerce_migrator_platforms', function($platforms) {
        $platforms['yourplatform'] = [
            'name'        => 'Your Platform',
            'fetcher'     => 'YourNamespace\\YourPlatformFetcher',
            'mapper'      => 'YourNamespace\\YourPlatformMapper',
            'credentials' => [
                'api_key' => 'Enter API Key:',
                'store_url' => 'Enter Store URL:',
            ],
        ];
        return $platforms;
    });
});
```

### 3. Implement Fetcher

```php
<?php
use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;

class YourPlatformFetcher implements PlatformFetcherInterface {
    private array $credentials;

    public function __construct(array $credentials) {
        $this->credentials = $credentials;
    }

    public function fetch_batch(array $args): array {
        // Use $this->credentials['api_key'] etc.
        // Return: ['items' => [], 'cursor' => '', 'has_next_page' => bool].
    }

    public function fetch_total_count(array $args): int {
        // Return: total product count.
    }
}
```

### 4. Implement Mapper

```php
<?php
use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;

class YourPlatformMapper implements PlatformMapperInterface {
    public function map_product_data(object $source_product): array {
        return [
            'name' => $source_product->title,
            'description' => $source_product->description,
            'regular_price' => $source_product->price,
            'sku' => $source_product->sku,
            // ... more mappings
        ];
    }
}
```

### Reference Implementation

See the Shopify platform for a complete example: [`Platforms/Shopify/`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/)

## Required Data Structure

Your mapper must return data in this format:

```php
[
    // Basic Product Information
    'name' => 'Product Title',
    'slug' => 'product-slug',
    'description' => 'Full product description',
    'short_description' => 'Brief description',
    'status' => 'publish', // publish|draft|private
    'catalog_visibility' => 'visible', // visible|catalog|search|hidden
    'date_created_gmt' => '2023-01-01 12:00:00',
    
    // Pricing
    'regular_price' => '29.99',
    'sale_price' => '19.99',
    
    // Inventory
    'sku' => 'PRODUCT-SKU',
    'manage_stock' => true,
    'stock_quantity' => 100,
    'stock_status' => 'instock', // instock|outofstock|onbackorder
    
    // Physical Properties
    'weight' => '1.5',
    
    // Taxonomies
    'categories' => [
        ['name' => 'Category 1', 'slug' => 'category-1'],
        ['name' => 'Category 2', 'slug' => 'category-2']
    ],
    'tags' => [
        ['name' => 'Tag 1', 'slug' => 'tag-1'],
        ['name' => 'Tag 2', 'slug' => 'tag-2']
    ],
    'brand' => [
        'name' => 'Brand Name',
        'slug' => 'brand-slug'
    ],
    
    // Images
    'images' => [
        [
            'src' => 'https://example.com/image1.jpg',
            'alt' => 'Image description',
            'is_featured' => true,
            'original_id' => 'source_image_id_123'
        ],
        [
            'src' => 'https://example.com/image2.jpg',
            'alt' => 'Gallery image',
            'is_featured' => false,
            'original_id' => 'source_image_id_456'
        ]
    ],
    
    // Variable Products
    'is_variable' => false,
    'attributes' => [
        [
            'name' => 'Color',
            'options' => ['Red', 'Blue', 'Green'],
            'position' => 0,
            'is_visible' => true,
            'is_variation' => true
        ],
        [
            'name' => 'Size',
            'options' => ['Small', 'Medium', 'Large'],
            'position' => 1,
            'is_visible' => true,
            'is_variation' => true
        ]
    ],
    'variations' => [
        [
            'original_id' => 'source_variant_id_789',
            'regular_price' => '29.99',
            'sale_price' => '24.99',
            'sku' => 'PRODUCT-SKU-RED-S',
            'manage_stock' => true,
            'stock_quantity' => 10,
            'stock_status' => 'instock',
            'weight' => '1.5',
            'menu_order' => 0,
            'attributes' => [
                'Color' => 'Red',
                'Size' => 'Small'
            ],
            'image_original_id' => 'source_image_id_789',
            'cost_of_goods' => '15.00'
        ]
    ],
    
    // Metadata and Tracking
    'original_product_id' => 'source_platform_product_id',
    'original_url' => 'https://source-platform.com/products/product-slug',
    'metafields' => [
        'custom_field_1' => 'value1',
        'custom_field_2' => 'value2',
        'global_title_tag' => 'SEO Title',
        'global_description_tag' => 'SEO Description'
    ],
    'meta_data' => [
        ['key' => 'custom_meta_key', 'value' => 'custom_meta_value']
    ],
    
    // Cost of Goods (if supported)
    'cost_of_goods' => '15.00'
]
```
