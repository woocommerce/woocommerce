---
post_title: wc_get_products and product queries
sidebar_label: Product Querying
---

# `wc_get_products` and product queries

`wc_get_products` and `WC_Product_Query` provide a standard way of retrieving products that is safe to use and will not break due to database changes in future WooCommerce versions. Building custom WP_Queries or database queries is likely to break your code in future versions of WooCommerce as data moves towards custom tables for better performance. This is the best-practices way for plugin and theme developers to retrieve multiple products. `wc_get_products` and `WC_Product_Query` are similar to WordPress [`get_posts` and `WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/). Just like those, you pass in an array of arguments defining the criteria for the search.

## Basic usage

### Examples

Here are a few examples:

```php
// Get downloadable products created in the year 2016.
$products = wc_get_products( array(
    'downloadable' => true,
    'date_created' => '2016-01-01...2016-12-31',
) );
```

```php
// Get 10 most recent product IDs in date descending order.
$query = new WC_Product_Query( array(
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'ids',
) );
$products = $query->get_products();
```

```php
// Get products containing a specific SKU.
// Does partial matching, so this will get products with SKUs "PRDCT-1", "PRDCT-2", etc.
$query = new WC_Product_Query();
$query->set( 'sku', 'PRDCT' );
$products = $query->get_products();
```

Note that `wc_get_products()` is mostly a shortcut to `WC_Product_Query::get_products()`.

## API reference

| Method | Description |
| ------ | ----------- |
| `wc_get_products( $args )` | Retrieve products matching query `$args`. |
| `WC_Product_Query::get_query_vars()` | Get an array of all of the current query variables set on the query object. |
| `WC_Product_Query::get( string $key, mixed $default = '' )` | Get the value of a query variable or the default if the query variable is not set. |
| `WC_Product_Query::set( string $key, mixed $value )` | Set a query variable to a value. |
| `WC_Product_Query::get_products()` | Get all products matching the current query variables. |

Query parameters/arguments that can be used with these functions are described below.

## Query parameters reference

### General

| Parameter | Description |
| --------- | ----------- |
| **status** | Accepts a string or array of strings: one or more of `'draft'`, `'pending'`, `'private'`, `'publish'`, or a custom status. See [ProductStatus constant class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Enums/ProductStatus.php). |
| **type** | Accepts a string or array of strings: one or more of `'external'`, `'grouped'`, `'simple'`, `'variable'`, or a custom type. See [ProductType constant class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Enums/ProductType.php). |
| **include** | Accepts an array of integers: only includes products with IDs in the array. |
| **exclude** | Accepts an array of integers: excludes products with IDs in the array. |
| **parent** | Accepts an integer: post ID of the product parent. |
| **parent_exclude** | Accepts an array of integers: excludes products with parent IDs in the array. |
| **order** | Accepts a string: `'DESC'` or `'ASC'`. Use with `'orderby'`. Default: `'DESC'`. |
| **orderby** | Accepts a string: `'none'`, `'ID'`, `'name'`, `'type'`, `'rand'`, `'date'`, `'modified'`. Default: `'date'`. |
| **return** | Return type. Accepts a string: `'ids'` or `'objects'`. Default: `'objects'`. |

#### Examples

```php
// Get draft products.
$products = wc_get_products( array( 'status' => 'draft' ) );
```

```php
// Using constant class for status.
$products = wc_get_products( array( 'status' => \Automattic\WooCommerce\Enums\ProductStatus::DRAFT ) );
```

```php
// Get external products.
$products = wc_get_products( array( 'type' => 'external' ) );
```

```php
// Get external products limited to specific IDs.
$args = array(
    'type' => 'external',
    'include' => array( 134, 200, 210, 340 ),
);
$products = wc_get_products( $args );
```

```php
// Get products that aren't the current product.
$products = wc_get_products( array( 'exclude' => array( $product->get_id() ) ) );
```

```php
// Get products with a specific parent.
$products = wc_get_products( array( 'parent' => 20 ) );
```

```php
// Get most recently modified products.
$args = array(
    'orderby' => 'modified',
    'order' => 'DESC',
);
$products = wc_get_products( $args );
```

```php
// Get some random products.
$products = wc_get_products( array( 'orderby' => 'rand' ) );
```

```php
// Return only product IDs.
$products = wc_get_products( array( 'return' => 'ids' ) );
```

### Pagination

| Parameter | Description |
| --------- | ----------- |
| **limit** | Accepts an integer: maximum number of results to retrieve or `-1` for unlimited. Default: site `posts_per_page` setting. |
| **page** | Accepts an integer: page of results to retrieve. Does nothing if `'offset'` is used. |
| **offset** | Accepts an integer: amount to offset product results. |
| **paginate** | Accepts a boolean: true for pagination, or false for not. Default: `false`. If enabled, modifies the return results to give an object with fields: `products` (array of found products), `total` (number of found products), and `max_num_pages` (total number of pages). |

#### Examples

```php
// Get latest 3 products.
$products = wc_get_products( array( 'limit' => 3 ) );
```

```php
// First 3 products.
$args = array(
    'limit' => 3,
    'page'  => 1,
);
$page_1_products = wc_get_products( $args );

// Second 3 products.
$args = array(
    'limit' => 3,
    'page'  => 2,
);
$page_2_products = wc_get_products( $args );
```

```php
// Get products with extra info about the results.
$results = wc_get_products( array( 'paginate' => true ) );
echo $results->total . " products found\n";
echo 'Page 1 of ' . $results->max_num_pages . "\n";
if ( count( $results->products ) > 0 ) {
    echo 'First product id is: ' . $results->products[0]->get_id() . "\n";
}
```

```php
// Get second to fifth most-recent products.
$args = array(
    'limit' => 4,
    'offset' => 1,
);
$products = wc_get_products( $args );
```

### Product lookup

| Parameter | Description |
| --------- | ----------- |
| **sku** | Accepts a string: product SKU to match on. Does partial matching on the SKU. |
| **name** | Accepts a string: the product name (title) to match on. Case sensitivity depends on the collation of the WordPress posts table. |
| **tag** | Accepts an array: limit results to products assigned to specific tags by slug. |
| **product_tag_id** | Accepts an integer or array of integers: limit results to products assigned to specific tags by ID. |
| **category** | Accepts an array: limit results to products assigned to specific categories by slug. |
| **product_category_id** | Accepts an integer or array of integers: limit results to products assigned to specific categories by ID. |

#### Examples

```php
// Get products with "PRDCT" in their SKU (e.g. PRDCT-1 and PRDCT-2).
$products = wc_get_products( array( 'sku' => 'PRDCT' ) );
```

```php
// Get a product named "Test Product".
$products = wc_get_products( array( 'name' => 'Test Product' ) );
```

```php
// Get products with the "Excellent" or "Modern" tags.
$products = wc_get_products( array( 'tag' => array( 'excellent', 'modern' ) ) );
```

```php
// Get products by tag IDs.
$products = wc_get_products( array( 'product_tag_id' => array( 17, 23 ) ) );
```

```php
// Get shirts.
$products = wc_get_products( array( 'category' => array( 'shirts' ) ) );
```

```php
// Get products by category IDs.
$products = wc_get_products( array( 'product_category_id' => array( 17, 23 ) ) );
```

### Dimensions & pricing

| Parameter | Description |
| --------- | ----------- |
| **weight** | Accepts a float: the weight measurement to match on. |
| **length** | Accepts a float: the length measurement to match on. |
| **width** | Accepts a float: the width measurement to match on. |
| **height** | Accepts a float: the height measurement to match on. |
| **price** | Accepts a float: the current price to match on. |
| **regular_price** | Accepts a float: the regular price to match on. |
| **sale_price** | Accepts a float: the sale price to match on. |
| **total_sales** | Accepts an integer: gets products with that many sales. |

#### Examples

```php
// Get products 5.5 units wide and 10 units long.
$args = array(
    'width' => 5.5,
    'length' => 10,
);
$products = wc_get_products( $args );
```

```php
// Get products that currently cost 9.99.
$products = wc_get_products( array( 'price' => 9.99 ) );
```

```php
// Get products that have never been purchased.
$products = wc_get_products( array( 'total_sales' => 0 ) );
```

### Product settings

| Parameter | Description |
| --------- | ----------- |
| **virtual** | Accepts a boolean: limit to virtual products. |
| **downloadable** | Accepts a boolean: limit to downloadable products. |
| **featured** | Accepts a boolean: limit to featured products. |
| **sold_individually** | Accepts a boolean: limit to products sold individually. |
| **manage_stock** | Accepts a boolean: limit to products with stock management enabled. |
| **reviews_allowed** | Accepts a boolean: limit to products that allow reviews. |
| **backorders** | Accepts a string: `'yes'`, `'no'`, or `'notify'`. |
| **visibility** | Accepts a string: `'visible'`, `'catalog'`, `'search'`, or `'hidden'`. See [CatalogVisibility constant class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Enums/CatalogVisibility.php). |
| **download_limit** | Accepts an integer: the download limit or `-1` for unlimited. |
| **download_expiry** | Accepts an integer: the download expiry (days) or `-1` for unlimited. |

#### Examples

```php
// Get downloadable products that don't allow reviews.
$args = array(
    'downloadable' => true,
    'reviews_allowed' => false,
);
$products = wc_get_products( $args );
```

```php
// Get products that allow backorders.
$products = wc_get_products( array( 'backorders' => 'yes' ) );
```

```php
// Get products that show in the catalog.
$products = wc_get_products( array( 'visibility' => 'catalog' ) );
```

```php
// Using constant class for visibility.
$products = wc_get_products( array( 'visibility' => \Automattic\WooCommerce\Enums\CatalogVisibility::CATALOG ) );
```

```php
// Get products with unlimited downloads.
$products = wc_get_products( array( 'download_limit' => -1 ) );
```

### Stock & inventory

| Parameter | Description |
| --------- | ----------- |
| **stock_quantity** | Accepts an integer: the quantity of a product in stock. |
| **stock_status** | Accepts a string: `'outofstock'`, `'instock'`, or `'onbackorder'`. See [ProductStockStatus constant class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Enums/ProductStockStatus.php). |

#### Examples

```php
// Get products that only have one left in stock.
$products = wc_get_products( array( 'stock_quantity' => 1 ) );
```

```php
// Get out of stock products.
$products = wc_get_products( array( 'stock_status' => 'outofstock' ) );
```

```php
// Using constant class for stock status.
$products = wc_get_products( array( 'stock_status' => \Automattic\WooCommerce\Enums\ProductStockStatus::OUT_OF_STOCK ) );
```

### Tax & shipping

| Parameter | Description |
| --------- | ----------- |
| **tax_status** | Accepts a string: `'taxable'`, `'shipping'`, or `'none'`. See [ProductTaxStatus constant class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Enums/ProductTaxStatus.php). |
| **tax_class** | Accepts a string: a tax class slug. |
| **shipping_class** | Accepts a string or array of strings: one or more shipping class slugs. |

#### Examples

```php
// Get taxable products.
$products = wc_get_products( array( 'tax_status' => 'taxable' ) );
```

```php
// Using constant class for tax status.
$products = wc_get_products( array( 'tax_status' => \Automattic\WooCommerce\Enums\ProductTaxStatus::TAXABLE ) );
```

```php
// Get products in the "Reduced Rate" tax class.
$products = wc_get_products( array( 'tax_class' => 'reduced-rate' ) );
```

```php
// Get products in the "Bulky" shipping class.
$products = wc_get_products( array( 'shipping_class' => 'bulky' ) );
```

### Reviews & ratings

| Parameter | Description |
| --------- | ----------- |
| **average_rating** | Accepts a float: the average rating. |
| **review_count** | Accepts an integer: the number of reviews. |

#### Examples

```php
// Get products with all 5-star ratings.
$products = wc_get_products( array( 'average_rating' => 5.0 ) );
```

```php
// Get products with 1 review.
$products = wc_get_products( array( 'review_count' => 1 ) );
```

### Date

Date arguments receive values following the standard format described below, allowing for more flexible queries.

| Parameter | Description |
| --------- | ----------- |
| **date_created** | Matches product creation date. Accepts a string in standard format. |
| **date_modified** | Matches product modification date. Accepts a string in standard format. |
| **date_on_sale_from** | Matches sale start date. Accepts a string in standard format. |
| **date_on_sale_to** | Matches sale end date. Accepts a string in standard format. |

#### Standard format

- `YYYY-MM-DD` - Matches on products during that one day in site timezone.
- `>YYYY-MM-DD` - Matches on products after that one day in site timezone.
- `>=YYYY-MM-DD` - Matches on products during or after that one day in site timezone.
- `<YYYY-MM-DD` - Matches on products before that one day in site timezone.
- `<=YYYY-MM-DD` - Matches on products during or before that one day in site timezone.
- `YYYY-MM-DD...YYYY-MM-DD` - Matches on products during or in between the days in site timezone.
- `TIMESTAMP` - Matches on products during that one second in UTC timezone.
- `>TIMESTAMP` - Matches on products after that one second in UTC timezone.
- `>=TIMESTAMP` - Matches on products during or after that one second in UTC timezone.
- `<TIMESTAMP` - Matches on products before that one second in UTC timezone.
- `<=TIMESTAMP` - Matches on products during or before that one second in UTC timezone.
- `TIMESTAMP...TIMESTAMP` - Matches on products during or in between the seconds in UTC timezone.

#### Examples

```php
// Get downloadable products created in the year 2016.
$products = wc_get_products( array(
    'downloadable' => true,
    'date_created' => '2016-01-01...2016-12-31',
) );
```

## Adding support for custom parameters

It is possible to add support for custom parameters in `wc_get_products()` or `WC_Product_Query`. To do this you need to filter the generated query.

```php
/**
 * Handle a custom 'customvar' query var to get products with the 'customvar' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Product_Query.
 * @return array modified $query
 */
function handle_custom_query_var( $query, $query_vars ) {
	if ( ! empty( $query_vars['customvar'] ) ) {
		if ( ! isset( $query['meta_query'] ) ) {
			$query['meta_query'] = array();
		}
		$query['meta_query'][] = array(
			'key'   => 'customvar',
			'value' => sanitize_text_field( $query_vars['customvar'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'handle_custom_query_var', 10, 2 );
```

Usage:

```php
$products = wc_get_products( array( 'customvar' => 'somevalue' ) );
```
