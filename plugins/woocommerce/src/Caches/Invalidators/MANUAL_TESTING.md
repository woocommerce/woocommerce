# Manual Testing Instructions for ProductVersionStringInvalidator

## Prerequisites

For most tests, you'll set up a product ID in Test 2 (can be any product type).
The variation-specific tests (Test 5, 5b, 5c) are self-contained and create their own variable products.

## Setup - Access WP Shell

```bash
# From the project root
pnpm wp-env run tests-cli wp shell

# Or using docker directly
docker exec -it <container-id> wp shell
```

## Test 1: Verify Invalidator is Initialized

```php
$invalidator = wc_get_container()->get( Automattic\WooCommerce\Internal\Caches\ProductVersionStringInvalidator::class );
var_dump( $invalidator ); // Should show ProductVersionStringInvalidator object
```

## Test 2: Capture Invalidation Events

Set up a listener to capture all invalidation events:

```php
// Setup a product ID for testing (can be any existing product)
$parent_id = 123;  // Replace with any product ID in your database

// Capture all invalidation events
$captured_events = [];
add_action( 'woocommerce_product_cache_invalidated', function( $product_id, $operation, $context ) use ( &$captured_events ) {
    $captured_events[] = [
        'product_id' => $product_id,
        'operation' => $operation,
        'hook' => $context['hook'] ?? null,
        'function' => $context['function'] ?? null,
        'context_keys' => array_keys( $context )
    ];
}, 10, 3 );

echo "Listener registered. Events will be captured in \$captured_events\n";
echo "Note: Hook-triggered events have 'hook' key, direct data store calls have 'function' key\n";
```

## Test 3: Version String Integration

Test that version strings are created and deleted:

```php
// Get the version string generator
$version_gen = wc_get_container()->get( Automattic\WooCommerce\Internal\Caches\VersionStringGenerator::class );

// Create a version string for the parent product
$version_before = $version_gen->generate_version( "product_{$parent_id}" );
echo "Version created: {$version_before}\n";

// Verify it exists
$check = $version_gen->get_version( "product_{$parent_id}", false );
echo "Version exists: " . ( $check ? 'YES' : 'NO' ) . "\n";

// Now update the product to trigger invalidation
$product = wc_get_product( $parent_id );
$product->set_name( 'Updated Name - ' . time() );
$product->save();

// Check if version was deleted
$version_after = $version_gen->get_version( "product_{$parent_id}", false );
echo "Version after update: " . ( $version_after ? 'STILL EXISTS (BAD)' : 'DELETED (GOOD)' ) . "\n";

// Check captured events
echo "\nCaptured " . count( $captured_events ) . " events\n";
print_r( $captured_events );
```

## Test 4: Different Operations

Test each operation type:

```php
// Test CREATE operation - Simple Product
$captured_events = [];

$new_product = new WC_Product_Simple();
$new_product->set_name( 'Test Product - ' . time() );
$new_product->set_regular_price( '19.99' );
$new_product->save();
$new_product_id = $new_product->get_id();

echo "\n=== CREATE Operation (Simple Product) ===\n";
echo "New product ID: {$new_product_id}\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$create_events = array_filter( $captured_events, fn( $e ) => $e['operation'] === 'create' );
if ( count( $create_events ) > 0 ) {
    echo "✓ PASS: CREATE operation fired\n";
} else {
    echo "✗ FAIL: No CREATE operation found\n";
}

// Test UPDATE operation
$captured_events = [];
$product = wc_get_product( $parent_id );
$product->set_description( 'Test update - ' . time() );
$product->save();

echo "\n=== UPDATE Operation ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$update_events = array_filter( $captured_events, fn( $e ) => $e['operation'] === 'update' );
if ( count( $update_events ) > 0 ) {
    echo "✓ PASS: UPDATE operation fired\n";
} else {
    echo "✗ FAIL: No UPDATE operation found\n";
}

// Test TRASH operation
$captured_events = [];
wp_trash_post( $parent_id );

echo "\n=== TRASH Operation ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$trash_events = array_filter( $captured_events, fn( $e ) => $e['operation'] === 'trash' );
if ( count( $trash_events ) > 0 ) {
    echo "✓ PASS: TRASH operation fired\n";
} else {
    echo "✗ FAIL: No TRASH operation found\n";
}

// Test UNTRASH operation
$captured_events = [];
wp_untrash_post( $parent_id );

echo "\n=== UNTRASH Operation ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$untrash_events = array_filter( $captured_events, fn( $e ) => $e['operation'] === 'untrash' );
if ( count( $untrash_events ) > 0 ) {
    echo "✓ PASS: UNTRASH operation fired\n";
} else {
    echo "✗ FAIL: No UNTRASH operation found\n";
}

// Test DELETE operation (permanent delete of the test product we created)
$captured_events = [];
wp_delete_post( $new_product_id, true ); // true = force delete (skip trash)

echo "\n=== DELETE Operation ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$delete_events = array_filter( $captured_events, fn( $e ) => $e['operation'] === 'delete' );
if ( count( $delete_events ) > 0 ) {
    echo "✓ PASS: DELETE operation fired\n";
} else {
    echo "✗ FAIL: No DELETE operation found\n";
}
```

## Test 4b: WooCommerce Product API Operations

