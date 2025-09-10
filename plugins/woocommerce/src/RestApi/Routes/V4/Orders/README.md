# WooCommerce REST API v4 - Orders Endpoint

This directory contains the implementation of the WooCommerce REST API v4 Orders endpoint (`/wp-json/wc/v4/orders`).

## Overview

The v4 Orders endpoint represents a **complete architectural rewrite** from the v3 implementation. The Orders endpoint was built with a modular architecture using the `Automattic\WooCommerce` namespace and extending `WP_REST_Controller` directly.

**Current Status**: Experimental feature requiring the `rest-api-v4` feature flag.

## Architecture

- **Namespace**: `wc/v4`
- **Base Class**: Extends `WP_REST_Controller` directly (not WooCommerce base classes)
- **Location**: Under `src/` directory with `Automattic\WooCommerce` namespace
- **Modular Design**: Separated into focused utility classes for better maintainability

### File Structure

```markdown
src/RestApi/
├── Routes/V4/Orders/
│   ├── Controller.php      # Main controller with route registration and CRUD operations
│   ├── DataUtils.php       # Data preparation and validation utilities
│   ├── QueryUtils.php      # Query parameter handling and validation
│   ├── ResponseUtils.php   # Response formatting and data transformation
│   └── README.md           # This documentation
└── Schemas/V4/
    └── OrderSchema.php     # Schema definition and field management
```

## Key Architectural Changes

### 1. **Modular Design**

- **V3**: Monolithic controller with all functionality in one class
- **V4**: Separated concerns into focused utility classes:
    - `DataUtils`: Handles data preparation, validation, and database operations
    - `QueryUtils`: Manages query parameters and filtering
    - `ResponseUtils`: Formats responses and transforms data
    - `OrderSchema`: Defines schema and field properties

### 2. **Modern PHP Standards**

- **V3**: Traditional PHP without strict typing
- **V4**: Uses `declare( strict_types=1 )` and modern PHP features
- **V3**: Mixed namespace usage
- **V4**: Consistent `Automattic\WooCommerce\RestApi\Routes\V4\Orders` namespace

### 3. **Direct WP_REST_Controller Extension**

- **V3**: Extends `WC_REST_Orders_V2_Controller` → `WC_REST_CRUD_Controller` → `WC_REST_Posts_Controller` → `WC_REST_Controller`
- **V4**: Extends `WP_REST_Controller` directly for cleaner inheritance

### 4. **Enhanced Coupon Handling**

- **V3**: Coupon logic embedded in main controller
- **V4**: Dedicated coupon handling with proper removal before adding new coupons
- **Bug Fix**: V4 fixes a critical bug where coupon replacement didn't work properly in V3

### 5. **Improved Line Item Management**

- **V3**: Basic line item handling
- **V4**: Enhanced line item management with proper validation and error handling
- **Feature**: Better support for removing items by setting them to null/zero

## API Endpoints

### Core Endpoints

- `GET /wp-json/wc/v4/orders` - List orders
- `POST /wp-json/wc/v4/orders` - Create order
- `GET /wp-json/wc/v4/orders/{id}` - Get single order
- `PUT /wp-json/wc/v4/orders/{id}` - Update order
- `DELETE /wp-json/wc/v4/orders/{id}` - Delete order

### Batch Operations

- **Status**: Not implemented in V4 (intentionally removed)
- **Reason**: Following Store API pattern, batch operations should be implemented as separate routes
- **Note**: V3 had batch operations, but V4 removes them in favor of a cleaner architecture

## Breaking Changes from V3

### 1. **No Batch Endpoint**

- **V3**: Had `/wp-json/wc/v3/orders/batch` endpoint
- **V4**: Batch endpoint removed (following Store API pattern)
- **Migration**: Use individual API calls or implement batch as separate route

### 2. **Enhanced Coupon Behavior**

- **V3**: Coupon replacement had bugs (old coupons not properly removed)
- **V4**: Fixed coupon replacement - old coupons are properly removed before adding new ones
- **Impact**: More reliable coupon updates, but behavior may differ from V3

### 3. **Improved Line Item Handling**

- **V3**: Limited line item removal capabilities
- **V4**: Enhanced line item removal by setting items to null/zero
- **Impact**: More flexible line item management

## Development Philosophy

The V4 Orders endpoint represents a **forward-looking architecture** that:

- **Prioritizes maintainability** over backward compatibility
- **Uses modern PHP standards** and best practices
- **Separates concerns** into focused utility classes
- **Fixes known bugs** from V3 implementation
- **Prepares for future enhancements** with a clean foundation

## Change Log

### 2025-09-08 - Initial V4 Implementation

**Summary**: Complete architectural rewrite of the Orders endpoint with modern, modular design  
**PR**: [To be added]  

**Key Changes**:

- **Modular Architecture**: Separated into focused utility classes (DataUtils, QueryUtils, ResponseUtils, OrderSchema)
- **Modern PHP**: Uses strict typing and `Automattic\WooCommerce` namespace
- **Direct WP_REST_Controller**: Extends WordPress REST controller directly
- **Enhanced Coupon Handling**: Fixed coupon replacement bug from V3
- **Improved Line Item Management**: Better validation and removal capabilities
- **Removed Batch Operations**: Following Store API pattern for cleaner architecture
- **Added Product Data**: Added product data to line items (product_type, is_virtual, is_downloadable, needs_shipping, permalink)

**Breaking Changes**:

- **No Batch Endpoint**: `/batch` endpoint removed (use individual calls or implement separately)
- **Enhanced Coupon Behavior**: Coupon replacement now works correctly (may differ from V3)
- **Improved Line Item Handling**: Enhanced removal capabilities

---

## Future Changes Template

When adding new changes, please use this format:

### YYYY-MM-DD - Brief Change Description

**Summary**: Detailed description of what was changed  
**PR**: [#XXXXX](https://github.com/woocommerce/woocommerce/pull/XXXXX)  

**Breaking Changes**: Description of any breaking changes or "None"

## Testing

The V4 Orders endpoint includes comprehensive tests covering:

- **Coupon Operations**: Adding, removing, and replacing coupons
- **Line Item Management**: Creating, updating, and removing line items
- **Order CRUD**: Full create, read, update, delete operations
- **Error Handling**: Validation and error response testing

Test files: `tests/php/includes/rest-api/Controllers/Version4/Orders/`
