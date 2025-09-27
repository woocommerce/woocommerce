---
post_title: MCP Abilities Reference
sidebar_label: MCP Abilities
category_slug: mcp
---

# MCP Abilities Reference

This document provides a comprehensive reference of all WooCommerce operations currently exposed as MCP tools through the WordPress Abilities API. Each ability represents a specific WooCommerce operation that AI assistants can discover and execute with proper authentication.

## Overview

The current list of WooCommerce MCP abilities are REST API endpoints exposed as structured tools through the WordPress Abilities API. This implementation leverages existing WooCommerce REST controllers to provide immediate functionality. Future versions may include custom implementations that go beyond REST API bridging to provide specialized tools optimized for AI interactions.

Each current ability includes:

- **Input Schema**: JSON Schema defining required and optional parameters
- **Output Schema**: JSON Schema describing the response structure
- **Route Parameters**: Named placeholders in URLs (e.g., `{order_id}`, `{id}`)
- **Permissions**: Based on WooCommerce REST API key scope (`read`, `write`, `read_write`)

## Product Management

### List Products
**ID**: `woocommerce/products-list`
**Operation**: `list`
**Route**: `/wc/v3/products`
**Description**: Retrieve a paginated list of products with optional filters for status, category, price range, and other attributes.

**Key Parameters**:
- `per_page` (integer): Maximum number of items (1-100, default: 10)
- `page` (integer): Current page number (default: 1)
- `search` (string): Search products by name or description
- `status` (string): Filter by product status (`publish`, `draft`, `private`, etc.)
- `category` (string): Filter by category ID
- `min_price` / `max_price` (string): Price range filtering
- `orderby` (string): Sort by field (`date`, `title`, `price`, `popularity`, etc.)
- `order` (string): Sort direction (`asc` or `desc`)

### Get Product
**ID**: `woocommerce/products-get`
**Operation**: `get`
**Route**: `/wc/v3/products/{id}`
**Description**: Retrieve detailed information about a single product by ID, including price, description, images, and metadata.

**Route Parameters**:
- `id` (integer, required): Product ID

### Create Product
**ID**: `woocommerce/products-create`
**Operation**: `create`
**Route**: `/wc/v3/products`
**Description**: Create a new product with name, price, description, and other product attributes.

**Key Parameters**:
- `name` (string, required): Product name
- `type` (string): Product type (`simple`, `grouped`, `external`, `variable`)
- `regular_price` (string): Regular price
- `sale_price` (string): Sale price
- `description` (string): Full product description
- `short_description` (string): Brief product summary
- `categories` (array): Category assignments
- `images` (array): Product images
- `attributes` (array): Product attributes (for variable products)
- `status` (string): Product status (`publish`, `draft`, etc.)

### Update Product
**ID**: `woocommerce/products-update`
**Operation**: `update`
**Route**: `/wc/v3/products/{id}`
**Description**: Update an existing product by modifying its attributes such as price, stock, description, or metadata.

**Route Parameters**:
- `id` (integer, required): Product ID

### Delete Product
**ID**: `woocommerce/products-delete`
**Operation**: `delete`
**Route**: `/wc/v3/products/{id}`
**Description**: Permanently delete a product from the store. This action cannot be undone.

**Route Parameters**:
- `id` (integer, required): Product ID

## Product Variations

Product variations are used with variable products to represent different combinations of attributes (size, color, etc.).

### List Product Variations
**ID**: `woocommerce/product-variations-list`
**Operation**: `list`
**Route**: `/wc/v3/products/{product_id}/variations`
**Description**: Retrieve all variations for a variable product, including pricing, stock, and attribute combinations.

**Route Parameters**:
- `product_id` (integer, required): Parent product ID

### Get Product Variation
**ID**: `woocommerce/product-variations-get`
**Operation**: `get`
**Route**: `/wc/v3/products/{product_id}/variations/{id}`
**Description**: Retrieve detailed information about a specific product variation, including attributes, pricing, and stock.

**Route Parameters**:
- `product_id` (integer, required): Parent product ID
- `id` (integer, required): Variation ID

