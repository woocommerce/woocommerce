---
post_title: Order fulfillments
sidebar_label: Order fulfillments
category_slug: order-fulfillments
---

# Order fulfillments

Order fulfillments add a first-class fulfillment record to WooCommerce orders. A single order can have multiple fulfillment records, each with its own status, item allocations, tracking information, and customer notification flow.

This feature is intended for developers who need to create, update, read, or extend fulfillment data from custom code, integrations, or admin tooling. WooCommerce exposes order fulfillments through a dedicated data store, REST API controllers, custom database tables, and WordPress hooks.

## Architecture overview

The fulfillment system is built with several key components working together to provide a complete fulfillment workflow.

### Backend components

- **FulfillmentsManager**: Core manager class handling lifecycle hooks and business logic for fulfillment operations.
- **Fulfillment**: The main data object representing a fulfillment with methods for accessing and modifying fulfillment state.
- **FulfillmentsDataStore**: Data persistence layer that handles reading and writing fulfillment data to the custom database tables.
- **FulfillmentsSettings**: Handles WooCommerce settings integration including auto-fulfill options and the shipping providers admin page.
- **OrderFulfillmentsRestController**: REST API endpoints for v3 order-scoped routes, allowing client access to fulfillments under the `/wp-json/wc/v3/orders/{order_id}/fulfillments` namespace.

### Frontend components

- **React-based UI**: Modern interface built with WordPress components used by the WooCommerce admin and order details pages.
- **WP Data Store**: State management for fulfillment data using the WordPress data API, available under the `order/fulfillments` namespace.

## Enable the feature

The fulfillments data store, database tables, and REST routes are only registered when the `fulfillments` feature is enabled.

For local development, the simplest way to enable it is with WP-CLI:

```bash
wp option update woocommerce_feature_fulfillments_enabled yes
```

When the feature initializes, WooCommerce registers the `order-fulfillment` data store, creates the fulfillment tables if they do not already exist, and exposes the REST API endpoints used by the WooCommerce admin and customer-facing order views.

## Data model

Order fulfillments are stored in two custom tables:

- `wp_wc_order_fulfillments`
- `wp_wc_order_fulfillment_meta`

Each fulfillment row stores the relationship back to the owning entity together with the current fulfillment state.

| Field | Description |
| --- | --- |
| `entity_type` | The supported entity class. Core currently registers the order controller for `WC_Order`. |
| `entity_id` | The owning order ID. |
| `status` | The fulfillment status slug, for example `unfulfilled` or `fulfilled`. |
| `is_fulfilled` | Boolean state used to distinguish draft or pending records from fulfilled records. |
| `date_updated` | Last update timestamp. |
| `date_deleted` | Soft-delete timestamp. Deleted fulfillments stay in the table and are filtered out by the controllers. |

The associated metadata table stores the fulfillment payload that developers usually care about most:

| Meta key | Description |
| --- | --- |
| `_items` | Array of line item allocations, each with an `item_id` and `qty`. |
| `_tracking_number` | Carrier tracking number. |
| `_shipment_provider` | Provider slug used to build tracking links and UI labels. |
| `_tracking_url` | Explicit tracking URL when a provider template is not enough. |
| `_date_fulfilled` | Timestamp set when a fulfillment transitions to a fulfilled state. |
| `_is_locked` | Internal flag used to prevent edits in specific workflows. |
| `_lock_message` | Optional message shown when a fulfillment is locked. |

The `Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment` object wraps these values with helper methods such as `get_items()`, `set_items()`, `get_tracking_number()`, and `set_tracking_url()`.

## PHP class reference

### Fulfillment

The main data object representing a fulfillment. The `Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment` class extends `WC_Data` and wraps fulfillment record values with helper methods.

#### Core methods

