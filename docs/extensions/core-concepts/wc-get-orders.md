---
sidebar_label: Order Querying
---
# `wc_get_orders()` and order queries

`wc_get_orders()` and `WC_Order_Query` provide a standard way of retrieving orders from the database, similar to WordPress' [`get_posts()` and `WP_Query`](https://codex.wordpress.org/Class_Reference/WP_Query) but specifically for orders.

Plugin and theme developers are discouraged from writing custom WordPress queries or direct SQL as changes in the WordPress or WooCommerce database can result in breakage. These APIs provide the best-practices and future-proof way to fetch orders in WooCommerce.

## Basic usage

### Examples

Here are a few examples:

```php
// Get orders from people named John paid in 2016.
$orders = wc_get_orders(
    array(
        'billing_first_name' => 'John',
        'date_paid'          => '2016-01-01...2016-12-31',
    )
);
```

```php
// Get 10 most recent order IDs.
$query = new WC_Order_Query(
    array(
        'limit'   => 10,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'ids',
    )
);
$orders = $query->get_orders();
```

```php
// Get orders from the customer with email 'woocommerce@woocommerce.com'.
$query = new WC_Order_Query();
$query->set( 'customer', 'woocommerce@woocommerce.com' );
$orders = $query->get_orders();
```

Note that `wc_get_orders()` is mostly a shortcut to `WC_Order_Query::get_orders()`.

### Best practices

- Avoid direct base queries and rely on `wc_get_orders()` instead.
- If your code needs to support legacy setups, test thoroughly with HPOS enabled and disabled.
- Use specific parameters to limit results and improve performance.
- Consider pagination for large result sets using `limit` and `offset`.
- Cache results when appropriate.
- For complex filtering requirements, leverage the new query arguments `meta_query`, `field_query` and `date_query` available since 8.2 on sites running HPOS.


## API reference

| Method                                   | Description                              |
|-------------------------------------------|------------------------------------------|
| `wc_get_orders ( $args )`            | Retrieve orders matching query `$args`. |
| `WC_Order_Query::get_query_vars()`        | Get an array of all of the current query variables set on the query object.         |
| `WC_Order_Query::get( string $key, mixed $default = '' )`               | Get the value of a query variable or the default if the query variable is not set.            |
| `WC_Order_Query::set( string $key, mixed $value )`       | Set a query variable.                     |
| `WC_Order_Query::get_orders()`            | Get all orders matching the current query variables.  |

Query parameters/arguments that can be used with these functions are described below.

## Query parameters reference

### General

|Parameter|Description|
|-|-|
|**status**|Accepts an array of strings: by default is set to the keys of `wc_get_order_statuses()`.|
|**type**|Accepts a string: `'shop_order'`, `'shop_order_refund'`, or a custom order type.|
|**version**|Accepts a string: WooCommerce version number the order was created in.|
|**created_via**|Accepts a string: 'checkout', 'rest-api', or a custom creation method slug.|
|**parent**|Accepts an integer: post ID of the order parent.|
|**parent_exclude**|Accepts an array of integers: Excludes orders with parent ids in the array.|
|**exclude**|Accepts an array of integers: excludes orders that have the ids.|
|**order**|Accepts a string: 'DESC' or 'ASC'. Use with 'orderby'. Default: 'DESC'.|
|**orderby**|Accepts a string: 'none', 'ID', 'name', 'type', 'rand', 'date', 'modified'. Default: 'date'.|
|**return**|Return type. Accepts a string: 'ids' or 'objects'. Default: 'objects'.|

#### Examples

```php
// Get most recently modified orders.
$args = array(
    'orderby' => 'modified',
    'order' => 'DESC',
);
$orders = wc_get_orders( $args );
```

```php
// Get some random orders.
$orders = wc_get_orders( array( 'orderby' => 'rand' ) );
```

```php
// Return only order ids.
$orders = wc_get_orders( array( 'return' => 'ids' ) );
```

```php
// Get orders processing and on-hold.
$args = array(
    'status' => array( 'wc-processing', 'wc-on-hold' ),
);
$orders = wc_get_orders( $args );
```

```php
// Get refunds in the last 24 hours.
$args = array(
    'type'         => 'shop_order_refund',
    'date_created' => '>' . ( time() - DAY_IN_SECONDS ),
);
$orders = wc_get_orders( $args );
```

