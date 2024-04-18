---
post_title: HPOS order querying APIs
tags: reference
---

With the introduction of HPOS, we’ve enhanced the querying functionality in WC. Now, in addition to the well-known [existing APIs](https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query), we’re adding a few features that would make it easier to create complex queries on orders or their properties, including the ability to query custom order metadata.

All the new query types are implemented as additional query arguments that can be passed to `wc_get_orders()` and are heavily inspired by similar functionality in WordPress’ own `WP_Query`. As regular query arguments, they can be combined with other arguments to produce complex queries that, until now, would have required writing custom code and SQL.

## The new query types

### Metadata queries (`meta_query`)

With the introduction of HPOS, order fields that were previously stored as post metadata have been moved to their own tables, but the remaining metadata (custom, or from other extensions) can now be queried through the `meta_query `argument.

At its core, `meta_query` is an array that can contain one or more arrays with keys:
`key` (the meta key name),
`value` (the meta value)
`compare` (optional) an operator to use for comparison purposes such as LIKE, RLIKE, NOT BETWEEN, BETWEEN, etc.
`type` to cast the meta value to a specific SQL type in the query

The different arrays can also be combined using `relation` (which admits 'AND' or 'OR' values) to produce more complex queries. The syntax for this new argument is exactly the same as for WP_Query’s `meta_query`. As such, you can refer to the [`meta_query` docs](https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters) for more details.

```php
// Example: obtain all orders which have metadata with the "color" key (any value) and have metadata
// with key "size" containing "small" (so it’d match "extra-small" as well as "small", for example).
$orders = wc_get_orders(
    array(
        'meta_query' => array(
            array(
                'key' => 'color',
            ),
            array(
                'key'        => 'size',
                'value'      => 'small',
                'compare'    => 'LIKE'
            ),
        ),
    )
);
```


### Order field queries (`field_query`)

This query type has a syntax similar to that of meta queries (`meta_query`) but instead of `key` you’d use `field` inside the different clauses. Here, `field` refers to any order property (such as `billing_first_name`, `total` or `order_key`, etc.) which are also accessible as top-level keys in the query arguments as usual. The difference between directly querying those properties and using a `field_query` is that you can create more complex queries by implementing comparison operators and combining or nesting.

```php
// Example. For a simple query, you’d be better off by using the order properties directly, even though there's a `field_query` equivalent.
$orders = wc_get_orders(
    array(
        'billing_first_name' => 'Lauren',
        'order_key'          => 'my_order_key',
    )
);

$orders = wc_get_orders(
    array(
        'field_query' => array(
            array(
                'field' => 'billing_first_name',
                'value' => 'Lauren'
            ),
            array(
                'field' => 'order_key',
                'value' => 'my_order_key',
            )
        )
    )
);
```

The true power of `field_query` is revealed when you want to perform more interesting queries, that were not possible before...

```php
// Example. Obtain all orders where either the total or shipping total is less than 5.0.

$orders = wc_get_orders(
    array(
        'field_query' => array(
            'relation' => 'OR',
            array(
                'field'   => 'total',
                'value'   => '5.0',
                'compare' => '<',
            ),
            array(
                'field'   => 'shipping_total',
                'value'   => '5.0',
                'compare' => '<',
            ),
        )
    )
);
```

### Date queries (`date_query`)

Date queries allow you to fetch orders by querying their associated dates (`date_completed`, `date_created`, `date_updated`, `date_paid`) in various ways.
The syntax for `date_query` is fully compatible with that of `date_query` in `WP_Query`. As such, a good source of examples and details is [the `meta_query` docs](https://developer.wordpress.org/reference/classes/wp_query/#date-parameters) in the WP codex.

```php
// Example. Obtain all orders paid in the last month that were created before noon (on any date).

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

## Advanced Examples

```php
// Obtain orders either "on-hold" or "pending" that have metadata `weight` >= 50 and metadata `color` or `size` is set.

$query_args = array(
    'status' => array( 'pending', 'on-hold' ),
    'meta_query' => array(
        array(
            'key'     => 'weight',
            'value'   => '50',
            'compare' => '>=',
        ),
        array(
            'relation' => 'OR',
            array(
                'key'     => 'color',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => 'size',
                'compare' => 'EXISTS',
            )
        ),
    )
);

$orders = wc_get_orders( $query_args );
```

```php
// Obtain orders where the first name in the billing details contains "laur" (so it’d both match "lauren" and "laura", for example), and where the order’s total is less than 10.0 and the total discount is >= 5.0.

$orders = wc_get_orders(
    array(
        'field_query' => array(
            array(
                'field'   => 'billing_first_name',
                'value'   => 'laur',
                'compare' => 'LIKE',
            ),
            array(
                'relation' => 'AND',
                array(
                    'field'   => 'total',
                    'value'   => '10.0',
                    'compare' => '<',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'field'   => 'discount_total',
                    'value'   => '5.0',
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                )
            )
        ),
    )
);
```
