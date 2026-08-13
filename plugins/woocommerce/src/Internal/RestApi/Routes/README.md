# REST API Route Controllers

This directory contains route controllers and schemas for WooCommerce REST API endpoints, organized by major version.

## Version Structure

Each major version of the REST API has its own directory:

- `V4/` - REST API v4 controllers and schemas
- `V5/` - REST API v5 controllers and schemas (future)
- `V6/` - REST API v6 controllers and schemas (future)

## Legacy API Versions

Previous versions of the REST API (v1, v2, v3) can be found in the legacy includes directory:

```markdown
plugins/woocommerce/includes/rest-api/
```

These legacy controllers are maintained for backwards compatibility and should not be modified for new features.

## V4 Controllers and Schemas

Route controllers and schemas for the WooCommerce REST API V4 endpoints are placed in the `V4/` directory.

### Directory Structure

Each route should have its own directory containing both controller and schema files:

```markdown
V4/
├── AbstractController.php
├── AbstractSchema.php
├── Orders/
│   ├── Controller.php
│   └── OrderSchema.php
├── OrderNotes/
│   ├── Controller.php
│   └── OrderNoteSchema.php
└── Products/
    ├── Controller.php
    └── ProductSchema.php
```

### Naming Convention

#### Controllers

The main controller class should be named `Controller.php` with the correct namespace for the route.

For example:

- `V4/Orders/Controller.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders`
- `V4/OrderNotes/Controller.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes`

#### Schemas

The schema class should be named `{SingularResourceType}Schema.php` with the correct namespace for the route.

For example:

- `V4/Orders/OrderSchema.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders`
- `V4/OrderNotes/OrderNoteSchema.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes`

### Controller Structure

Controllers should extend `V4/AbstractController.php`, which extends `WP_REST_Controller`.

### Schema Structure

Schemas should extend `V4/AbstractSchema.php` and implement the following:

1. **IDENTIFIER constant** - Unique identifier for the schema (e.g., `'order'`, `'order_note'`)
2. **get_item_properties() method** - Return array of schema properties
3. **Context constants** - Define available contexts (`VIEW_EDIT_EMBED_CONTEXT`, `VIEW_EDIT_CONTEXT`)

### Schema Contexts

- **view** - Data visible when viewing the resource
- **edit** - Data visible when editing the resource
- **embed** - Data visible when the resource is embedded in another response

### Schema Properties

See the WordPress documentation for more information on supported schema properties.

<https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/>
