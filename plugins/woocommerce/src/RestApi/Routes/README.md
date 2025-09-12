# REST API Route Controllers

This directory contains route controllers for WooCommerce REST API endpoints, organized by major version.

## Version Structure

Each major version of the REST API has its own directory:

- `V4/` - REST API v4 controllers
- `V5/` - REST API v5 controllers (future)
- `V6/` - REST API v6 controllers (future)

## Legacy API Versions

Previous versions of the REST API (v1, v2, v3) can be found in the legacy includes directory:

```
plugins/woocommerce/includes/rest-api/
```

These legacy controllers are maintained for backwards compatibility and should not be modified for new features.

## V4 Controllers

Route controllers for the WooCommerce REST API V4 endpoints are placed in the `V4/` directory.

### Examples

- `V4/Orders/` - Controller for orders endpoints
- `V4/OrderNotes/` - Controller for order notes endpoints

### Naming Convention

The main controller class should be named `Controller.php` with the correct namespace for the route.

For example:

- `V4/Orders/Controller.php` with namespace `Automattic\WooCommerce\RestApi\Routes\V4\Orders`
- `V4/OrderNotes/Controller.php` with namespace `Automattic\WooCommerce\RestApi\Routes\V4\OrderNotes`

### Controller Structure

Controllers should extend `V4/AbstractController.php`, which extends `WP_REST_Controller`.