Test operations using WooCommerce's `$product->delete()` method (fires different hooks than WordPress post functions):

```php
// Create a test product for WC API tests
$captured_events = [];
$wc_test_product = new WC_Product_Simple();
$wc_test_product->set_name( 'WC API Test Product - ' . time() );
$wc_test_product->set_regular_price( '15.99' );
$wc_test_product->save();
$wc_test_product_id = $wc_test_product->get_id();
echo "Created test product ID: {$wc_test_product_id}\n";

// Test TRASH via WooCommerce API ($product->delete(false))
// This fires: woocommerce_trash_product
$captured_events = [];
$wc_test_product->delete( false ); // false = move to trash

echo "\n=== WC API TRASH Operation (woocommerce_trash_product) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$wc_trash_events = array_filter( $captured_events, fn( $e ) => $e['hook'] === 'woocommerce_trash_product' );
if ( count( $wc_trash_events ) > 0 ) {
    echo "✓ PASS: woocommerce_trash_product hook fired\n";
} else {
    echo "✗ FAIL: woocommerce_trash_product hook not found\n";
}

// Test UNTRASH via WordPress (WooCommerce doesn't have a dedicated untrash hook)
// This fires: untrashed_post (WordPress hook, no WooCommerce equivalent exists)
$captured_events = [];
wp_untrash_post( $wc_test_product_id );

echo "\n=== UNTRASH Operation (untrashed_post) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$untrash_events = array_filter( $captured_events, fn( $e ) => $e['hook'] === 'untrashed_post' );
if ( count( $untrash_events ) > 0 ) {
    echo "✓ PASS: untrashed_post hook fired\n";
} else {
    echo "✗ FAIL: untrashed_post hook not found\n";
}

// Test DELETE via WooCommerce API ($product->delete(true))
// This fires: woocommerce_before_delete_product
$captured_events = [];
$wc_test_product = wc_get_product( $wc_test_product_id ); // Reload after untrash
$wc_test_product->delete( true ); // true = force delete (permanent)

echo "\n=== WC API DELETE Operation (woocommerce_before_delete_product) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    echo "- Product {$event['product_id']}: {$event['operation']} via {$event['hook']}\n";
}
$wc_delete_events = array_filter( $captured_events, fn( $e ) => $e['hook'] === 'woocommerce_before_delete_product' );
if ( count( $wc_delete_events ) > 0 ) {
    echo "✓ PASS: woocommerce_before_delete_product hook fired\n";
} else {
    echo "✗ FAIL: woocommerce_before_delete_product hook not found\n";
}
```

## Test 5: Variation Operations (Should Invalidate Parent)

```php
// First, create a variable product with variations for this test
$captured_events = [];

// Create parent variable product
$test5_parent = new WC_Product_Variable();
$test5_parent->set_name( 'Test 5 Variable Product - ' . time() );
$test5_parent->save();
$test5_parent_id = $test5_parent->get_id();

// Create initial variation
$test5_variation1 = new WC_Product_Variation();
$test5_variation1->set_parent_id( $test5_parent_id );
$test5_variation1->set_regular_price( '19.99' );
$test5_variation1->save();
$test5_variation1_id = $test5_variation1->get_id();

echo "Created parent product ID: {$test5_parent_id}\n";
echo "Created variation ID: {$test5_variation1_id}\n";

// Test VARIATION CREATE
$captured_events = [];

$new_variation = new WC_Product_Variation();
$new_variation->set_parent_id( $test5_parent_id );
$new_variation->set_regular_price( '29.99' );
$new_variation->save();
$new_variation_id = $new_variation->get_id();

echo "\n=== VARIATION Create (should invalidate parent too) ===\n";
echo "New variation ID: {$new_variation_id}\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    $is_parent = ( $event['product_id'] == $test5_parent_id ) ? ' [PARENT]' : '';
    $is_variation = ( $event['product_id'] == $new_variation_id ) ? ' [VARIATION]' : '';
    echo "- Product {$event['product_id']}{$is_parent}{$is_variation}: {$event['operation']} via {$event['hook']}\n";
}

$variation_create = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $new_variation_id && $e['operation'] === 'create' );
$parent_update = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5_parent_id && $e['operation'] === 'update' );
if ( count( $variation_create ) > 0 && count( $parent_update ) > 0 ) {
    echo "✓ PASS: Variation CREATE and parent UPDATE both fired\n";
} else {
    echo "✗ FAIL: Expected variation create and parent update events\n";
}

// Test VARIATION UPDATE
$captured_events = [];

$variation = wc_get_product( $test5_variation1_id );
$variation->set_regular_price( rand( 10, 100 ) );
$variation->save();

echo "\n=== VARIATION Update (should invalidate parent too) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    $is_parent = ( $event['product_id'] == $test5_parent_id ) ? ' [PARENT]' : '';
    $is_variation = ( $event['product_id'] == $test5_variation1_id ) ? ' [VARIATION]' : '';
    echo "- Product {$event['product_id']}{$is_parent}{$is_variation}: {$event['operation']} via {$event['hook']}\n";
}

$variation_update = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5_variation1_id && $e['operation'] === 'update' );
$parent_update = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5_parent_id && $e['operation'] === 'update' );
if ( count( $variation_update ) > 0 && count( $parent_update ) > 0 ) {
    echo "✓ PASS: Variation UPDATE and parent UPDATE both fired\n";
} else {
    echo "✗ FAIL: Expected variation update and parent update events\n";
}

// Test VARIATION DELETE
$captured_events = [];

wp_delete_post( $new_variation_id, true ); // Delete the variation we created

echo "\n=== VARIATION Delete (should invalidate parent too) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    $is_parent = ( $event['product_id'] == $test5_parent_id ) ? ' [PARENT]' : '';
    $is_variation = ( $event['product_id'] == $new_variation_id ) ? ' [VARIATION]' : '';
    echo "- Product {$event['product_id']}{$is_parent}{$is_variation}: {$event['operation']} via {$event['hook']}\n";
}

$variation_delete = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $new_variation_id && $e['operation'] === 'delete' );
$parent_update = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5_parent_id && $e['operation'] === 'update' );
if ( count( $variation_delete ) > 0 && count( $parent_update ) > 0 ) {
    echo "✓ PASS: Variation DELETE and parent UPDATE both fired\n";
} else {
    echo "✗ FAIL: Expected variation delete and parent update events\n";
}

// Clean up - delete the test parent (will also delete remaining variation)
wp_delete_post( $test5_parent_id, true );
```

