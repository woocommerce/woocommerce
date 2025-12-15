# Integrating POS Store API in iOS WooCommerce App

Guide for migrating the iOS WooCommerce app's POS checkout from REST API to Store API.

## Current State

The iOS app currently:
- Uses WooCommerce REST API (`/wc/v3/`) for all operations
- Cannot provide billing customer email at checkout
- Cannot provide required gift card fields
- Creates orders directly via REST API

## Target State

Migrate to Store API (`/wc/store/v1/`) for POS checkout:
- Cart-based checkout flow
- Email optional (POS bypass)
- Gift cards handled via cart/checkout flow
- Same endpoints as web checkout block

## Required Headers

All POS requests must include:
```
Authorization: Basic <base64(ck_xxx:cs_xxx)>
X-WC-POS: 1
```

Use the existing WooCommerce API key authentication (same as REST API).

## API Endpoints

### Cart Operations

| Operation | Method | Endpoint | Body |
|-----------|--------|----------|------|
| Get cart | GET | `/wc/store/v1/cart` | - |
| Add item | POST | `/wc/store/v1/cart/add-item` | `{id, quantity, variation?, ...extras}` |
| Update quantity | POST | `/wc/store/v1/cart/update-item` | `{key, quantity}` |
| Remove item | POST | `/wc/store/v1/cart/remove-item` | `{key}` |
| Clear cart | DELETE | `/wc/store/v1/cart/items` | - |

### Coupons

| Operation | Method | Endpoint | Body |
|-----------|--------|----------|------|
| Apply coupon | POST | `/wc/store/v1/cart/apply-coupon` | `{code}` |
| Remove coupon | POST | `/wc/store/v1/cart/remove-coupon` | `{code}` |
| List coupons | GET | `/wc/store/v1/cart/coupons` | - |

### Checkout

| Operation | Method | Endpoint | Body |
|-----------|--------|----------|------|
| Get checkout state | GET | `/wc/store/v1/checkout` | - |
| Process payment | POST | `/wc/store/v1/checkout` | See below |

## Checkout Flow

### Cash Payment (Single Step)

```swift
// 1. Build cart
POST /wc/store/v1/cart/add-item
{"id": 123, "quantity": 1}

// 2. Apply coupon (optional)
POST /wc/store/v1/cart/apply-coupon
{"code": "DISCOUNT10"}

// 3. Checkout
POST /wc/store/v1/checkout
{
  "payment_method": "pos_cash",
  "billing_address": {},  // Empty object OK for POS
  "payment_data": []
}
// → Order created with status: completed
```

### Card Payment (Two Step)

```swift
// 1. Build cart (same as cash)

// 2. Create pending order
POST /wc/store/v1/checkout
{
  "payment_method": "pos_card",
  "billing_address": {},
  "payment_data": []
}
// → Order created with status: pending
// → Response includes order_id

// 3. Collect card via Stripe Terminal SDK
// ... Terminal SDK returns payment_intent_id ...

// 4. Capture payment
POST /wc/store/v1/checkout
{
  "payment_method": "pos_card",
  "billing_address": {},
  "payment_data": [
    {"key": "payment_intent_id", "value": "pi_xxx"}
  ]
}
// → Order status: completed
```

## Response Handling

### Cart Response Structure

```swift
struct StoreAPICart: Codable {
    let items: [CartItem]
    let itemsCount: Int
    let coupons: [CartCoupon]
    let totals: CartTotals
    let needsPayment: Bool
    let needsShipping: Bool
    let errors: [CartError]
}

struct CartItem: Codable {
    let key: String          // Use for update/remove
    let id: Int              // Product ID
    let quantity: Int
    let name: String
    let prices: ItemPrices
    let totals: ItemTotals
}

struct CartTotals: Codable {
    let currencyCode: String
    let totalItems: String      // Subtotal in minor units
    let totalDiscount: String   // Coupon discount
    let totalPrice: String      // Final total
}
```

