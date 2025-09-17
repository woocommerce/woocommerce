# WooCommerce CLI Migrator

The WooCommerce CLI Migrator is a powerful tool for migrating products and data from external e-commerce platforms to WooCommerce. It provides a modular, extensible architecture that supports multiple source platforms through a standardized interface.

## Architecture Overview

The CLI Migrator follows a modular architecture with the following components:

### Core Components

- **Platform Registry** (`Core/PlatformRegistry.php`) - Manages registered migration platforms
- **Credential Manager** (`Core/CredentialManager.php`) - Securely handles platform credentials
- **Products Controller** (`Core/ProductsController.php`) - Orchestrates product migration process
- **WooCommerce Product Importer** (`Core/WooCommerceProductImporter.php`) - Handles data import to WooCommerce

### Platform Interface

Each migration platform must implement two key interfaces:

- **PlatformFetcherInterface** (`Interfaces/PlatformFetcherInterface.php`) - Data retrieval from source platform
- **PlatformMapperInterface** (`Interfaces/PlatformMapperInterface.php`) - Data transformation to WooCommerce format

## Supported Platforms

### Built-in Platforms

| Platform | Status | Location | Documentation |
|----------|--------|----------|---------------|
| **Shopify** | ✅ Functional (Ships with WooCommerce) | `Platforms/Shopify/` | [Shopify Technical Reference](Platforms/Shopify/README.md) |

The Shopify platform is a **fully functional, production-ready migration platform** that ships with WooCommerce Core. Users can migrate from Shopify stores immediately without requiring any additional plugins.

## Usage

### Setup and Configuration

1. **Configure platform credentials:**

   ```bash
   wp wc migrate setup
   ```

2. **Select your source platform and provide required credentials**

### Command Examples

#### Basic Usage

```bash
# Get product count
wp wc migrate products --count

# Migrate all products from Shopify (default platform)
wp wc migrate products

# Migrate with limit and batch size
wp wc migrate products --limit=100 --batch-size=25
```

#### Filtering Examples

```bash
# Count products with filters
wp wc migrate products --count --status=active
wp wc migrate products --count --product-type="T-Shirt"
wp wc migrate products --count --vendor="My Brand"

# Migrate with filters
wp wc migrate products --status=active --limit=50
wp wc migrate products --product-type="single" --status=active
wp wc migrate products --vendor="My Brand" --limit=25
```

#### Field Selection Examples

```bash
# Migrate only specific fields
wp wc migrate products --fields=name,price,sku --limit=50

# Exclude heavy fields like images
wp wc migrate products --exclude-fields=images,metafields --limit=100
```

#### Advanced Migration Examples

```bash
# Dry run to preview migration
wp wc migrate products --dry-run --verbose --limit=10

# Resume previous migration
wp wc migrate products --resume

# Skip existing products
wp wc migrate products --skip-existing --limit=200

# Migrate specific products by ID
wp wc migrate products --ids="123,456,789"

# Assign default category to uncategorized products
wp wc migrate products --assign-default-category --limit=100
```

### Available Commands

| Command | Description | Example | Implementation |
|---------|-------------|---------|----------------|
| `setup` | Configure platform credentials | `wp wc migrate setup [--platform=shopify]` | [`SetupCommand`](Commands/SetupCommand.php) |
| `products` | Migrate products from source platform | `wp wc migrate products --platform=shopify` | [`ProductsCommand`](Commands/ProductsCommand.php) |
| `list` | List all registered migration platforms | `wp wc migrate list` | [`ListCommand`](Commands/ListCommand.php) |
| `reset` | Reset platform credentials | `wp wc migrate reset [--platform=shopify]` | [`ResetCommand`](Commands/ResetCommand.php) |

#### Setup Command

Configure credentials for a specific platform:

```bash
# Setup credentials for default platform (Shopify)
wp wc migrate setup

# Setup credentials for specific platform
wp wc migrate setup --platform=shopify
```

#### List Command

Display all registered migration platforms in a detailed table format:

```bash
# Shows table with columns: ID, Name, Fetcher Class, Mapper Class
wp wc migrate list
```

#### Reset Command

Delete stored credentials for a platform:

```bash
# Reset credentials for default platform (Shopify)
wp wc migrate reset

# Reset credentials for specific platform
wp wc migrate reset --platform=shopify
```

### Products Command Parameters

The `products` command supports extensive filtering and configuration options:

#### Basic Parameters

| Parameter | Description | Example Values |
|-----------|-------------|----------------|
| `--platform` | Source platform identifier | `shopify` (default) |
| `--limit` | Maximum number of products to migrate | `100`, `500` |
| `--batch-size` | Products to process per batch | `20` (default), max `250` |
| `--count` | Only fetch and display total product count | `--count` |

#### Filtering Parameters

| Parameter | Description | Example Values |
|-----------|-------------|----------------|
| `--status` | Filter products by status | `active`, `archived`, `draft` |
| `--product-type` | Filter by product type | `"T-Shirt"`, `"single"`, `"variable"` |
| `--vendor` | Filter products by vendor/brand | `"My Brand"` |
| `--ids` | Comma-separated list of specific product IDs | `"123,456,789"` |