- `get_id()`: Get fulfillment ID
- `set_id( $id )`: Set fulfillment ID
- `get_entity_type()`: Get associated entity type (typically `WC_Order`)
- `set_entity_type( $entity_type )`: Set associated entity type
- `get_entity_id()`: Get associated entity ID
- `set_entity_id( $entity_id )`: Set associated entity ID

#### Status management

- `get_status()`: Get fulfillment status
- `set_status( $status )`: Set fulfillment status
- `get_is_fulfilled()`: Check if fulfillment is completed
- **Note**: There is no `set_is_fulfilled()` method; the fulfilled state is calculated automatically when you set the fulfillment status via `set_status()`.

#### Items management

- `get_items()`: Get fulfilled items array
- `set_items( $items )`: Set fulfillment items

The items array contains objects with at least `item_id` (the order line item ID, not the product ID) and `qty` (quantity fulfilled).

#### Date management

- `get_date_updated()`: Get last updated timestamp
- `set_date_updated( $date )`: Set last updated timestamp
- `get_date_fulfilled()`: Get fulfillment completion timestamp
- `set_date_fulfilled( $date )`: Set fulfillment completion timestamp
- `get_date_deleted()`: Get deletion timestamp (for soft deletes)
- `set_date_deleted( $date )`: Set deletion timestamp

#### Lock management

- `is_locked()`: Check if fulfillment is locked
- `get_lock_message()`: Get lock message
- `set_locked( $locked, $message )`: Set lock status and optional message

**What locking means**: A locked fulfillment cannot be edited by merchants through the WooCommerce admin UI. Extensions use locking to communicate that a fulfillment is being managed externally and should not be modified manually. Common use cases include:

- Marking fulfillments created or updated automatically by a 3rd-party shipping integration as read-only.
- Preventing further edits once a fulfillment has been handed off to a carrier and confirmed.
- Enforcing immutability for fulfillments in specific custom statuses that your extension controls.

When `set_locked( true, 'Managed by MyShipping plugin' )` is called, the optional message is shown in the admin UI to explain why the fulfillment cannot be edited.

#### Data access

- `get_order()`: Get the `WC_Order` object associated with this fulfillment (works when entity type is an order)
- `get_raw_data()`: Get all data including metadata as a raw array
- `get_raw_meta_data()`: Get raw metadata array

#### Inherited methods from WC_Data

- `save()`: Create or update the fulfillment in the database
- `delete( $force_delete = false )`: Delete the fulfillment (soft delete by default)
- `get_data()`: Get all data as an associative array
- `get_changes()`: Get data that has been changed since the last save
- `apply_changes()`: Apply changes and mark the object as clean

#### Tracking and shipping convenience methods

New in 10.7.0, the `Fulfillment` class provides convenience methods for the most common tracking-related metadata keys:

- `get_tracking_number()`: Get the carrier tracking number (`_tracking_number` meta)
- `set_tracking_number( $tracking_number )`: Set the carrier tracking number
- `get_shipment_provider()`: Get the shipment provider slug (`_shipment_provider` meta)
- `set_shipment_provider( $shipment_provider )`: Set the shipment provider slug
- `get_tracking_url()`: Get the explicit tracking URL (`_tracking_url` meta)
- `set_tracking_url( $tracking_url )`: Set the explicit tracking URL
- `get_item_count()`: Get the total quantity of items across all item allocations

These methods are equivalent to calling the corresponding `get_meta()` / `update_meta_data()` methods directly, but provide type safety and a cleaner API.

#### Metadata management

The Fulfillment class inherits standard WC_Data metadata methods:

- `get_meta_data()`: Get all metadata objects
- `get_meta( $key, $single = true, $context = 'view' )`: Get metadata value by key
- `meta_exists( $key )`: Check if metadata key exists
- `add_meta_data( $key, $value, $unique = false )`: Add new metadata
- `update_meta_data( $key, $value, $meta_id = 0 )`: Update existing metadata
- `delete_meta_data( $key )`: Delete metadata by key
- `delete_meta_data_by_mid( $meta_id )`: Delete metadata by ID
- `set_meta_data( $data )`: Set all metadata from array
- `save_meta_data()`: Save metadata changes to database