### Checkout Response Structure

```swift
struct CheckoutResponse: Codable {
    let orderId: Int
    let status: String           // "pending", "completed", etc.
    let orderKey: String
    let paymentResult: PaymentResult
}

struct PaymentResult: Codable {
    let paymentStatus: String    // "success", "pending", "failure"
    let redirectUrl: String?
}
```

## Error Handling

### HTTP Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Process response |
| 201 | Created | Item added / order created |
| 400 | Bad request | Check error message |
| 401 | Unauthorized | Re-authenticate |
| 404 | Not found | Item/coupon doesn't exist |
| 409 | Conflict | Cart changed; resync from response |

### Error Response

```swift
struct StoreAPIError: Codable {
    let code: String      // e.g., "woocommerce_rest_cart_invalid_key"
    let message: String
    let data: ErrorData?
}
```

All cart-modifying endpoints return updated cart on error, allowing state reconciliation.

## Migration Steps

### 1. Add Store API Client

Create parallel networking layer for Store API:
- Base URL: `/wc/store/v1/`
- Add `X-WC-POS: 1` header to all requests
- Use existing Application Password auth

### 2. Implement Cart State Management

Replace direct order creation with cart-based flow:
- Local cart model synced with server
- Handle 409 conflicts by accepting server state
- Clear cart after successful checkout

### 3. Update Checkout Flow

```swift
// Before (REST API)
func checkout() {
    let order = createOrderRequest(items: localItems)
    restAPI.createOrder(order) { ... }
}

// After (Store API)
func checkout() {
    // Cart already populated via add-item calls
    let checkout = CheckoutRequest(
        paymentMethod: selectedMethod,
        billingAddress: [:],  // Empty for POS
        paymentData: paymentData
    )
    storeAPI.checkout(checkout) { ... }
}
```

### 4. Handle Two-Step Card Payments

For Stripe Terminal integration:
1. Call checkout without `payment_intent_id` → pending order
2. Collect card via Terminal SDK
3. Call checkout again with `payment_intent_id` → capture

## Gift Cards

Gift card products (WooCommerce Gift Cards plugin) require extra fields when adding to cart:

```swift
POST /wc/store/v1/cart/add-item
{
  "id": 92,
  "quantity": 1,
  "wc_gc_giftcard_to": "recipient@example.com",
  "wc_gc_giftcard_from": "Sender Name"
}
```

Extra fields beyond `id`, `quantity`, and `variation` are automatically passed through to `cart_item_data`, where plugins can access them via the `woocommerce_add_cart_item_data` filter.

**For redeeming gift cards** (applying as payment):
- If gift cards work as coupon codes, use `/cart/apply-coupon`
- Discount appears in `totals.total_discount`

## Known Limitations

1. **User-scoped sessions**: One cart per authenticated user. Multiple devices share state.
2. **No explicit transaction start**: Cart persists until checkout completes or explicit clear.
3. **WooPayments required**: Card capture requires WooPayments plugin for `payment_intent_id` handling.

## Technical Notes

### URL Format Support

Both URL formats are supported:

- Pretty permalinks: `/wp-json/wc/store/v1/cart`
- Query parameter: `/?rest_route=/wc/store/v1/cart`

The iOS app typically uses the `?rest_route=` format when pretty permalinks are unavailable. Both formats now work correctly with POS sessions.

## Testing Checklist

- [ ] Add single item to cart
- [ ] Add variable product with attributes
- [ ] Update item quantity
- [ ] Remove item
- [ ] Apply valid coupon
- [ ] Apply invalid coupon (expect 400)
- [ ] Cash checkout (single step)
- [ ] Card checkout step 1 (pending order)
- [ ] Card checkout step 2 (capture)
- [ ] Handle 409 conflict response
- [ ] Clear cart after checkout
- [ ] Verify order appears in WooCommerce admin
