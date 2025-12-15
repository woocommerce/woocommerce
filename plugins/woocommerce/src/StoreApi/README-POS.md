# POS Checkout via Store API

Point of Sale (POS) checkout functionality for the WooCommerce Store API. Built as a Hack Week proof-of-concept.

## Overview

POS checkout allows authenticated store operators to process in-person sales using the same Store API endpoints used by the checkout block. POS sessions skip validation requirements that don't apply to in-person transactions (addresses, email, nonces).

## Authentication

POS requests require:
1. **Authentication** via Application Passwords (Basic or Bearer)
2. **`X-WC-POS: 1` header** on all requests
3. **`manage_woocommerce` capability** on the authenticated user

Requests with `X-WC-POS` header but without valid authentication return 401.

### Supported Auth Methods

```http
# WooCommerce API keys (iOS app)
Authorization: Basic <base64(ck_xxx:cs_xxx)>

# WordPress Application Passwords
Authorization: Basic <base64(username:app_password)>
```

Both are handled by WooCommerce's existing `WC_REST_Authentication` class.

## Key Components

### POSSessionHandler
`src/StoreApi/POSSessionHandler.php`

Session handler for POS requests. Uses the authenticated user ID as the session key (prefixed with `pos_`), keeping POS cart state separate from the user's web cart.

### POSUtils
`src/StoreApi/Utilities/POSUtils.php`

- `is_pos_session()` - Check if current request is a POS session
- `current_user_can_pos()` - Check if user has POS permissions

### wc_is_pos_session()
Global helper function in `src/StoreApi/functions.php`.

### Payment Gateways

**POS Cash** (`includes/gateways/pos-cash/`)
- Single-step cash payment
- Orders marked completed immediately

**POS Card** (`includes/gateways/pos-card/`)
- Two-step card payment for Stripe Terminal
- Step 1: Create pending order (no `payment_intent_id`)
- Step 2: Capture payment (with `payment_intent_id` from Terminal)

## Validation Bypasses

When `wc_is_pos_session()` returns true:

| Validation | Location |
|------------|----------|
| Address validation | `Checkout.php`, `OrderController.php` |
| Billing address required | Schema filter |
| Email required | `BillingAddressSchema.php`, `OrderController.php` |
| Nonce check | `AbstractCartRoute.php`, `Checkout.php` |
| Cart hash for pending orders | `DraftOrderTrait.php` |

## API Usage

### Cash Payment
```http
POST /wc/store/v1/cart/add-item
X-WC-POS: 1
Authorization: Basic xxx

POST /wc/store/v1/checkout
X-WC-POS: 1
{"payment_method": "pos_cash"}
```

### Card Payment
```http
POST /wc/store/v1/checkout
X-WC-POS: 1
{"payment_method": "pos_card"}
# → Order created, status: pending

# [Collect card via Stripe Terminal]

POST /wc/store/v1/checkout
X-WC-POS: 1
{
  "payment_method": "pos_card",
  "payment_data": [{"key": "payment_intent_id", "value": "pi_xxx"}]
}
# → Payment captured, order completed
```

## Known Limitations

1. **User-scoped sessions**: One active transaction per user. Multiple devices with same user share cart state.
2. **No transaction isolation**: Abandoned carts persist until next successful checkout.
3. **WooPayments dependency**: Card payments require WooPayments for capture endpoint.
4. **Guest orders**: POS orders created with customer_id = 0 to avoid polluting operator's order history.

## Technical Notes

### REST API URL Format Fix

The `?rest_route=` query parameter format (e.g., `/?rest_route=/wc/store/v1/cart`) is commonly used by mobile apps when pretty permalinks are not available. This format was not being recognized as a Store API request by `WC()->is_store_api_request()`, which caused the cart to be loaded with the wrong session handler before the POS session filter could be applied.

**Root cause**: `is_rest_api_request()` and `is_store_api_request()` in `class-woocommerce.php` only checked for `/wp-json/` in `REQUEST_URI`, not the `?rest_route=` query parameter.

**Fix**: Both methods now also check for the `rest_route` query parameter (see `includes/class-woocommerce.php`).

**Impact**: Without this fix, POS sessions fail when using `?rest_route=` URLs because `wc_load_cart()` is called early (treating it as a frontend request), initializing the wrong session handler before the Store API route can set up `POSSessionHandler`.

This is a general WooCommerce bug that affects any Store API functionality relying on early detection of Store API requests, not just POS.

### Extra Cart Item Data for Plugins

Plugins like Gift Cards expect additional fields when adding products to cart (e.g., `wc_gc_giftcard_to`, `wc_gc_giftcard_from`). These plugins typically read from `$_POST`, which isn't populated for JSON requests.

**Fix**: The `cart/add-item` endpoint now passes unknown request parameters through to `cart_item_data`, allowing plugins to receive extra fields from JSON requests via the `woocommerce_add_cart_item_data` filter.

**Example**:
```json
POST /wc/store/v1/cart/add-item
{
  "id": 92,
  "quantity": 1,
  "wc_gc_giftcard_to": "recipient@example.com",
  "wc_gc_giftcard_from": "Josh"
}
```

## Files Changed

Key files:

- `src/StoreApi/POSSessionHandler.php` - Session handling
- `src/StoreApi/Utilities/POSUtils.php` - Utility functions
- `src/StoreApi/functions.php` - Global helper
- `src/StoreApi/Routes/V1/Checkout.php` - Validation bypasses
- `src/StoreApi/Routes/V1/AbstractCartRoute.php` - Nonce bypass, session init
- `src/StoreApi/Utilities/OrderController.php` - Address/email validation
- `src/StoreApi/Utilities/DraftOrderTrait.php` - Pending order reuse
- `src/StoreApi/Schemas/V1/BillingAddressSchema.php` - Optional email
- `src/StoreApi/Legacy.php` - Error passthrough
- `src/StoreApi/Utilities/CheckoutTrait.php` - Error passthrough
- `src/StoreApi/Routes/V1/CartAddItem.php` - Extra cart item data passthrough
- `includes/gateways/pos-cash/` - Cash gateway
- `includes/gateways/pos-card/` - Card gateway
- `includes/class-wc-payment-gateways.php` - Gateway registration
- `includes/data-stores/class-wc-customer-data-store-session.php` - Email autofill skip
- `includes/class-woocommerce.php` - REST API URL format detection fix
