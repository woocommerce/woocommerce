# Data

WooCommerce Admin data stores implement the [`SqlQuery` class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/API/Reports/SqlQuery.php). 

## SqlQuery Class

The `SqlQuery` class is a SQL Query statement object. Its properties consist of

- A `context` string identifying the context of the query.
- SQL clause (`type`) string arrays used to construct the SQL statement:
    - `select`
    - `from`
    - `right_join`
    - `join`
    - `left_join`
    - `where`
    - `where_time`
    - `group_by`
    - `having`
    - `order_by`
    - `limit`

## Reports Data Stores

The base DataStore `Automattic\WooCommerce\Admin\API\Reports\DataStore` extends the `SqlQuery` class. There is `StatsDataStoreTrait` that adds Interval & Total Queries. The implementation data store classes use the following `SqlQuery` instances:

| Data Store | Context | Class Query | Sub Query | Interval Query | Total Query |
| ---------- | ------- | ----------- | --------- | -------------- | ----------- |
| Categories | categories | Yes | Yes | - | - |
| Coupons | coupons | Yes | Yes | - | - |
| Coupon Stats | coupon_stats | Yes | - | Yes | Yes |
| Customers | customers | Yes | Yes | - | - |
| Customer Stats | customer_stats | Yes | - | Yes | Yes |
| Downloads | downloads | Yes | Yes | - | - |
| Download Stats | download_stats | Yes | - | Yes | Yes |
| Orders | orders | Yes | Yes | - | - |
| Order Stats | order_stats | Yes | - | Yes | Yes |
| Products | products | Yes | Yes | - | - |
| Product Stats | product_stats | Yes | - | Yes | Yes |
| Taxes | taxes | Yes | Yes | - | - |
| Tax Stats | tax_stats | Yes | - | Yes | Yes |
| Variations | variations | Yes | Yes | - | - |
| StatsDataStoreTrait | n/a | n/a | - | Yes | Yes |

Query contexts are named as follows:

- Class Query = Class Context
- Sub Query = Class Context + `_subquery`
- Interval Query = Class Context + `_interval`
- Total Query = Class Context + `_total`

## Filters

When getting the full statement the clause arrays are passed through two filters where `$context` is the query object context and `$type` is:

- `select`
- `from`
- `join` = `right_join` + `join` + `left_join`
- `where` = `where` + `where_time`
- `group_by`
- `having`
- `order_by`
- `limit`

The filters are:

- `apply_filters( "woocommerce_analytics_clauses_{$type}", $clauses, $context );`
- `apply_filters( "woocommerce_analytics_clauses_{$type}_{$context}", $clauses );`

Example usage

```php
add_filter( 'woocommerce_analytics_clauses_product_stats_select_total', 'my_custom_product_stats' );
/**
 * Add sample data to product stats totals.
 *
 * @param array $clauses array of SELECT clauses.
 * @return array
 */
function my_custom_product_stats( $clauses ) {
	$clauses[] = ', SUM( sample_column ) as sample_total';
	return $clauses;
}
```