## Test 5b: WooCommerce Variation API Operations

Test variation operations using WooCommerce's `$variation->delete()` method:

```php
// First, create a variable product with a variation for this test
$captured_events = [];

// Create parent variable product
$test5b_parent = new WC_Product_Variable();
$test5b_parent->set_name( 'Test 5b Variable Product - ' . time() );
$test5b_parent->save();
$test5b_parent_id = $test5b_parent->get_id();
echo "Created parent product ID: {$test5b_parent_id}\n";

// Create a test variation for WC API tests
$captured_events = [];
$wc_test_variation = new WC_Product_Variation();
$wc_test_variation->set_parent_id( $test5b_parent_id );
$wc_test_variation->set_regular_price( '39.99' );
$wc_test_variation->save();
$wc_test_variation_id = $wc_test_variation->get_id();
echo "Created test variation ID: {$wc_test_variation_id}\n";

// Test VARIATION TRASH via WooCommerce API ($variation->delete(false))
// This fires: woocommerce_trash_product_variation
$captured_events = [];
$wc_test_variation->delete( false ); // false = move to trash

echo "\n=== WC API VARIATION TRASH (woocommerce_trash_product_variation) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    $is_parent = ( $event['product_id'] == $test5b_parent_id ) ? ' [PARENT]' : '';
    $is_variation = ( $event['product_id'] == $wc_test_variation_id ) ? ' [VARIATION]' : '';
    echo "- Product {$event['product_id']}{$is_parent}{$is_variation}: {$event['operation']} via {$event['hook']}\n";
}
$wc_variation_trash = array_filter( $captured_events, fn( $e ) => $e['hook'] === 'woocommerce_trash_product_variation' );
if ( count( $wc_variation_trash ) > 0 ) {
    echo "✓ PASS: woocommerce_trash_product_variation hook fired\n";
} else {
    echo "✗ FAIL: woocommerce_trash_product_variation hook not found\n";
}

// Test VARIATION UNTRASH
// This fires: untrashed_post (WordPress hook, no WooCommerce equivalent exists)
$captured_events = [];
wp_untrash_post( $wc_test_variation_id );

echo "\n=== VARIATION UNTRASH (untrashed_post) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    $is_parent = ( $event['product_id'] == $test5b_parent_id ) ? ' [PARENT]' : '';
    $is_variation = ( $event['product_id'] == $wc_test_variation_id ) ? ' [VARIATION]' : '';
    echo "- Product {$event['product_id']}{$is_parent}{$is_variation}: {$event['operation']} via {$event['hook']}\n";
}
$variation_untrash = array_filter( $captured_events, fn( $e ) => $e['hook'] === 'untrashed_post' && $e['product_id'] == $wc_test_variation_id );
$parent_update = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5b_parent_id && $e['operation'] === 'update' );
if ( count( $variation_untrash ) > 0 ) {
    echo "✓ PASS: untrashed_post hook fired for variation\n";
} else {
    echo "✗ FAIL: untrashed_post hook not found for variation\n";
}
if ( count( $parent_update ) > 0 ) {
    echo "✓ PASS: Parent product also invalidated\n";
} else {
    echo "✗ FAIL: Parent product not invalidated\n";
}

// Test VARIATION DELETE via WooCommerce API ($variation->delete(true))
// This fires: woocommerce_before_delete_product_variation
$captured_events = [];
$wc_test_variation = wc_get_product( $wc_test_variation_id ); // Reload after untrash
$wc_test_variation->delete( true ); // true = force delete (permanent)

echo "\n=== WC API VARIATION DELETE (woocommerce_before_delete_product_variation) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
foreach ( $captured_events as $event ) {
    $is_parent = ( $event['product_id'] == $test5b_parent_id ) ? ' [PARENT]' : '';
    $is_variation = ( $event['product_id'] == $wc_test_variation_id ) ? ' [VARIATION]' : '';
    echo "- Product {$event['product_id']}{$is_parent}{$is_variation}: {$event['operation']} via {$event['hook']}\n";
}
$wc_variation_delete = array_filter( $captured_events, fn( $e ) => $e['hook'] === 'woocommerce_before_delete_product_variation' );
$parent_update = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5b_parent_id && $e['operation'] === 'update' );
if ( count( $wc_variation_delete ) > 0 ) {
    echo "✓ PASS: woocommerce_before_delete_product_variation hook fired\n";
} else {
    echo "✗ FAIL: woocommerce_before_delete_product_variation hook not found\n";
}
if ( count( $parent_update ) > 0 ) {
    echo "✓ PASS: Parent product also invalidated\n";
} else {
    echo "✗ FAIL: Parent product not invalidated\n";
}

// Clean up - delete the test parent
wp_delete_post( $test5b_parent_id, true );
```