### Create Product Variation
**ID**: `woocommerce/product-variations-create`
**Operation**: `create`
**Route**: `/wc/v3/products/{product_id}/variations`
**Description**: Create a new product variation with specific attributes, pricing, and stock settings.

**Route Parameters**:
- `product_id` (integer, required): Parent product ID

**Key Parameters**:
- `regular_price` (string): Variation price
- `attributes` (array, required): Attribute combinations (e.g., `[{"name": "Size", "option": "Large"}]`)
- `stock_quantity` (integer): Stock level
- `manage_stock` (boolean): Enable stock management

### Update Product Variation
**ID**: `woocommerce/product-variations-update`
**Operation**: `update`
**Route**: `/wc/v3/products/{product_id}/variations/{id}`
**Description**: Update an existing product variation by modifying attributes, pricing, stock, or other settings.

**Route Parameters**:
- `product_id` (integer, required): Parent product ID
- `id` (integer, required): Variation ID

### Delete Product Variation
**ID**: `woocommerce/product-variations-delete`
**Operation**: `delete`
**Route**: `/wc/v3/products/{product_id}/variations/{id}`
**Description**: Permanently delete a product variation. This action cannot be undone.

**Route Parameters**:
- `product_id` (integer, required): Parent product ID
- `id` (integer, required): Variation ID

## Order Management

### List Orders
**ID**: `woocommerce/orders-list`
**Operation**: `list`
**Route**: `/wc/v3/orders`
**Description**: Retrieve a paginated list of orders with optional filters for status, customer, date range, and other criteria.

**Key Parameters**:
- `per_page` (integer): Maximum number of items (1-100, default: 10)
- `page` (integer): Current page number
- `status` (array): Filter by order status (`pending`, `processing`, `completed`, etc.)
- `customer` (integer): Filter by customer ID
- `after` / `before` (string): Date range filtering (ISO8601 format)
- `orderby` (string): Sort by field (`date`, `id`, `title`)

### Get Order
**ID**: `woocommerce/orders-get`
**Operation**: `get`
**Route**: `/wc/v3/orders/{id}`
**Description**: Retrieve detailed information about a single order by ID, including line items, customer details, and payment information.

**Route Parameters**:
- `id` (integer, required): Order ID

### Create Order
**ID**: `woocommerce/orders-create`
**Operation**: `create`
**Route**: `/wc/v3/orders`
**Description**: Create a new order with customer information, line items, shipping details, and payment information.

**Key Parameters**:
- `line_items` (array, required): Products in the order (`[{"product_id": 123, "quantity": 2}]`)
- `billing` (object): Billing address and contact information
- `shipping` (object): Shipping address
- `customer_id` (integer): Existing customer ID (0 for guest)
- `payment_method` (string): Payment method ID
- `status` (string): Initial order status

### Update Order
**ID**: `woocommerce/orders-update`
**Operation**: `update`
**Route**: `/wc/v3/orders/{id}`
**Description**: Update an existing order by modifying status, customer information, line items, or other order details.

**Route Parameters**:
- `id` (integer, required): Order ID

## Order Notes

Order notes are internal comments or customer communications attached to orders.

### List Order Notes
**ID**: `woocommerce/order-notes-list`
**Operation**: `list`
**Route**: `/wc/v3/orders/{order_id}/notes`
**Description**: Retrieve all notes for a specific order, including internal and customer-visible notes with filtering options.

**Route Parameters**:
- `order_id` (integer, required): Order ID

**Key Parameters**:
- `type` (string): Filter by note type (`any`, `customer`, `internal`)

### Get Order Note
**ID**: `woocommerce/order-notes-get`
**Operation**: `get`
**Route**: `/wc/v3/orders/{order_id}/notes/{id}`
**Description**: Retrieve detailed information about a specific order note by ID.

**Route Parameters**:
- `order_id` (integer, required): Order ID
- `id` (integer, required): Note ID

### Create Order Note
**ID**: `woocommerce/order-notes-create`
**Operation**: `create`
**Route**: `/wc/v3/orders/{order_id}/notes`
**Description**: Add a new note to an order, either as an internal note or customer-visible note.

