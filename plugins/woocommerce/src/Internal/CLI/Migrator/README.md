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

- **Platform Registry** ([`https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/PlatformRegistry.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/PlatformRegistry.php)) - Manages registered migration platforms
- **Credential Manager** ([`https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/CredentialManager.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/CredentialManager.php)) - Handles platform credentials
- **Products Controller** ([`https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/ProductsController.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/ProductsController.php)) - Orchestrates product migration
- **WooCommerce Product Importer** ([`https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/WooCommerceProductImporter.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Core/WooCommerceProductImporter.php)) - Imports data to WooCommerce

### Platform Interface

Each platform must implement:
- **PlatformFetcherInterface** ([`https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformFetcherInterface.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformFetcherInterface.php)) - Data retrieval
- **PlatformMapperInterface** ([`https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformMapperInterface.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Interfaces/PlatformMapperInterface.php)) - Data transformation

## Supported Platforms

| Platform | Status | Location |
|----------|--------|----------|
| **Shopify** | ✅ Production Ready | [`https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/) |

## Creating a New Platform

New platforms should be created as **external WordPress plugins** that hook into the `woocommerce_migrator_platforms` filter.

### 1. Create Plugin Structure

```text
your-platform-migrator/
├── your-platform-migrator.php     # Main plugin file
├── src/
│   ├── YourPlatformFetcher.php    # Implements PlatformFetcherInterface
│   └── YourPlatformMapper.php     # Implements PlatformMapperInterface
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
    public function fetch_batch(array $args): array {
        // Return: ['items' => [], 'cursor' => '', 'has_next_page' => bool]
    }

    public function fetch_total_count(array $args): int {
        // Return: total product count
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

See the Shopify platform for a complete example: [`https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/CLI/Migrator/Platforms/Shopify/)

## Required Data Structure

Your mapper must return data in this format:

```php
[
    'name' => 'Product Title',
    'description' => 'Product description',
    'regular_price' => '29.99',
    'sku' => 'PRODUCT-SKU',
    'categories' => [
        ['name' => 'Category', 'slug' => 'category']
    ],
    'images' => [
        ['src' => 'https://example.com/image.jpg', 'alt' => 'Alt text']
    ],
    // ... see WooCommerceProductImporter for complete structure
]
```