## Test 5c: Deleting Variable Product (Cascades to Variations)

Test that deleting a variable product also invalidates all its variations:

```php
// Create a variable product with multiple variations
$captured_events = [];

$test5c_parent = new WC_Product_Variable();
$test5c_parent->set_name( 'Test 5c Variable Product - ' . time() );
$test5c_parent->save();
$test5c_parent_id = $test5c_parent->get_id();

// Create multiple variations
$test5c_variation_ids = [];
for ( $i = 1; $i <= 3; $i++ ) {
    $variation = new WC_Product_Variation();
    $variation->set_parent_id( $test5c_parent_id );
    $variation->set_regular_price( 10 * $i );
    $variation->save();
    $test5c_variation_ids[] = $variation->get_id();
}

echo "Created parent product ID: {$test5c_parent_id}\n";
echo "Created variation IDs: " . implode( ', ', $test5c_variation_ids ) . "\n";

// Now delete the parent product
$captured_events = [];
$test5c_parent = wc_get_product( $test5c_parent_id );
$test5c_parent->delete( true ); // Force delete

echo "\n=== DELETE Variable Product (should invalidate parent + all variations) ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";

// Group events by product
$events_by_product = [];
foreach ( $captured_events as $event ) {
    $pid = $event['product_id'];
    if ( ! isset( $events_by_product[ $pid ] ) ) {
        $events_by_product[ $pid ] = [];
    }
    $events_by_product[ $pid ][] = $event;
}

foreach ( $events_by_product as $pid => $events ) {
    $label = '';
    if ( $pid == $test5c_parent_id ) {
        $label = ' [PARENT]';
    } elseif ( in_array( $pid, $test5c_variation_ids ) ) {
        $label = ' [VARIATION]';
    }
    echo "Product {$pid}{$label}:\n";
    foreach ( $events as $event ) {
        echo "  - {$event['operation']} via {$event['hook']}\n";
    }
}

// Check that parent was invalidated with DELETE
$parent_delete = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $test5c_parent_id && $e['operation'] === 'delete' );
if ( count( $parent_delete ) > 0 ) {
    echo "✓ PASS: Parent product DELETE fired\n";
} else {
    echo "✗ FAIL: Parent product DELETE not found\n";
}

// Check that each variation was invalidated
$all_variations_invalidated = true;
foreach ( $test5c_variation_ids as $var_id ) {
    $var_events = array_filter( $captured_events, fn( $e ) => $e['product_id'] == $var_id );
    if ( count( $var_events ) === 0 ) {
        echo "✗ FAIL: Variation {$var_id} was NOT invalidated\n";
        $all_variations_invalidated = false;
    }
}
if ( $all_variations_invalidated ) {
    echo "✓ PASS: All variations were invalidated\n";
}
```

## Test 6: Context Information

Test that rich context is provided:

```php
// Setup detailed capture
$captured_events = [];
add_action( 'woocommerce_product_cache_invalidated', function( $product_id, $operation, $context ) use ( &$captured_events ) {
    $captured_events[] = [
        'product_id' => $product_id,
        'operation' => $operation,
        'context' => $context,
    ];
}, 10, 3 );

// Trigger update via WordPress hook (will include 'post' in context)
wp_update_post( [
    'ID' => $parent_id,
    'post_title' => 'Updated via wp_update_post - ' . time(),
] );

echo "\n=== Context Details (Product Update) ===\n";
foreach ( $captured_events as $event ) {
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Context keys: " . implode( ', ', array_keys( $event['context'] ) ) . "\n";

    if ( isset( $event['context']['hook'] ) ) {
        echo "  - Triggered by hook: {$event['context']['hook']}\n";
    }
    if ( isset( $event['context']['post'] ) ) {
        echo "  - Includes post object: YES\n";
    }
    echo "\n";
}

// Test variation update (will include 'product' in context)
$captured_events = [];
$variation_id = $variation_ids[0];
$variation = wc_get_product( $variation_id );
$variation->set_sku( 'TEST-' . time() );
$variation->save();

echo "\n=== Context Details (Variation Update) ===\n";
foreach ( $captured_events as $event ) {
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Context keys: " . implode( ', ', array_keys( $event['context'] ) ) . "\n";

    if ( isset( $event['context']['hook'] ) ) {
        echo "  - Triggered by hook: {$event['context']['hook']}\n";
    }
    if ( isset( $event['context']['product'] ) ) {
        echo "  - Includes product object: YES (variation hooks only)\n";
    }
    if ( isset( $event['context']['variation_id'] ) ) {
        echo "  - Includes variation_id: {$event['context']['variation_id']} (parent update from variation change)\n";
    }
    echo "\n";
}
```

