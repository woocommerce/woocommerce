# Cache Invalidation Service

A simple, hook-based cache invalidation system for WooCommerce entities.

## Overview

The cache invalidation service provides a straightforward way to respond to entity lifecycle events (like product creation, updates, and deletion) through WordPress action hooks.

## Architecture

The system consists of:

1. **CacheInvalidatorInterface** - Simple interface with an `invalidate()` method
2. **ProductCacheInvalidator** - Handles product lifecycle hooks and fires WordPress actions

This design leverages WordPress's native hook system rather than implementing a custom pub/sub pattern, making it simpler and more familiar to WordPress developers.

## Usage

### Listen to Product Cache Invalidation

The primary way to respond to product changes is through the `woocommerce_product_cache_invalidated` action:

```php
add_action( 'woocommerce_product_cache_invalidated', function( $product_id, $operation, $context ) {
    wp_cache_delete( 'my_cache_' . $product_id, 'my_plugin' );
}, 10, 3 );
```

### Manually Trigger Invalidation

You can manually trigger cache invalidation if needed:

```php
$invalidator = wc_get_container()->get( Automattic\WooCommerce\Caches\ProductCacheInvalidator::class );

$invalidator->invalidate(
    123,                // Product ID
    'custom_import',    // Operation name
    array(              // Optional context
        'source' => 'bulk_import',
        'batch_id' => 456,
    )
);
```

## Extending to Other Entity Types

To add support for other entity types:

1. Create a new invalidator class implementing `CacheInvalidatorInterface`
2. Register appropriate WooCommerce hooks
3. Call `invalidate()` to fire a WordPress action
4. Initialize your invalidator in


## API Reference

### ProductCacheInvalidator

```php
// Get instance
$invalidator = wc_get_container()->get( Automattic\WooCommerce\Caches\ProductCacheInvalidator::class );

// Manually trigger invalidation
$invalidator->invalidate( $product_id, $operation, $context );
```

### WordPress Hooks

```php
// Product cache invalidated
do_action( 'woocommerce_product_cache_invalidated', $product_id, $operation, $context );
```