#### Field Selection Parameters

| Parameter | Description | Example Values |
|-----------|-------------|----------------|
| `--fields` | Comma-separated list of fields to migrate | `"name,price,sku"` |
| `--exclude-fields` | Comma-separated list of fields to exclude | `"images,metafields"` |

#### Execution Control Parameters

| Parameter | Description | Example Values |
|-----------|-------------|----------------|
| `--resume` | Resume from previous migration session | `--resume` |
| `--skip-existing` | Skip products that already exist | `--skip-existing` |
| `--dry-run` | Perform migration without creating products | `--dry-run` |
| `--verbose` | Show detailed progress and error information | `--verbose` |
| `--assign-default-category` | Assign default category to uncategorized products | `--assign-default-category` |

## Creating a New Platform Integration

**Important**: New platforms should be created as **external WordPress plugins** that hook into the WooCommerce CLI Migrator via the `woocommerce_migrator_platforms` filter (see [`PlatformRegistry`](Core/PlatformRegistry.php)). Do not modify WooCommerce core files.

### Implementation Requirements

New platforms must implement two required interfaces:

- **[`PlatformFetcherInterface`](Interfaces/PlatformFetcherInterface.php)**: Defines `fetch_batch()` and `fetch_total_count()` methods for data retrieval
- **[`PlatformMapperInterface`](Interfaces/PlatformMapperInterface.php)**: Defines `map_product_data()` method for data transformation

### Step 1: Create External Plugin Structure

Create a new WordPress plugin for your platform:

```text
your-platform-migrator-plugin/
├── your-platform-migrator.php         # Main plugin file
├── src/
│   ├── YourPlatformPlatform.php       # Platform registration
│   ├── YourPlatformClient.php         # API communication
│   ├── YourPlatformFetcher.php        # Data fetching logic (implements PlatformFetcherInterface)
│   ├── YourPlatformMapper.php         # Data mapping logic (implements PlatformMapperInterface)
└── README.md                          # Platform-specific documentation
```

### Step 2: Create Main Plugin File

Create the main plugin file that hooks into WooCommerce:

```php
<?php
/**
 * Plugin Name: Your Platform Migrator
 * Description: Migrate products from Your Platform to WooCommerce
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires WooCommerce: 6.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Hook into WooCommerce Migrator
add_action('init', function() {
    if (!class_exists('WooCommerce') || !class_exists('Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry')) {
        return; // WooCommerce or CLI Migrator not available
    }
    
    // Register platform via filter
    add_filter('woocommerce_migrator_platforms', function($platforms) {
        $platforms['yourplatform'] = [
            'name'        => 'Your Platform',
            'description' => 'Import products from Your Platform',
            'fetcher'     => 'YourNamespace\YourPlatformFetcher',
            'mapper'      => 'YourNamespace\YourPlatformMapper',
            'credentials' => [
                'api_key'    => 'Enter API Key:',
                'store_url'  => 'Enter Store URL:',
            ],
        ];
        return $platforms;
    });
});
```

### Step 3: Implement Platform Registration Class

Create a platform registration class in your plugin:

```php
<?php
namespace YourNamespace;

class YourPlatformPlatform {
    public static function init(): void {
        add_filter('woocommerce_migrator_platforms', [self::class, 'register_platform']);
    }

    public static function register_platform(array $platforms): array {
        $platforms['yourplatform'] = [
            'name'        => 'Your Platform',
            'description' => 'Import products from Your Platform',
            'fetcher'     => YourPlatformFetcher::class,
            'mapper'      => YourPlatformMapper::class,
            'credentials' => [
                'api_key'    => 'Enter API Key:',
                'store_url'  => 'Enter Store URL:',
            ],
        ];
        return $platforms;
    }
}
```

### Step 4: Implement the Fetcher

Create a fetcher class that implements [`PlatformFetcherInterface`](Interfaces/PlatformFetcherInterface.php):

```php
<?php
namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\YourPlatform;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;

class YourPlatformFetcher implements PlatformFetcherInterface {
    
    public function fetch_batch(array $args): array {
        // Implement batch fetching logic
        // Return: ['items' => [], 'cursor' => '', 'has_next_page' => bool]
    }

    public function fetch_total_count(array $args): int {
        // Implement total count logic  
        // Return: total number of products
    }
}
```

**Reference Implementation**: See [`ShopifyFetcher`](Platforms/Shopify/ShopifyFetcher.php) for a complete example.

### Step 5: Implement the Mapper

Create a mapper class that implements [`PlatformMapperInterface`](Interfaces/PlatformMapperInterface.php):

```php
<?php
namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\YourPlatform;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;

class YourPlatformMapper implements PlatformMapperInterface {
    
    public function map_product_data(object $source_product): array {
        // Transform source platform data to WooCommerce format
        return [
            'name' => $source_product->title,
            'description' => $source_product->description,
            'regular_price' => $source_product->price,
            // ... more mappings (see data structure below)
        ];
    }
}
```