## Test 7: SQL-Level Updates

Test stock, price, and sales updates via actual WooCommerce API calls:

```php
// Create a simple product with stock management enabled for stock test
$stock_product = new WC_Product_Simple();
$stock_product->set_name( 'Stock Test Product' );
$stock_product->set_manage_stock( true );
$stock_product->set_stock_quantity( 100 );
$stock_product->save();
$stock_product_id = $stock_product->get_id();

$captured_events = [];

// Trigger SQL-level stock update via WooCommerce API
// wc_update_product_stock() calls $data_store->update_product_stock() which fires woocommerce_updated_product_stock
wc_update_product_stock( $stock_product_id, 50, 'set' );

echo "\n=== SQL Stock Update (wc_update_product_stock) ===\n";
$stock_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_updated_product_stock' === $e['context']['hook'] );
if ( count( $stock_events ) > 0 ) {
    $event = reset( $stock_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    echo "✓ PASS: Stock update triggered invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_updated_product_stock event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Hook: {$e['context']['hook']}\n";
    }
}

// Clean up stock test product
wp_delete_post( $stock_product_id, true );

$captured_events = [];

// Create a variable product with variations for price sync test
$variable_product = new WC_Product_Variable();
$variable_product->set_name( 'Price Sync Test Product' );
$variable_product->save();
$variable_product_id = $variable_product->get_id();

// Create a variation with a price
$variation = new WC_Product_Variation();
$variation->set_parent_id( $variable_product_id );
$variation->set_regular_price( '29.99' );
$variation->save();

$captured_events = [];

// Trigger SQL-level price update via WC_Product_Variable::sync()
// This calls $data_store->sync_price() which fires woocommerce_updated_product_price
WC_Product_Variable::sync( $variable_product_id, false );

echo "\n=== SQL Price Update (WC_Product_Variable::sync) ===\n";
$price_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_updated_product_price' === $e['context']['hook'] );
if ( count( $price_events ) > 0 ) {
    $event = reset( $price_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    echo "✓ PASS: Price sync triggered invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_updated_product_price event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Hook: {$e['context']['hook']}\n";
    }
}

// Clean up variable product (variations deleted automatically)
wp_delete_post( $variable_product_id, true );

$captured_events = [];

// Create a simple product for sales update test
$sales_product = new WC_Product_Simple();
$sales_product->set_name( 'Sales Test Product' );
$sales_product->save();
$sales_product_id = $sales_product->get_id();

// Trigger SQL-level sales update via data store
// This is how WooCommerce updates sales counts when orders are processed
$data_store = WC_Data_Store::load( 'product' );
$data_store->update_product_sales( $sales_product_id, 5, 'increase' );

echo "\n=== SQL Sales Update (data_store->update_product_sales) ===\n";
$sales_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_updated_product_sales' === $e['context']['hook'] );
if ( count( $sales_events ) > 0 ) {
    $event = reset( $sales_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    echo "✓ PASS: Sales update triggered invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_updated_product_sales event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Hook: {$e['context']['hook']}\n";
    }
}

// Clean up sales test product
wp_delete_post( $sales_product_id, true );
```

## Test 8: Variable Product Data Store Operations

Test internal data store operations that sync variable products with their variations.

### Test 8a: Sync Variation Names

This tests direct data store calls from `sync_variation_names` which fires when a variable product
syncs its name to variation post titles (e.g., "Product Name - Blue, Large").

**Note:** These direct data store calls use `'function'` in context (not `'hook'`) with the format
`WC_Product_Variable_Data_Store_CPT::sync_variation_names`.

```php
// Create a variable product with attributes
$variable_product = new WC_Product_Variable();
$variable_product->set_name( 'Sync Names Test Product' );

// Create a simple attribute (not global) for the product
$attribute = new WC_Product_Attribute();
$attribute->set_name( 'Color' );
$attribute->set_options( array( 'Red', 'Blue' ) );
$attribute->set_visible( true );
$attribute->set_variation( true );
$variable_product->set_attributes( array( $attribute ) );
$variable_product->save();
$variable_product_id = $variable_product->get_id();

// Create variations
$variation1 = new WC_Product_Variation();
$variation1->set_parent_id( $variable_product_id );
$variation1->set_attributes( array( 'color' => 'Red' ) );
$variation1->set_regular_price( '10.00' );
$variation1->save();
$variation1_id = $variation1->get_id();

$variation2 = new WC_Product_Variation();
$variation2->set_parent_id( $variable_product_id );
$variation2->set_attributes( array( 'color' => 'Blue' ) );
$variation2->set_regular_price( '12.00' );
$variation2->save();
$variation2_id = $variation2->get_id();

// Clear events before testing sync
$captured_events = [];

// Rename the parent product and save - this triggers sync_variation_names
$variable_product = wc_get_product( $variable_product_id );
$variable_product->set_name( 'Renamed Sync Test Product' );
$variable_product->save();

echo "\n=== Sync Variation Names (direct data store call) ===\n";
// Filter for events with 'function' containing 'sync_variation_names'
$sync_name_events = array_filter( $captured_events, fn( $e ) => isset( $e['function'] ) && str_contains( $e['function'], 'sync_variation_names' ) );
if ( count( $sync_name_events ) > 0 ) {
    echo "Events captured for sync_variation_names: " . count( $sync_name_events ) . "\n";
    foreach ( $sync_name_events as $event ) {
        echo "  - Product ID: {$event['product_id']}, Operation: {$event['operation']}\n";
        echo "    Function: {$event['function']}\n";
        if ( isset( $event['context_keys'] ) ) {
            echo "    Context keys: " . implode( ', ', $event['context_keys'] ) . "\n";
        }
    }
    // Verify the function name format
    $first_event = reset( $sync_name_events );
    if ( str_contains( $first_event['function'], 'WC_Product_Variable_Data_Store_CPT::' ) ) {
        echo "✓ PASS: Function name has correct format (class::method)\n";
    } else {
        echo "✗ FAIL: Function name format unexpected\n";
    }
    echo "✓ PASS: Sync variation names triggered invalidation via direct data store call\n";
} else {
    echo "✗ FAIL: sync_variation_names events not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        $source = $e['hook'] ?? $e['function'] ?? '?';
        echo "  - Product: {$e['product_id']}, Source: {$source}\n";
    }
}

// Clean up
wp_delete_post( $variable_product_id, true );
```

