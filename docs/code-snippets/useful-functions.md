---
post_title: Useful core functions
tags: code-snippet
---

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

The ` $args` array has an option called ` ex_tax_label` - if true then an `excluding tax` message will be appended.

## Order Functions

### wc_get_orders

This function is the standard way of retrieving orders based on certain parameters. This function should be used for order retrieval so that when we move to custom tables, functions still work.

```php
wc_get_orders( $args )
```

[Arguments and usage](https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query)

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

### wc_get_page_id

Gets a WooCommerce page ID by name, e.g. thankyou

```php
wc_get_page_id( $page )
```

### wc_get_endpoint_url

Gets the URL for an `$endpoint`, which varies depending on permalink settings.

```php
wc_get_endpoint_url( $endpoint, $value = '', $permalink = '' )
```

## Product Functions

### wc_get_products

This function is the standard way of retrieving products based on certain parameters.

```php
wc_get_products( $args )
```

[Arguments and usage](https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query)

### wc_get_product

This is the main function for returning products. It uses the `WC_Product_Factory` class.

```php
wc_get_product( $the_product = false )
```

The argument `$the_product` can be a post object or post ID of the product.

### wc_get_product_ids_on_sale

Returns an array containing the IDs of the products that are on sale.

```php
wc_get_product_ids_on_sale()
```

### wc_get_featured_product_ids

Returns an array containing the IDs of the featured products.

```php
wc_get_featured_product_ids()
```

### wc_get_related_products

Gets the related products for product based on product category and tags.

```php
wc_get_related_products( $product_id, $limit = 5, $exclude_ids = array() )
```

## Account Functions

### wc_get_account_endpoint_url

Gets the account endpoint URL.

```php
wc_get_account_endpoint_url( $endpoint )
```

## Attribute Functions

### wc_get_attribute_taxonomies

Gets the taxonomies of product attributes.

```php
wc_get_attribute_taxonomies()
```

### wc_attribute_taxonomy_name

Gets the taxonomy name for a given product attribute.

```php
wc_attribute_taxonomy_name( $attribute_name )
```

### wc_attribute_taxonomy_id_by_name

Gets a product attribute ID by name.

```php
wc_attribute_taxonomy_id_by_name( $name )
```

## REST Functions

### wc_rest_prepare_date_response

Parses and formats a date for ISO8601/RFC3339.

```php
wc_rest_prepare_date_response( $date, $utc = true )
```

Pass `$utc` as `false` to get local/offset time.

### wc_rest_upload_image_from_url

Uploads an image from a given URL.

```php
wc_rest_upload_image_from_url( $image_url )
```

### wc_rest_urlencode_rfc3986

Encodes a `$value` according to RFC 3986.

```php
wc_rest_urlencode_rfc3986( $value )
```

### wc_rest_check_post_permissions

Checks permissions of posts on REST API.

```php
wc_rest_check_post_permissions( $post_type, $context = 'read', $object_id = 0 )
```

The available values for `$context` which is the request context are `read`, `create`, `edit`, `delete` and `batch`.