```php
// Get orders created during WooCommerce 2.6.14 and through site checkout.
$args = array(
    'version'     => '2.6.14',
    'created_via' => 'checkout',
);
$orders = wc_get_orders( $args );
```

```php
// Get orders with post parent ID of 20 that aren't order 12.
$args = array(
    'parent'  => 20,
    'exclude' => array( 12 ),
);
$orders = wc_get_orders( $args );
```

### Pagination

|Parameter|Description|
|-|-|
|**limit**|Accepts an integer: Maximum number of results to retrieve or `-1` for unlimited. Default: Site 'posts_per_page' setting.|
|**paged**|Accepts an integer: Page of results to retrieve. Does nothing if 'offset' is used.|
|**offset**|Accepts an integer: Amount to offset order results.|
|**paginate**|Accepts a boolean: True for pagination, or false for not (default: false). If enabled, modifies the return results to give an object with fields: `orders` (array of found orders), `total` (number of found orders) and `max_num_pages` (total number of pages).|

#### Examples

```php
// Get latest 3 orders.
$orders = wc_get_orders( array( 'limit' => 3 ) );
```

```php
// First 3 orders.
$args = array(
    'limit' => 3,
    'paged' => 1,
);
$page_1_orders = wc_get_orders( $args );

// Second 3 orders.
$args = array(
    'limit' => 3,
    'paged' => 2,
);
$page_2_orders = wc_get_orders( $args );
```

```php
// Get orders with extra info about the results.
$results = wc_get_orders( array( 'paginate' => true ) );
echo $results->total . " orders found\n";
echo 'Page 1 of ' . $results->max_num_pages . "\n";
echo 'First order id is: ' . $results->orders[0]->get_id() . "\n";
```

### Payment & amounts

|Parameter|Description|
|-|-|
|**currency**|Accepts a string: Currency used in order.|
|**prices_include_tax**|Accepts a string: 'yes' or 'no'.|
|**payment_method**|Accepts a string: Slug of payment method used.|
|**payment_method_title**|Accepts a string: Full title of payment method used.|
|**discount_total**|Accepts a float: unrounded amount to match on.|
|**discount_tax**|Accepts a float: unrounded amount to match on.|
|**shipping_total**|Accepts a float: unrounded amount to match on.|
|**shipping_tax**|Accepts a float: unrounded amount to match on.|
|**cart_tax**|Accepts a float: unrounded amount to match on.|
|**total**|Accepts a float: unrounded amount to match on.|

#### Examples

```php
// Get orders paid in USD.
$orders = wc_get_orders( array( 'currency' => 'USD' ) );
```

```php
// Get orders paid by check.
$orders = wc_get_orders( array( 'payment_method' => 'cheque' ) );
```

```php
// Get orders with 20.00 discount total.
$orders = wc_get_orders( array( 'discount_total' => 20.00 ) );
```

### Customer

|Parameter|Description|
|-|-|
|**customer**|Accepts a string or an integer: The order's billing email or customer id.|
|**customer_id**|Accepts an integer: Customer ID.|
|**customer_ip_address**|Accepts string: Value to match on.|

#### Examples

```php
// Get orders by customer with email 'woocommerce@woocommerce.com'.
$orders = wc_get_orders( array( 'customer' => 'woocommerce@woocommerce.com' ) );
```

```php
// Get orders by customer with ID 12.
$orders = wc_get_orders( array( 'customer_id' => 12 ) );
```

### Billing & shipping

|Parameter|Description|
|-|-|
|**billing_first_name**|Accepts string: value to match on.|
|**billing_last_name**|Accepts string: value to match on.|
|**billing_company**|Accepts string: value to match on.|
|**billing_address_1**|Accepts string: value to match on.|
|**billing_address_2**|Accepts string: value to match on.|
|**billing_city**|Accepts string: value to match on.|
|**billing_state**|Accepts string: value to match on.|
|**billing_postcode**|Accepts string: value to match on.|
|**billing_country**|Accepts string: value to match on.|
|**billing_email**|Accepts string: value to match on.|
|**billing_phone**|Accepts string: value to match on.|
|**shipping_first_name**|Accepts string: value to match on.|
|**shipping_last_name**|Accepts string: value to match on.|
|**shipping_company**|Accepts string: value to match on.|
|**shipping_address_1**|Accepts string: value to match on.|
|**shipping_address_2**|Accepts string: value to match on.|
|**shipping_city**|Accepts string: value to match on.|
|**shipping_state**|Accepts string: value to match on.|
|**shipping_postcode**|Accepts string: value to match on.|
|**shipping_country**|Accepts string: value to match on.|