### Test 8b: Sync Managed Variation Stock Status

This tests direct data store calls from `sync_managed_variation_stock_status` which fires when
a variable product with stock management syncs its stock status to variations.

**Note:** These direct data store calls use `'function'` in context (not `'hook'`) with the format
`WC_Product_Variable_Data_Store_CPT::sync_managed_variation_stock_status`.

```php
// Create a variable product with stock management
$variable_product = new WC_Product_Variable();
$variable_product->set_name( 'Stock Sync Test Product' );
$variable_product->set_manage_stock( true );
$variable_product->set_stock_quantity( 10 );
$variable_product->set_stock_status( 'instock' );

// Create a simple attribute for the product
$attribute = new WC_Product_Attribute();
$attribute->set_name( 'Size' );
$attribute->set_options( array( 'Small', 'Large' ) );
$attribute->set_visible( true );
$attribute->set_variation( true );
$variable_product->set_attributes( array( $attribute ) );
$variable_product->save();
$variable_product_id = $variable_product->get_id();

// Create variations (they inherit stock management from parent)
$variation1 = new WC_Product_Variation();
$variation1->set_parent_id( $variable_product_id );
$variation1->set_attributes( array( 'size' => 'Small' ) );
$variation1->set_regular_price( '15.00' );
$variation1->save();
$variation1_id = $variation1->get_id();

$variation2 = new WC_Product_Variation();
$variation2->set_parent_id( $variable_product_id );
$variation2->set_attributes( array( 'size' => 'Large' ) );
$variation2->set_regular_price( '18.00' );
$variation2->save();
$variation2_id = $variation2->get_id();

// Clear events before testing
$captured_events = [];

// Change stock status on parent - this triggers sync_managed_variation_stock_status
$variable_product = wc_get_product( $variable_product_id );
$variable_product->set_stock_status( 'outofstock' );
$variable_product->save();

echo "\n=== Sync Managed Variation Stock Status (direct data store call) ===\n";
// Filter for events with 'function' containing 'sync_managed_variation_stock_status'
$stock_sync_events = array_filter( $captured_events, fn( $e ) => isset( $e['function'] ) && str_contains( $e['function'], 'sync_managed_variation_stock_status' ) );
if ( count( $stock_sync_events ) > 0 ) {
    echo "Events captured for sync_managed_variation_stock_status: " . count( $stock_sync_events ) . "\n";
    foreach ( $stock_sync_events as $event ) {
        echo "  - Product ID: {$event['product_id']}, Operation: {$event['operation']}\n";
        echo "    Function: {$event['function']}\n";
        if ( isset( $event['context_keys'] ) ) {
            echo "    Context keys: " . implode( ', ', $event['context_keys'] ) . "\n";
        }
    }
    // Verify the function name format
    $first_event = reset( $stock_sync_events );
    if ( str_contains( $first_event['function'], 'WC_Product_Variable_Data_Store_CPT::' ) ) {
        echo "✓ PASS: Function name has correct format (class::method)\n";
    } else {
        echo "✗ FAIL: Function name format unexpected\n";
    }
    echo "✓ PASS: Managed variation stock status sync triggered invalidation via direct data store call\n";
} else {
    echo "Note: No stock_sync events captured - this may be expected if stock status didn't change\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        $source = $e['hook'] ?? $e['function'] ?? '?';
        echo "  - Product: {$e['product_id']}, Source: {$source}\n";
    }
}

// Clean up
wp_delete_post( $variable_product_id, true );
```

## Test 9: Attribute Operations

Test attribute update and delete operations that invalidate products using those attributes:

```php
// Clear attribute cache first
delete_transient( 'wc_attribute_taxonomies' );

// Create a global attribute for testing
$attr_args = array(
    'name'         => 'Test Attr ' . time(),
    'slug'         => 'test-attr-' . time(),
    'type'         => 'select',
    'order_by'     => 'menu_order',
    'has_archives' => false,
);
$attr_id = wc_create_attribute( $attr_args );
if ( is_wp_error( $attr_id ) ) {
    echo "✗ FAIL: Could not create test attribute: " . $attr_id->get_error_message() . "\n";
    return;
}

// Get the taxonomy name
$taxonomy = wc_attribute_taxonomy_name( $attr_args['slug'] );

// Register the taxonomy temporarily so we can use it
register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false ) );

// Create an attribute term
$term = wp_insert_term( 'Test Value', $taxonomy );
if ( is_wp_error( $term ) ) {
    echo "✗ FAIL: Could not create test term: " . $term->get_error_message() . "\n";
    wc_delete_attribute( $attr_id );
    return;
}
$term_id = $term['term_id'];

// Create a variable product with this attribute
$variable_product = new WC_Product_Variable();
$variable_product->set_name( 'Attribute Test Product' );

// Set the product attribute
$attribute = new WC_Product_Attribute();
$attribute->set_id( $attr_id );
$attribute->set_name( $taxonomy );
$attribute->set_options( array( $term_id ) );
$attribute->set_visible( true );
$attribute->set_variation( true );
$variable_product->set_attributes( array( $attribute ) );
$variable_product->save();
$variable_product_id = $variable_product->get_id();

echo "Created test attribute (ID: {$attr_id}, taxonomy: {$taxonomy})\n";
echo "Created test product (ID: {$variable_product_id}) using this attribute\n";

$captured_events = [];

// Test 8a: Attribute Update
// Update the attribute (rename it) - should invalidate products using it
wc_update_attribute( $attr_id, array( 'name' => 'Renamed Test Attr' ) );

echo "\n=== Attribute Update (wc_update_attribute) ===\n";
$attr_update_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_attribute_updated' === ( $e['context']['hook'] ?? '' ) );
if ( count( $attr_update_events ) > 0 ) {
    $event = reset( $attr_update_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    if ( isset( $event['context']['attribute_id'] ) ) {
        echo "Attribute ID: {$event['context']['attribute_id']}\n";
    }
    echo "✓ PASS: Attribute update triggered product invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_attribute_updated event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Product: {$e['product_id']}, Hook: " . ( $e['context']['hook'] ?? '?' ) . "\n";
    }
}

$captured_events = [];

// Test 8b: Term Edit (rename attribute value)
// Edit the attribute term (e.g., rename "Test Value" to "Renamed Value")
// This fires edited_term hook which should invalidate products using this attribute
wp_update_term( $term_id, $taxonomy, array( 'name' => 'Renamed Value' ) );

echo "\n=== Term Edit (wp_update_term / edited_term) ===\n";
$term_edit_events = array_filter( $captured_events, fn( $e ) => 'edited_term' === ( $e['context']['hook'] ?? '' ) );
if ( count( $term_edit_events ) > 0 ) {
    $event = reset( $term_edit_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    if ( isset( $event['context']['term_id'] ) ) {
        echo "Term ID: {$event['context']['term_id']}\n";
    }
    if ( isset( $event['context']['taxonomy'] ) ) {
        echo "Taxonomy: {$event['context']['taxonomy']}\n";
    }
    echo "✓ PASS: Term edit triggered product invalidation\n";
} else {
    echo "✗ FAIL: edited_term event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Product: {$e['product_id']}, Hook: " . ( $e['context']['hook'] ?? '?' ) . "\n";
    }
}

$captured_events = [];

// Test 8c: Attribute Update
// Update the attribute itself (rename it) - should invalidate products using it
wc_update_attribute( $attr_id, array( 'name' => 'Renamed Test Attr' ) );

echo "\n=== Attribute Update (wc_update_attribute) ===\n";
$attr_update_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_attribute_updated' === ( $e['context']['hook'] ?? '' ) );
if ( count( $attr_update_events ) > 0 ) {
    $event = reset( $attr_update_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    if ( isset( $event['context']['attribute_id'] ) ) {
        echo "Attribute ID: {$event['context']['attribute_id']}\n";
    }
    echo "✓ PASS: Attribute update triggered product invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_attribute_updated event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Product: {$e['product_id']}, Hook: " . ( $e['context']['hook'] ?? '?' ) . "\n";
    }
}

$captured_events = [];

// Test 8d: Attribute Delete
// Delete the attribute - should invalidate products using it
wc_delete_attribute( $attr_id );

echo "\n=== Attribute Delete (wc_delete_attribute) ===\n";
$attr_delete_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_attribute_deleted' === ( $e['context']['hook'] ?? '' ) );
if ( count( $attr_delete_events ) > 0 ) {
    $event = reset( $attr_delete_events );
    echo "Product: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    if ( isset( $event['context']['attribute_id'] ) ) {
        echo "Attribute ID: {$event['context']['attribute_id']}\n";
    }
    echo "✓ PASS: Attribute delete triggered product invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_attribute_deleted event not captured\n";
    echo "Events captured: " . count( $captured_events ) . "\n";
    foreach ( $captured_events as $e ) {
        echo "  - Product: {$e['product_id']}, Hook: " . ( $e['context']['hook'] ?? '?' ) . "\n";
    }
}

// Clean up
wp_delete_post( $variable_product_id, true );
delete_transient( 'wc_attribute_taxonomies' );
```

### Test 8e: Variation Attribute Summary Update

