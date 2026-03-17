# Reducing Excessive Store Reads

Covers identifying and eliminating redundant data store reads caused by calling lazy-loading accessors more than once for the same data within a single execution path.

## Why it matters

WooCommerce data accessors such as `get_items()`, `get_coupon_codes()`, `get_shipping_methods()`, and `get_fees()` are backed by a data store. On first call they issue a SQL query and populate an internal cache; subsequent calls within the same request may re-invoke the accessor, fire associated hooks a second time, or bypass the cache depending on implementation. Extracting the result into a local variable eliminates the redundant store invocation and guarantees hooks fire exactly once.

## Pattern — accessor called more than once for the same result

**Identify when:** The same lazy-loading accessor (with identical arguments) appears two or more times in the same method or template block — typically once for a guard check and once for the loop.

**Common forms:**

```php
// Guard + loop — two store reads:
if ( count( $order->get_items() ) > 0 ) {
    foreach ( $order->get_items() as $item_id => $item ) { ... }
}

// empty() guard + loop:
if ( ! empty( $order->get_items() ) ) {
    return;
}
foreach ( $order->get_items() as $item ) { ... }

// Accessor re-invoked inside the loop body:
foreach ( $order->get_items() as $item ) {
    if ( count( $order->get_items() ) === 1 ) { ... } // store read on every iteration
}
```

**Correct pattern — extract once, reuse:**

```php
$items = $order->get_items();
if ( count( $items ) > 0 ) {
    foreach ( $items as $item_id => $item ) { ... }
}
```

## Identification criteria

An accessor qualifies for extraction when **all** of the following hold:

1. It is called with **identical arguments** in both call sites.
2. Both call sites are in the **same method or template block** (not across separate hook callbacks).
3. The accessor is a **lazy-loading data store method** — i.e., it queries the database or fires hooks on invocation. Pure computed accessors (`get_id()`, `get_total()`) are lower priority; extract them only when called inside a loop.

## Known lazy-loading accessors in WooCommerce

| Accessor | Object | Notes |
| --- | --- | --- |
| `get_items( $type )` | `WC_Order` | Loads order line items by type; fires `woocommerce_order_get_items` |
| `get_coupon_codes()` | `WC_Order` | Delegates to `get_items( 'coupon' )` |
| `get_shipping_methods()` | `WC_Order` | Delegates to `get_items( 'shipping' )` |
| `get_fees()` | `WC_Order` | Delegates to `get_items( 'fee' )` |
| `get_taxes()` | `WC_Order` | Delegates to `get_items( 'tax' )` |
| `get_downloadable_items()` | `WC_Order` | Queries downloadable items from order items |
| `get_attributes()` | `WC_Product` | Loads product attributes from data store; fires `woocommerce_product_get_attributes` |
| `get_downloads()` | `WC_Product` | Loads downloadable files from data store |