**Reference Implementation**: See [`ShopifyMapper`](Platforms/Shopify/ShopifyMapper.php) for a complete example with comprehensive field mapping.

### Step 6: Required Data Structure

Your mapper must return data in this standardized format (as expected by [`WooCommerceProductImporter`](Core/WooCommerceProductImporter.php)):

```php
[
    // Basic Product Information
    'name' => 'Product Title',
    'slug' => 'product-slug',
    'description' => 'Full product description',
    'short_description' => 'Brief description',
    'status' => 'publish|draft|private',
    'catalog_visibility' => 'visible|catalog|search|hidden',
    
    // Pricing
    'regular_price' => '29.99',
    'sale_price' => '19.99',
    
    // Inventory
    'sku' => 'PRODUCT-SKU',
    'manage_stock' => true,
    'stock_quantity' => 100,
    'stock_status' => 'instock|outofstock|onbackorder',
    
    // Physical Properties
    'weight' => '1.5',
    'length' => '10',
    'width' => '5',
    'height' => '2',
    
    // Taxonomies
    'categories' => [
        ['name' => 'Category 1', 'slug' => 'category-1'],
        ['name' => 'Category 2', 'slug' => 'category-2']
    ],
    'tags' => [
        ['name' => 'Tag 1', 'slug' => 'tag-1'],
        ['name' => 'Tag 2', 'slug' => 'tag-2']
    ],
    
    // Images
    'images' => [
        [
            'src' => 'https://example.com/image1.jpg',
            'alt' => 'Image description',
            'is_featured' => true
        ]
    ],
    
    // Variable Products
    'is_variable' => false,
    'attributes' => [
        [
            'name' => 'Color',
            'options' => ['Red', 'Blue', 'Green'],
            'is_visible' => true,
            'is_variation' => true
        ]
    ],
    'variations' => [
        [
            'attributes' => ['Color' => 'Red'],
            'regular_price' => '29.99',
            'sku' => 'PRODUCT-SKU-RED'
        ]
    ],
    
    // Metadata
    'metafields' => [
        'custom_field_1' => 'value1',
        'custom_field_2' => 'value2'
    ]
]
```

## Testing Your Platform

### Unit Tests

Create tests following the existing pattern in `tests/php/src/Internal/CLI/Migrator/Platforms/`:

```php
<?php
class YourPlatformFetcherTest extends WP_UnitTestCase {
    public function test_fetch_batch() {
        // Test your fetcher implementation
    }
    
    public function test_fetch_total_count() {
        // Test your count implementation
    }
}
```

## Best Practices

### Error Handling

- Use `WP_Error` for recoverable errors
- Provide clear, actionable error messages
- Log debug information appropriately
- Handle API rate limiting gracefully

### Performance

- Implement efficient pagination
- Use batch processing for large datasets
- Minimize API calls through optimized queries
- Provide progress feedback for long operations

### Security

- Never expose credentials in logs or errors
- Validate all input data
- Use HTTPS for all API communications
- Follow WordPress security best practices

### Data Integrity

- Preserve original IDs for reference
- Handle data type conversions properly
- Validate required fields before mapping
- Provide fallback values for missing data

## Platform-Specific Documentation

### Built-in Platforms

- **Shopify Platform**: [Technical Implementation Reference](Platforms/Shopify/README.md)
    - The Shopify platform ships with WooCommerce and provides immediate migration capabilities
    - Includes complete GraphQL/REST API integration
    - Serves as the reference implementation for building new platforms

### External Platform Examples

External platform plugins follow the same architecture but are developed as separate WordPress plugins that hook into the `woocommerce_migrator_platforms` filter. Examples could include:

- Magento Migrator Plugin
- BigCommerce Migrator Plugin  
- Squarespace Migrator Plugin

Each external platform plugin would provide its own fetcher and mapper implementations following the interfaces demonstrated by the built-in Shopify platform.

## Advanced Configuration

### Custom Field Mapping

```php
// Example: Custom field processing
$mapper = new YourPlatformMapper([
    'fields' => ['title', 'price', 'images'] // Only process specific fields
]);
```

### Filtering and Hooks

The migrator provides several WordPress hooks for customization:

```php
// Register custom platform
add_filter('woocommerce_migrator_platforms', 'my_custom_platform_registration');

// Customize import behavior
add_filter('woocommerce_product_import_pre_insert_product_object', 'customize_product_data');
```

## Troubleshooting

### Common Issues

1. **Platform Not Registered**
   - Ensure platform class is properly loaded
   - Check filter hook registration
   - Verify namespace and class names

2. **API Connection Failures**
   - Validate credentials and permissions
   - Check network connectivity
   - Review API endpoint URLs

3. **Data Mapping Errors**
   - Verify required fields are present
   - Check data type compatibility
   - Review field name mappings

### Debug Mode

Enable comprehensive debugging:

```bash
wp wc migrate products --platform=yourplatform --debug --dry-run
```

## Contributing

When contributing new platforms or improvements:

1. Follow existing code structure and patterns
2. Include comprehensive unit tests
3. Provide detailed documentation
4. Test with various data scenarios
5. Follow WordPress coding standards