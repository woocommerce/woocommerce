# REST API Schema Controllers

This directory contains schema controllers for WooCommerce REST API endpoints, organized by major version. Schema controllers define the structure and validation rules for API request/response data.

## Version Structure

Each major version of the REST API has its own schema directory:

- `V4/` - REST API v4 schemas
- `V5/` - REST API v5 schemas (future)
- `V6/` - REST API v6 schemas (future)

## Legacy API Schemas

Previous versions of the REST API schemas (v1, v2, v3) can be found in the legacy includes directory:

```
plugins/woocommerce/includes/rest-api/
```

These legacy schemas are maintained for backwards compatibility and should not be modified for new features.

## V4 Schemas

Schema controllers for the WooCommerce REST API V4 endpoints are placed in the `V4/` directory.

### Examples

- `V4/OrderSchema.php` - Schema for order endpoints
- `V4/OrderNoteSchema.php` - Schema for order note endpoints

### Naming Convention

Schema classes should be named with the `Schema` suffix and placed directly in the version directory.

For example:

- `V4/OrderSchema.php` with namespace `Automattic\WooCommerce\RestApi\Schemas\V4`
- `V4/OrderNoteSchema.php` with namespace `Automattic\WooCommerce\RestApi\Schemas\V4`

### Schema Structure

Schemas should extend `V4/AbstractSchema.php` and implement the following:

1. **IDENTIFIER constant** - Unique identifier for the schema (e.g., `'order'`, `'order_note'`)
2. **get_item_properties() method** - Return array of schema properties
3. **Context constants** - Define available contexts (`VIEW_EDIT_EMBED_CONTEXT`, `VIEW_EDIT_CONTEXT`)

### Example Schema Implementation

```php
<?php
namespace Automattic\WooCommerce\RestApi\Schemas\V4;

class OrderSchema extends AbstractSchema {
    const IDENTIFIER = 'order';

    public function get_item_properties() {
        return array(
            'id' => array(
                'description' => __( 'Unique identifier for the order.', 'woocommerce' ),
                'type'        => 'integer',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ),
            'status' => array(
                'description' => __( 'Order status.', 'woocommerce' ),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'enum'        => array( 'pending', 'processing', 'completed' ),
            ),
        );
    }
}
```

### Schema Contexts

- **view** - Data visible when viewing the resource
- **edit** - Data visible when editing the resource
- **embed** - Data visible when the resource is embedded in another response

### Schema Properties

See the WordPress documentation for more information on supported schema properties.

<https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/>