#### Examples

```php
// Get orders from the US.
$orders = wc_get_orders( array( 'billing_country' => 'US' ) );
```

```php
// Get orders from people named Bill Evans.
$args = array(
    'billing_first_name' => 'Bill',
    'billing_last_name'  => 'Evans',
);
$orders = wc_get_orders( $args );
```

### Date

Date arguments receive values following the standard format described below, allowing for more flexible queries.

|Parameter|Description|
|-|-|
|**date_created**|Matches order creation date. Accepts a string in standard format.|
|**date_modified**|Matches order modification date. Accepts a string in standard format.|
|**date_completed**|Matches order completed date. Accepts a string in standard format.|
|**date_paid**|Matches order payment date. Accepts a string in standard format.|

#### Standard format

- `YYYY-MM-DD` - Matches on orders during that one day in site timezone.
- `>YYYY-MM-DD` - Matches on orders after that one day in site timezone.
- `>=YYYY-MM-DD` - Matches on orders during or after that one day in site timezone.
- `<YYYY-MM-DD` - Matches on orders before that one day in site timezone.
- `<=YYYY-MM-DD` - Matches on orders during or before that one day in site timezone.
- `YYYY-MM-DD...YYYY-MM-DD` - Matches on orders during or in between the days in site timezone.
- `TIMESTAMP` - Matches on orders during that one second in UTC timezone.
- `>TIMESTAMP` - Matches on orders after that one second in UTC timezone.
- `>=TIMESTAMP` - Matches on orders during or after that one second in UTC timezone.
- `<TIMESTAMP` - Matches on orders before that one second in UTC timezone.
- `<=TIMESTAMP` - Matches on orders during or before that one second in UTC timezone.
- `TIMESTAMP...TIMESTAMP` - Matches on orders during or in between the seconds in UTC timezone.

#### Examples

```php
// Get orders paid February 12, 2016.
$orders = wc_get_orders( array( 'date_paid' => '2016-02-12' ) );
```

```php
// Get orders created before the last hour.
$args = array(
    'date_created' => '<' . ( time() - HOUR_IN_SECONDS ),
);
$orders = wc_get_orders( $args );
```

```php
// Get orders completed 16 May 2017 21:46:17 UTC to 17 May 2017 12:46:17 UTC.
$args = array(
    'date_completed' => '1494938777...1494971177',
);
$orders = wc_get_orders( $args );
```

### Metadata