**Route Parameters**:
- `order_id` (integer, required): Order ID

**Key Parameters**:
- `note` (string, required): Note content
- `customer_note` (boolean): Whether note is visible to customer (default: false)
- `added_by_user` (boolean): Whether note is attributed to current user (default: false)

### Delete Order Note
**ID**: `woocommerce/order-notes-delete`
**Operation**: `delete`
**Route**: `/wc/v3/orders/{order_id}/notes/{id}`
**Description**: Permanently remove a note from an order. This action cannot be undone.

**Route Parameters**:
- `order_id` (integer, required): Order ID
- `id` (integer, required): Note ID

## Order Refunds

### List Order Refunds
**ID**: `woocommerce/order-refunds-list`
**Operation**: `list`
**Route**: `/wc/v3/orders/{order_id}/refunds`
**Description**: Retrieve all refunds for a specific order, including refund amounts, reasons, and timestamps.

**Route Parameters**:
- `order_id` (integer, required): Order ID

### Get Order Refund
**ID**: `woocommerce/order-refunds-get`
**Operation**: `get`
**Route**: `/wc/v3/orders/{order_id}/refunds/{id}`
**Description**: Retrieve detailed information about a specific refund, including line items and refund metadata.

**Route Parameters**:
- `order_id` (integer, required): Order ID
- `id` (integer, required): Refund ID

### Create Order Refund
**ID**: `woocommerce/order-refunds-create`
**Operation**: `create`
**Route**: `/wc/v3/orders/{order_id}/refunds`
**Description**: Process a refund for an order, specifying amount, reason, and whether to restock items.

**Route Parameters**:
- `order_id` (integer, required): Order ID

**Key Parameters**:
- `amount` (string): Refund amount (can be partial)
- `reason` (string): Reason for refund
- `api_refund` (boolean): Use payment gateway API (default: true)
- `api_restock` (boolean): Restock refunded items (default: true)
- `line_items` (array): Specific line items to refund (for partial refunds)

**Refund Behavior**:
- **Partial Refunds**: Order status remains unchanged, refund tracked separately
- **Full Refunds**: Order status automatically changes to `"refunded"`, order becomes non-editable

### Delete Order Refund
**ID**: `woocommerce/order-refunds-delete`
**Operation**: `delete`
**Route**: `/wc/v3/orders/{order_id}/refunds/{id}`
**Description**: Permanently delete a refund record. This action cannot be undone.

**Route Parameters**:
- `order_id` (integer, required): Order ID
- `id` (integer, required): Refund ID

## Customer Management

### List Customers
**ID**: `woocommerce/customers-list`
**Operation**: `list`
**Route**: `/wc/v3/customers`
**Description**: Retrieve a paginated list of customers with optional filters for email, role, and registration date.

**Key Parameters**:
- `per_page` (integer): Maximum number of items (1-100, default: 10)
- `search` (string): Search customers by name or email
- `email` (string): Filter by specific email address
- `role` (string): Filter by user role (`customer`, `all`, etc.)
- `orderby` (string): Sort by field (`name`, `registered_date`, `id`)

### Get Customer
**ID**: `woocommerce/customers-get`
**Operation**: `get`
**Route**: `/wc/v3/customers/{id}`
**Description**: Retrieve detailed information about a single customer by ID, including personal details and order history.

**Route Parameters**:
- `id` (integer, required): Customer ID

### Create Customer
**ID**: `woocommerce/customers-create`
**Operation**: `create`
**Route**: `/wc/v3/customers`
**Description**: Create a new customer account with email, personal information, and billing/shipping addresses.

**Key Parameters**:
- `email` (string, required): Customer email address
- `first_name` (string): Customer first name
- `last_name` (string): Customer last name
- `username` (string): Login username
- `password` (string): Account password
- `billing` (object): Billing address information
- `shipping` (object): Shipping address information

### Update Customer
**ID**: `woocommerce/customers-update`
**Operation**: `update`
**Route**: `/wc/v3/customers/{id}`
**Description**: Update an existing customer by modifying personal information, addresses, or account settings.

