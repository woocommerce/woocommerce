# Platform Development Guide

This directory contains platform implementations for the WooCommerce Migrator.

## Architecture Overview

The WooCommerce Migrator uses a **hybrid platform architecture**:

1. **Built-in Default Platform** - Shopify ships with the core plugin as the primary migration source
2. **External Platform Plugins** - Additional platforms hook into the migrator via filters (e.g., "WooCommerce Magento Migrator", "WooCommerce BigCommerce Migrator")

## Creating a Platform Plugin

To create a new platform plugin (e.g., for Magento, BigCommerce, etc.), follow these steps:

### 1. Create Your Plugin Structure

```text
your-platform-migrator/
├── your-platform-migrator.php          # Main plugin file
├── src/
│   ├── class-platform-fetcher.php      # Implements PlatformFetcherInterface
│   ├── class-platform-mapper.php       # Implements PlatformMapperInterface
│   └── class-platform-registration.php # Hooks into WC Migrator
└── composer.json                       # Autoloading
```

### 2. Implement Required Interfaces

Your platform plugin must implement:

- `WooCommerce\Migrator\ImporterCore\Interfaces\PlatformFetcherInterface`
- `WooCommerce\Migrator\ImporterCore\Interfaces\PlatformMapperInterface`

### 3. Register Your Platform

Hook into the `woocommerce_migrator_platforms` filter:

```php
add_filter( 'woocommerce_migrator_platforms', function( $platforms ) {
    $platforms['your-platform'] = [
        'name'    => 'Your Platform',
        'fetcher' => 'YourNamespace\PlatformFetcher',
        'mapper'  => 'YourNamespace\PlatformMapper',
    ];
    return $platforms;
} );
```

### 4. Handle Dependencies

Your plugin should:

- Check if WooCommerce Migrator is active
- Handle its own autoloading
- Initialize during appropriate WordPress hooks

## Built-in Shopify Platform

The `Shopify/` directory contains the **default platform implementation** that ships with WooCommerce Migrator. This provides:

- ✅ Complete Shopify-to-WooCommerce migration capabilities
- ✅ Reference implementation for external platform developers
- ✅ Interface implementations showing best practices
- ✅ Proper namespacing and PSR-4 autoloading
- ✅ WordPress coding standards compliance

## Additional Platform Development

For creating additional platforms (Magento, BigCommerce, etc.), use the Shopify implementation as your reference.

## Testing Your Plugin

Platform plugins should include comprehensive tests that verify:

1. Platform registration works correctly
2. Classes can be instantiated via the registry
3. Interface methods return expected data structures
4. Integration with CLI commands functions properly

## Best Practices

- Use semantic versioning
- Follow WordPress coding standards
- Implement proper error handling
- Include comprehensive documentation
- Provide configuration options for API credentials
- Support resumable migrations (via ImportSession)

For more details, refer to the WooCommerce Migrator documentation.