<!-- markdownlint-disable MD033 -->
|Parameter|Description|
|-|-|
|**meta_query**|One or more arrays with keys `key` (meta key), `value` (optional, string or array) and optionally `type` and `compare`.<br />This parameter is analogous to [WP_Query's `meta_query`](https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters), supporting various comparison operators and levels of queries joined by AND/OR relations.|
<!-- markdownlint-enable MD033 -->

For more details and examples, refer to the [HPOS order querying](/docs/features/high-performance-order-storage/wc-order-query-improvements#metadata-queries-meta_query) guide.

:::warning

Support for `meta_query` is only available when HPOS is the configured order data storage (the default since WooCommerce 8.2).

Check if it's enabled with `OrderUtil::custom_orders_table_usage_is_enabled()` before using.
:::


#### Examples

```php
// Orders with metadata 'custom_field' set to 'some_value' and metadata 'weight' higher than '50'.
$orders = wc_get_orders(
    array(
        'meta_query' => array(
            array(
                'key'     => 'custom_field',
                'value'   => 'some_value',
                'compare' => '='
            ),
            array(
                'key'     => 'weight',
                'value'   => '50',
                'compare' => '>='
            ),
            'relation' => 'AND'
        )
    )
);
```

### Order fields

<!-- markdownlint-disable MD033 -->
|Parameter|Description|
|-|-|
|**field_query**|One or more arrays with keys `field` (any order property), `value` and optionally `type` and `compare`.<br />This parameter is analogous to those of `meta_query` described in the previous section, supporting various comparison operators and levels of queries joined by AND/OR relations.|
<!-- markdownlint-enable MD033 -->

For more details and examples, refer to the [HPOS order querying](/docs/features/high-performance-order-storage/wc-order-query-improvements#order-field-queries-field_query) guide.

:::warning

Support for `field_query` is only available when HPOS is the configured order data storage (the default since WooCommerce 8.2).

Check if it's enabled with `OrderUtil::custom_orders_table_usage_is_enabled()` before using.
:::

#### Examples

```php
// Obtain orders with a total greater than 100 or from New York city.
$orders = wc_get_orders(
    array(
        'field_query' => array(
            array(
                'field' => 'total',
                'value' => 100,
                'compare' => '>'
            ),
            array(
                'field' => 'billing_city',
                'value' => 'New York',
                'compare' => '='
            ),
            'relation' => 'OR'
        )
    )
);
```

### Advanced date queries

<!-- markdownlint-disable MD033 -->
|Parameter|Description|
|-|-|
|**date_query**|One or more arrays with keys `column` (an order date: `date_completed`, `date_created`, `date_updated` or `date_paid`, optionally followed by `_gmt` for UTC dates), `value` and optionally `type` and `compare`.<br />This parameter is analogous to [WP_Query's `date_query`](https://developer.wordpress.org/reference/classes/wp_query/#date-parameters), supporting various comparison operators and levels of queries joined by AND/OR relations.|
<!-- markdownlint-enable MD033 -->

For more details and examples, refer to the [HPOS order querying](/docs/features/high-performance-order-storage/wc-order-query-improvements#date-queries-date_query) guide.

:::warning

Support for `date_query` is only available when HPOS is the configured order data storage (the default since WooCommerce 8.2).

Check if it's enabled with `OrderUtil::custom_orders_table_usage_is_enabled()` before using.
:::

#### Examples

```php
// Example: Orders paid in the last month that were created before noon (on any date).

$orders = wc_get_orders(
    array(
        'date_query' => array(
            'relation' => 'AND',
            array(
                'column'  => 'date_created_gmt',
                'hour'    => 12,
                'compare' => '<'
            ),
            array(
                'column'  => 'date_paid_gmt',
                'after'   => '1 month ago',
            ),
        ),
    )
);
```

## Adding support for custom parameters

Developers can extend the query capabilities by filtering the generated query to add support for custom parameters to both `wc_get_orders()` and `WC_Order_Query`.

WooCommerce currently supports two order storage mechanisms: HPOS (the default) and legacy (which uses WordPress posts and metadata), each with their own hook to filter the generated query:

- (HPOS) `woocommerce_order_query_args` to translate a parameter into an existing one, or `woocommerce_orders_table_query_clauses` to write your own SQL.
- (Legacy) `woocommerce_order_data_store_cpt_get_orders_query` to translate a parameter into a `WP_Query` parameter.

```php
/**
 * Example: Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
 */
use Automattic\WooCommerce\Utilities\OrderUtil;

// HPOS version.
function handle_custom_query_var_hpos( $query_args ) {
    if ( ! empty( $query_args['customvar'] ) ) {
        if ( ! isset( $query_args['meta_query'] ) ) {
            $query_args['meta_query'] = array();
        }

		$query_args['meta_query'][] = array(
			'key'   => 'customvar',
			'value' => esc_attr( $query_args['customvar'] ),
		);

        unset( $query_args['customvar'] );
    }


    return $query_args;
}

// Legacy version.
function handle_custom_query_var_legacy( $query, $query_vars ) {
	if ( ! empty( $query_vars['customvar'] ) ) {
		$query['meta_query'][] = array(
			'key'   => 'customvar',
			'value' => esc_attr( $query_vars['customvar'] ),
		);
	}

	return $query;
}

if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
    // HPOS.
    add_filter(
        'woocommerce_order_query_args',
        'handle_custom_query_var_hpos'
    );
} else {
    // Legacy support.
    add_filter(
        'woocommerce_order_data_store_cpt_get_orders_query',
        'handle_custom_query_var_legacy',
        10,
        2
    );
}
```

Usage:

```php
$orders = wc_get_orders( array( 'customvar' => 'somevalue' ) );
```