**Route Parameters**:
- `id` (integer, required): Customer ID

### Delete Customer
**ID**: `woocommerce/customers-delete`
**Operation**: `delete`
**Route**: `/wc/v3/customers/{id}`
**Description**: Permanently delete a customer account. This action cannot be undone.

**Route Parameters**:
- `id` (integer, required): Customer ID

## Coupon Management

### List Coupons
**ID**: `woocommerce/coupons-list`
**Operation**: `list`
**Route**: `/wc/v3/coupons`
**Description**: Retrieve a paginated list of coupons with optional filters for code, discount type, and usage restrictions.

**Key Parameters**:
- `per_page` (integer): Maximum number of items (1-100, default: 10)
- `search` (string): Search coupons by code or description
- `code` (string): Filter by specific coupon code

### Get Coupon
**ID**: `woocommerce/coupons-get`
**Operation**: `get`
**Route**: `/wc/v3/coupons/{id}`
**Description**: Retrieve detailed information about a single coupon by ID, including discount settings and usage restrictions.

**Route Parameters**:
- `id` (integer, required): Coupon ID

### Create Coupon
**ID**: `woocommerce/coupons-create`
**Operation**: `create`
**Route**: `/wc/v3/coupons`
**Description**: Create a new coupon with discount code, amount, expiration date, and usage restrictions.

**Key Parameters**:
- `code` (string, required): Coupon code
- `discount_type` (string): Type of discount (`percent`, `fixed_cart`, `fixed_product`)
- `amount` (string, required): Discount amount
- `description` (string): Coupon description
- `date_expires` (string): Expiration date (ISO8601 format)
- `usage_limit` (integer): Maximum number of uses
- `minimum_amount` (string): Minimum order amount
- `product_ids` (array): Restrict to specific products
- `email_restrictions` (array): Restrict to specific email addresses

### Update Coupon
**ID**: `woocommerce/coupons-update`
**Operation**: `update`
**Route**: `/wc/v3/coupons/{id}`
**Description**: Update an existing coupon by modifying discount amount, expiration date, or usage restrictions.

**Route Parameters**:
- `id` (integer, required): Coupon ID

### Delete Coupon
**ID**: `woocommerce/coupons-delete`
**Operation**: `delete`
**Route**: `/wc/v3/coupons/{id}`
**Description**: Permanently delete a coupon. This action cannot be undone.

**Route Parameters**:
- `id` (integer, required): Coupon ID

## System Status

### Get System Status
**ID**: `woocommerce/system-status-get`
**Operation**: `list`
**Route**: `/wc/v3/system_status`
**Description**: Retrieve comprehensive system status information including environment details, database info, active plugins, theme, and WooCommerce settings.

**Response includes**:
- Environment details (PHP, WordPress, WooCommerce versions)
- Database information and table sizes
- Active and inactive plugins
- Theme information
- WooCommerce configuration settings
- Security status
- Page configuration
- Logging configuration

## Route Parameters

Many WooCommerce MCP abilities use **named placeholders** in their routes:

- `{id}`: Generic entity ID (product, order, customer, etc.)
- `{order_id}`: Specific order ID for order-related operations
- `{product_id}`: Specific product ID for product variations
- `{customer_id}`: Specific customer ID (if implemented)

These parameters are automatically extracted from the route and added to the ability's input schema as required fields.

## Permission Model

All abilities respect WooCommerce's REST API permission model:

- **`read`**: Can execute GET operations (list, get)
- **`write`**: Can execute POST operations (create)
- **`read_write`**: Can execute all operations (GET, POST, PUT, DELETE)

Permissions are determined by the scope of the WooCommerce REST API key used for authentication.

## Error Handling

All abilities return standard WooCommerce REST API error responses:

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): id",
  "data": {
    "status": 400,
    "params": {
      "id": "id must be of type integer."
    }
  }
}
```

Common error codes include:
- `rest_invalid_param`: Invalid or missing parameters
- `woocommerce_rest_invalid_id`: Invalid entity ID
- `woocommerce_rest_cannot_view`: Insufficient permissions
- `rest_no_route`: Invalid route or method