**Private vs public metadata**: Metadata keys prefixed with an underscore (`_`) are considered private/internal and are not displayed in the admin UI. Keys without an underscore prefix are treated as public metadata and are shown to the merchant in the fulfillment section below the tracking number field. The label for public keys can be customized using the `woocommerce_fulfillment_meta_key_translations` and `woocommerce_fulfillment_translate_meta_key` filters.

#### Usage examples

**Creating a new fulfillment:**

```php
use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;

$fulfillment = new Fulfillment();
$fulfillment->set_entity_type( WC_Order::class );
$fulfillment->set_entity_id( '123' );
$fulfillment->set_status( 'fulfilled' );

// Add items to fulfillment (item_id is the order line item ID, not the product ID)
$items = [
    [ 'item_id' => 456, 'qty' => 2 ],
    [ 'item_id' => 789, 'qty' => 1 ]
];
$fulfillment->set_items( $items );

// Set tracking information using convenience methods (available since 10.7.0)
$fulfillment->set_tracking_number( '1Z999AA1234567890' );
$fulfillment->set_shipment_provider( 'ups' );

// Add public metadata (shown in the admin UI below the tracking number)
$fulfillment->add_meta_data( 'custom_public_note', 'Handle with care' );

// Save the fulfillment
$fulfillment->save();
```

**Working with existing fulfillments:**

```php
$existing = new Fulfillment( 456 );

// Check what data exists
$all_data = $existing->get_data(); // Get all data as array
$changes = $existing->get_changes(); // Get unsaved changes

// Update tracking using convenience methods (available since 10.7.0)
$existing->set_tracking_number( '1Z999BB1234567890' );
$existing->set_shipment_provider( 'fedex' );
$existing->save();

// Or update arbitrary metadata directly
$existing->delete_meta_data( 'custom_notes' ); // Remove metadata
$existing->save();

// Check if specific metadata exists
if ( $existing->meta_exists( '_shipment_provider' ) ) {
    $provider = $existing->get_meta( '_shipment_provider' );
}

// Lock/unlock fulfillment
$existing->set_locked( true, 'Processing by external system' );
if ( $existing->is_locked() ) {
    echo $existing->get_lock_message();
}

// Get associated order
$order = $existing->get_order();
if ( $order instanceof WC_Order ) {
    echo "Order #" . $order->get_id();
}

// Delete a fulfillment
$existing->delete(); // Soft delete
$existing->delete( true ); // Force delete (permanent)
```

**Creating a fulfillment programmatically:**

```php
function create_fulfillment_for_order( $order_id, $items, $tracking_number = '' ) {
    $fulfillment = new Fulfillment();
    $fulfillment->set_entity_type( WC_Order::class );
    $fulfillment->set_entity_id( (string) $order_id );
    $fulfillment->set_status( 'fulfilled' );

    // Add items
    $fulfillment->set_items( $items );

    // Add tracking if provided (using convenience method available since 10.7.0)
    if ( ! empty( $tracking_number ) ) {
        $fulfillment->set_tracking_number( $tracking_number );
    }

    $fulfillment->save();
    return $fulfillment;
}
```

### Custom shipping provider

To extend fulfillment functionality, you can register custom shipping providers in two ways.

#### Option 1: Via the admin UI

Navigate to **WooCommerce → Settings → Shipping → Shipping providers** to add a provider name, icon, and tracking URL template through the admin interface. Providers added this way appear immediately in the fulfillment tracking provider selector.

> **Note**: Providers added through the UI do not support automatic tracking number parsing. If you need to detect a specific tracking number format and auto-select the correct provider, you must register the provider in code (Option 2) and implement `try_parse_tracking_number()`.

#### Option 2: Via code (supports tracking number parsing)

