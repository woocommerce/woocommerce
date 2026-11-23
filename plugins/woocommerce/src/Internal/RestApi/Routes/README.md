# REST API Route Controllers

This directory holds route controllers and schemas for WooCommerce REST API endpoints, organized by
major version.

## Version Structure

Each major version of the REST API gets its own directory, e.g. `V5/`, `V6/`.

`V4/` previously held the experimental REST API v4 implementation. It was removed; the directory now
only contains no-op compatibility stubs kept to avoid mid-upgrade fatals (see
`src/Internal/RestApi/README.md`).

## Legacy API Versions

Previous versions of the REST API (v1, v2, v3) can be found in the legacy includes directory:

```markdown
plugins/woocommerce/includes/rest-api/
```

These legacy controllers are maintained for backwards compatibility and should not be modified for new features.

## Adding a New Version

### Directory Structure

Each route should have its own directory containing both controller and schema files:

```markdown
V5/
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

- `V5/Orders/Controller.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V5\Orders`
- `V5/OrderNotes/Controller.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V5\OrderNotes`

#### Schemas

The schema class should be named `{SingularResourceType}Schema.php` with the correct namespace for the route.

For example:

- `V5/Orders/OrderSchema.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V5\Orders`
- `V5/OrderNotes/OrderNoteSchema.php` with namespace `Automattic\WooCommerce\Internal\RestApi\Routes\V5\OrderNotes`

### Controller Structure

Controllers should extend an `AbstractController.php` in their version directory, which extends `WP_REST_Controller`.

### Schema Structure

Schemas should extend an `AbstractSchema.php` in their version directory and implement the following:

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