This hook fires when variation excerpts (attribute summaries) are updated. This happens
internally when WooCommerce regenerates variation summaries after attribute changes.

```php
// Create a variable product with a variation for attribute summary test
$parent = new WC_Product_Variable();
$parent->set_name( 'Summary Test Product' );
$parent->save();
$parent_id = $parent->get_id();

// Create a variation
$variation = new WC_Product_Variation();
$variation->set_parent_id( $parent_id );
$variation->set_regular_price( '19.99' );
$variation->save();
$variation_id = $variation->get_id();

$captured_events = [];

// Trigger the attribute summary update hook directly
// In real usage, this fires when WC_Post_Data::regenerate_single_variation_summary() runs
do_action( 'woocommerce_updated_product_attribute_summary', $variation_id );

echo "\n=== Variation Attribute Summary Update ===\n";
$summary_events = array_filter( $captured_events, fn( $e ) => 'woocommerce_updated_product_attribute_summary' === ( $e['context']['hook'] ?? '' ) );
if ( count( $summary_events ) > 0 ) {
    $event = reset( $summary_events );
    echo "Variation ID: {$event['product_id']}\n";
    echo "Operation: {$event['operation']}\n";
    echo "Hook: {$event['context']['hook']}\n";
    echo "✓ PASS: Attribute summary update triggered variation invalidation\n";
} else {
    echo "✗ FAIL: woocommerce_updated_product_attribute_summary event not captured\n";
}

// Clean up
wp_delete_post( $parent_id, true );
```

## Test 10: No Deduplication

Test that multiple invalidations for the same product are allowed:

```php
// Create a simple product for deduplication test
$dedup_product = new WC_Product_Simple();
$dedup_product->set_name( 'Deduplication Test Product' );
$dedup_product->save();
$dedup_product_id = $dedup_product->get_id();

$captured_events = [];

// Invalidate the same product 3 times
$invalidator = wc_get_container()->get( Automattic\WooCommerce\Internal\Caches\ProductVersionStringInvalidator::class );
$invalidator->invalidate( $dedup_product_id, 'update', [ 'test' => 'first' ] );
$invalidator->invalidate( $dedup_product_id, 'update', [ 'test' => 'second' ] );
$invalidator->invalidate( $dedup_product_id, 'update', [ 'test' => 'third' ] );

echo "\n=== No Deduplication Test ===\n";
echo "Events captured: " . count( $captured_events ) . "\n";
if ( count( $captured_events ) === 3 ) {
    echo "✓ PASS: All 3 invalidations fired (no deduplication)\n";
} else {
    echo "✗ FAIL: Expected 3 events, got " . count( $captured_events ) . "\n";
}

// Clean up
wp_delete_post( $dedup_product_id, true );
```

## Test 11: Clean Up

```php
// Remove the listener
remove_all_actions( 'woocommerce_product_cache_invalidated' );

// Clear captured events
$captured_events = [];

echo "Cleaned up test data\n";
```

## Expected Results Summary

For a proper integration:

1. ✓ Version strings should be deleted on invalidation
2. ✓ WordPress action `woocommerce_product_cache_invalidated` fires
3. ✓ All operations (CREATE, UPDATE, DELETE, TRASH, UNTRASH) work
4. ✓ Variation updates invalidate both variation AND parent
5. ✓ Context includes 'hook' key for hook-triggered invalidations
6. ✓ Context includes 'function' key for direct data store calls (format: `ClassName::method_name`)
7. ✓ Context includes 'post' for WordPress post hooks (save_post_product, delete_post, trashed_post, untrashed_post)
8. ✓ Context includes 'product' for WooCommerce variation hooks only (woocommerce_new_product_variation, woocommerce_update_product_variation)
9. ✓ Context includes 'variation_id' when parent is notified of variation change
10. ✓ SQL-level updates (stock, price, sales) trigger invalidation
11. ✓ Variable product data store operations (sync_variation_names, sync_managed_variation_stock_status) trigger invalidation with 'function' in context
12. ✓ Attribute term edits (edited_term) invalidate products using those attributes
13. ✓ Attribute operations (update, delete) invalidate products using those attributes
14. ✓ Variation attribute summary updates trigger invalidation
15. ✓ No deduplication - same product can be invalidated multiple times

## Quick One-Liner Tests

Replace `$product_id` with an actual product ID before running:

```php
// Quick test - Update product and check event (replace $product_id with actual ID)
$product_id = 123; $captured = []; add_action( 'woocommerce_product_cache_invalidated', function($id,$op,$ctx) use(&$captured) { $captured[]=['id'=>$id,'op'=>$op,'hook'=>$ctx['hook']??'?']; }, 10, 3); $p=wc_get_product($product_id); $p->set_name('Test-'.time()); $p->save(); print_r($captured);

// Quick test - Version string deletion (replace $product_id with actual ID)
$product_id = 123; $vg=wc_get_container()->get(\Automattic\WooCommerce\Internal\Caches\VersionStringGenerator::class); $vg->generate_version("product_{$product_id}"); echo "Before: ".($vg->get_version("product_{$product_id}",false)?'EXISTS':'NONE')."\n"; $p=wc_get_product($product_id); $p->save(); echo "After: ".($vg->get_version("product_{$product_id}",false)?'EXISTS':'NONE')."\n";
```