```php
use Automattic\WooCommerce\Admin\Features\Fulfillments\Providers\AbstractShippingProvider;

class My_Custom_Shipping_Provider extends AbstractShippingProvider {
    public function get_key(): string {
        return 'my-custom-provider';
    }

    public function get_name(): string {
        return 'My Custom Provider';
    }

    public function get_icon(): string {
        return 'https://example.com/my-carrier-icon.svg';
    }

    public function get_tracking_url( string $tracking_number ): string {
        return 'https://mycarrier.com/track/' . rawurlencode( $tracking_number );
    }

    public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
        if ( 1 === preg_match( '/^MC[0-9]{10}$/', $tracking_number ) ) {
            return [
                'url'             => $this->get_tracking_url( $tracking_number ),
                'ambiguity_score' => 100,
            ];
        }
        return null;
    }
}

// Register your custom shipping provider.
add_filter( 'woocommerce_fulfillment_shipping_providers', function( $providers ) {
    $providers[] = My_Custom_Shipping_Provider::class;
    return $providers;
} );
```

## Frontend integration

The WooCommerce admin uses a WordPress data store for state management of fulfillment data. You can access and interact with fulfillments from React components using the standard WordPress data API.

### Data store

The fulfillments data store is available under the `order/fulfillments` namespace:

```javascript
import { useSelect, useDispatch } from '@wordpress/data';

function MyComponent({ orderId }) {
    const { fulfillments, isLoading } = useSelect( ( select ) => ({
        fulfillments: select( 'order/fulfillments' ).readFulfillments( orderId ),
        isLoading: select( 'order/fulfillments' ).isLoading( orderId ),
    }));

    const { saveFulfillment } = useDispatch( 'order/fulfillments' );

    // Component logic here
}
```

### Available actions

- `saveFulfillment( orderId, fulfillment, notifyCustomer )`: Create or save a fulfillment
- `updateFulfillment( orderId, fulfillment, notifyCustomer )`: Update an existing fulfillment
- `deleteFulfillment( orderId, fulfillmentId, notifyCustomer )`: Delete a fulfillment

### Available selectors

- `getOrder( orderId )`: Get order details
- `readFulfillments( orderId )`: Get all fulfillments for an order
- `readFulfillment( orderId, fulfillmentId )`: Get a specific fulfillment
- `isLoading( orderId )`: Check if data is currently loading
- `getError( orderId )`: Get any error that occurred during API calls

## Best practices for developers

When working with the fulfillments feature, follow these guidelines to ensure robust, secure, and performant implementations:

1. **Error handling**: Always wrap API calls in try-catch blocks to gracefully handle failures.
2. **Data validation**: Validate fulfillment data before saving, especially item allocations and tracking information.
3. **Hook priority**: Use appropriate hook priorities when registering callbacks to ensure proper execution order and allow other extensions to hook at the same priority.
4. **Performance**: Use batch operations for bulk fulfillment updates rather than looping individual operations.
5. **Security**: Always validate user permissions before allowing fulfillment operations. Use the REST API permission checks as a reference for capability requirements.

## REST API and hooks

WooCommerce exposes order fulfillments through order-scoped REST API v3 routes.

- Use the [REST API reference](./rest-api.md) for endpoint shapes, permissions, and example payloads.
- Use the [hooks reference](./hooks.md) for lifecycle filters, notification actions, provider filters, and email template hooks.

## Shipping providers and tracking lookups

WooCommerce ships with a provider registry that powers the admin UI and tracking link generation. Developers can extend that registry with filters (see [Custom shipping provider](#custom-shipping-provider) above) or through the **WooCommerce → Settings → Shipping → Shipping providers** admin page.

The feature also exposes a tracking lookup route that runs the `woocommerce_fulfillment_parse_tracking_number` filter with the store base country and the order shipping country. That makes it possible to normalize tracking numbers or infer the correct provider without modifying the REST controller.
