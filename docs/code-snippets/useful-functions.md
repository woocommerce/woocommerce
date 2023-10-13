# Useful Core Functions

WooCommerce core functions are available on both front-end and admin. They can be found in `includes/wc-core-functions.php` and can be used by themes in plugins.

## Conditional Functions

WooCommerce conditional functions help determine the current query/page.

### is_woocommerce

Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and thus are not included).

```php
is_woocommerce()
```

### is_shop

Returns true when viewing the product type archive (shop).

```php
is_shop()
```

### is_product

Returns true when viewing a single product.

```php
is_product()
```

## Coupon Functions

### wc_get_coupon_code_by_id

Get coupon code by coupon ID.

```php
wc_get_coupon_code_by_id( $id )
```

The argument `$id` is the coupon ID.

### wc_get_coupon_id_by_code

Gets the coupon ID by code.

```php
wc_get_coupon_id_by_code( $code, $exclude = 0 )
```

`$code` is the coupon code and `$exclude` is to exclude an ID from the check if you're checking existence.

## User Functions

### wc_customer_bought_product

Checks if a customer has bought an item. The check can be done by email or user ID or both.

```php
wc_customer_bought_product( $customer_email, $user_id, $product_id )
```

### wc_get_customer_total_spent

Gets the total spent for a customer.

```php
wc_get_customer_total_spent( $user_id )
```

`$user_id` is the user ID of the customer.

### wc_get_customer_order_count

Gets the total orders for a customer.

```php
wc_get_customer_order_count( $user_id )
```

`$user_id` is the user ID of the customer.

## Formatting Functions

### wc_get_dimension

Takes a measurement `$dimension` measured in WooCommerce's dimension unit and converts it to the target unit `$to_unit`.

```php
wc_get_dimension( $dimension, $to_unit, $from_unit = '' )
```

Example usages:

```php
wc_get_dimension( 55, 'in' );
wc_get_dimension( 55, 'in', 'm' );
```

### wc_get_weight

Takes a weight `$weight` weighed in WooCommerce's weight unit and converts it to the target weight unit `$to_unit`.

```php
wc_get_weight( $weight, $to_unit, $from_unit = '' )
```

Example usages:

```php
wc_get_weight( 55, 'kg' );
wc_get_weight( 55, 'kg', 'lbs' );
```

### wc_clean

Clean variables using `sanitize_text_field`. Arrays are cleaned recursively. Non-scalar values are ignored.

```php
wc_clean( $var )
```

### wc_price

Formats a passed price with the correct number of decimals and currency symbol.

```php
wc_price( $price, $args = array() )
```

The ` $args` array has an option called ` ex_tax_label` – if true then an `excluding tax` message will be appended.

## Order Functions

### wc_get_orders

This function is the standard way of retrieving orders based on certain parameters. This function should be used for order retrieval so that when we move to custom tables, functions still work.

```php
wc_get_orders( $args )
```

Args and usage: https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query

### wc_get_order

This is the main function for returning orders, uses the `WC_Order_Factory` class.

```php
wc_get_order( $the_order = false )
```

The `the_order` parameter can be a post object or post ID of the order.

### wc_orders_count

Returns the orders count of a specific order status.

```php
wc_orders_count( $status, string $type = '' )
```

### wc_order_search

Searches orders based on the given `$term`.

```php
wc_order_search( $term )
```

## Page Functions

## Product Functions

## Stock Functions

## Account Functions

## Term Functions

## Attribute Functions

## REST Functions

## Widget Functions

## Webhook Functions
