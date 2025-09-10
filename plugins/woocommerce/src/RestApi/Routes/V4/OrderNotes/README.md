# WooCommerce REST API v4 - Order Notes Endpoint

This directory contains the implementation of the WooCommerce REST API v4 Order Notes endpoint (`/wp-json/wc/v4/order-notes`).

## Overview

The v4 Order Notes endpoint represents a **complete architectural rewrite** from the v3 implementation. The Order Notes endpoint was built with a modular architecture using the `Automattic\WooCommerce` namespace and extending `WP_REST_Controller` directly.

**Current Status**: Experimental feature requiring the `rest-api-v4` feature flag.

## Architecture

- **Namespace**: `wc/v4`
- **Base Class**: Extends `WP_REST_Controller` directly (not WooCommerce base classes)
- **Location**: Under `src/` directory with `Automattic\WooCommerce` namespace
- **Modular Design**: Separated into focused utility classes for better maintainability

### File Structure

```markdown
src/RestApi/
├── Routes/V4/OrderNotes/
│   └── Controller.php      # Main controller with route registration and CRUD operations
└── Schemas/V4/
    └── OrderNoteSchema.php # Schema definition and field management
```

## API Endpoints

### Core Endpoints

- `GET /wp-json/wc/v4/order-notes` - List order notes
- `POST /wp-json/wc/v4/order-notes` - Create order note
- `GET /wp-json/wc/v4/order-notes/{id}` - Get single order note
- `PUT /wp-json/wc/v4/order-notes/{id}` - Update order note
- `DELETE /wp-json/wc/v4/order-notes/{id}` - Delete order note
