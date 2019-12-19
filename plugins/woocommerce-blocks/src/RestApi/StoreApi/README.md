# WooCommerce Store API

The WooCommerce Store API is a public-facing REST API; unlike the main WooCommerce REST API, this API does not require authentication. It is intended to be used by client side code to provide functionality to customers.

Documentation in this readme file assumes knowledge of REST concepts.

## Current status

This API is used internally by Blocks--it is still in flux and may be subject to revisions. There is currently no versioning system and this should be used at your own risk. Eventually, it will be moved to the main WooCommerce REST API at which point it will be versioned and safe to use in other projects.

## Basic usage

Example of a valid API request using cURL:

```http
curl "https://example-store.com/wp-json/wc/store/products"
```

The API uses JSON to serialize data. You don’t need to specify `.json` at the end of an API URL.

## Namespace

Resources in the Store API are all found within the `wc/store/` namespace, and since this API extends the WordPress API, accessing it requires the `/wp-json/` base. Examples:

```http
GET /wp-json/wc/store/products
GET /wp-json/wc/store/cart
```

## Authentication

Requests to the store API do not require authentication. Only public data is returned, and most endpoints are read-only, with the exception of the cart API which only lets you manipulate data for the current user.

## Status codes

The following table gives an overview of how the API functions generally behave.

| Request type | Description                                                                                                 |
| :----------- | :---------------------------------------------------------------------------------------------------------- |
| `GET`        | Access one or more resources and return `200 OK` and the result as JSON.                                    |
| `POST`       | Return `201 Created` if the resource is successfully created and return the newly created resource as JSON. |
| `PUT`        | Return `200 OK` if the resource is modified successfully. The modified result is returned as JSON.          |
| `DELETE`     | Returns `204 No Content` if the resource was deleted successfully.                                          |

The following table shows the possible return codes for API requests.

| Response code            | Description                                                                                                                     |
| :----------------------- | :------------------------------------------------------------------------------------------------------------------------------ |
| `200 OK`                 | The request was successful, the resource(s) itself is returned as JSON.                                                         |
| `204 No Content`         | The server has successfully fulfilled the request and that there is no additional content to send in the response payload body. |
| `201 Created`            | The POST request was successful and the resource is returned as JSON.                                                           |
| `400 Bad Request`        | A required attribute of the API request is missing.                                                                             |  |
| `403 Forbidden`          | The request is not allowed.                                                                                                     |
| `404 Not Found`          | A resource could not be accessed, for example it doesn't exist.                                                                 |
| `405 Method Not Allowed` | The request is not supported.                                                                                                   |
| `500 Server Error`       | While handling the request something went wrong server-side.                                                                    |

## Pagination

If collections contain many results, they may be paginated. When listing resources you can pass the following parameters:

| Parameter  | Description                                                                            |
| :--------- | :------------------------------------------------------------------------------------- |
| `page`     | Current page of the collection. Defaults to `1`.                                       |
| `per_page` | Maximum number of items to be returned in result set. Defaults to `10`. Maximum `100`. |

In the example below, we list 20 products per page and return page 2.

```http
curl "https://example-store.com/wp-json/wc/store/products?page=2&per_page=20"
```

### Pagination headers

Additional pagination headers are also sent back.

| Header            | Description                                                               |
| :---------------- | :------------------------------------------------------------------------ |
| `X-WP-Total`      | The total number of items in the collection.                              |
| `X-WP-TotalPages` | The total number of pages in the collection.                              |
| `Link`            | Contains links to other pages; `next`, `prev`, and `up` where applicable. |

## API resources

Available resources in the Store API include:

| Resource                                                   | Available endpoints                     |
| :--------------------------------------------------------- | :-------------------------------------- |
| [`Product Collection Data`](#products-collection-data-api) | `/wc/store/products/collection-data`    |
| [`Products`](#products-api)                                | `/wc/store/products`                    |
| [`Cart`](#cart-api)                                        | `/wc/store/cart`                        |
| [`Cart Items`](#cart-items-api)                            | `/wc/store/cart/items`                  |
| [`Cart Coupons`](#cart-coupons-api)                        | `/wc/store/cart/coupons`                |
| [`Cart Shipping Rates`](#cart-shipping-rates-api)          | `/wc/store/cart/shipping-rates`         |
| [`Product Attributes`](#product-attributes-api)            | `/wc/store/products/attributes`         |
| [`Product Attribute Terms`](#product-attribute-terms-api)  | `/wc/store/products/attributes/1/terms` |

## Product Collection Data API

This endpoint allows you to get aggregate data from a collection of products, for example, the min and max price in a collection of products (ignoring pagination). This is used by blocks for product filtering widgets, since counts are based on the product catalog being viewed.

```http
GET /products/collection-data
GET /products/collection-data?calculate_price_range=true
GET /products/collection-data?calculate_attribute_counts[0][query_type]=or&calculate_attribute_counts[0][taxonomy]=pa_color
GET /products/collection-data?calculate_rating_counts=true
```

| Attribute                    | Type   | Required | Description                                                                                                                                                                                                |
| :--------------------------- | :----- | :------: | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `calculate_price_range`      | bool   |    No    | Returns the min and max price for the product collection. If false, only `null` will be returned.                                                                                                          |
| `calculate_attribute_counts` | object |    No    | Returns attribute counts for a list of attribute taxonomies you pass in via this parameter. Each should be provided as an object with keys "taxonomy" and "query_type". If empty, `null` will be returned. |
| `calculate_rating_counts`    | bool   |    No    | Returns the counts of products with a certain average rating, 1-5. If false, only `null` will be returned.                                                                                                 |

**In addition to the above attributes**, all product list attributes are supported. This allows you to get data for a certain subset of products. See [the products API list products section](#list-products) for the full list.

```http
curl "https://example-store.com/wp-json/wc/store/products/collection-data?calculate_price_range=true&calculate_attribute_counts=pa_size,pa_color&calculate_rating_counts=true"
```

Example response:

```json
{
	"price_range": [
		"currency_minor_unit": 2,
		"min_price": "0",
		"max_price": "9000",
	],
	"attribute_counts": [
		{
			"term": 22,
			"count": 4
		},
		{
			"term": 23,
			"count": 3
		},
		{
			"term": 24,
			"count": 4
		}
	],
	"rating_counts": [
		{
			"rating": 3,
			"count": 1
		},
		{
			"rating": 4,
			"count": 1
		}
	]
}
```

## Products API

### List products

```http
GET /products
GET /products?search=product%20name
GET /products?after=2017-03-22&date_column=date
GET /products?before=2017-03-22&date_column=date
GET /products?exclude=10,44,33
GET /products?include=10,44,33
GET /products?offset=10
GET /products?order=asc&orderby=price
GET /products?parent=10
GET /products?parent_exclude=10
GET /products?type=simple
GET /products?sku=sku-1,sku-2
GET /products?featured=true
GET /products?category=t-shirts
GET /products?tag=special-items
GET /products?attributes[0][attribute]=pa_color&attributes[0][slug]=red
GET /products?on_sale=true
GET /products?min_price=5000
GET /products?max_price=10000
GET /products?stock_status=outofstock
GET /products?catalog_visibility=search
GET /products?rating=4,5
GET /products?return_price_range=true
GET /products?return_attribute_counts=pa_size,pa_color
GET /products?return_rating_counts=true
```

| Attribute            | Type    | Required | Description                                                                                                                                                               |
| :------------------- | :------ | :------: | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `search`             | integer |    no    | Limit results to those matching a string.                                                                                                                                 |
| `after`              | string  |    no    | Limit response to resources created after a given ISO8601 compliant date.                                                                                                 |
| `before`             | string  |    no    | Limit response to resources created before a given ISO8601 compliant date.                                                                                                |
| `date_column`        | string  |    no    | When limiting response using after/before, which date column to compare against. Allowed values: `date`, `date_gmt`, `modified`, `modified_gmt`                           |
| `exclude`            | array   |    no    | Ensure result set excludes specific IDs.                                                                                                                                  |
| `include`            | array   |    no    | Limit result set to specific ids.                                                                                                                                         |
| `offset`             | integer |    no    | Offset the result set by a specific number of items.                                                                                                                      |
| `order`              | string  |    no    | Order sort attribute ascending or descending. Allowed values: `asc`, `desc`                                                                                               |
| `orderby`            | string  |    no    | Sort collection by object attribute. Allowed values: `date`, `modified`, `id`, `include`, `title`, `slug`, `price`, `popularity`, `rating`, `menu_order`, `comment_count` |
| `parent`             | array   |    no    | Limit result set to those of particular parent IDs.                                                                                                                       |
| `parent_exclude`     | array   |    no    | Limit result set to all items except those of a particular parent ID.                                                                                                     |
| `type`               | string  |    no    | Limit result set to products assigned a specific type.                                                                                                                    |
| `sku`                | string  |    no    | Limit result set to products with specific SKU(s). Use commas to separate.                                                                                                |
| `featured`           | boolean |    no    | Limit result set to featured products.                                                                                                                                    |
| `category`           | string  |    no    | Limit result set to products assigned a specific category ID.                                                                                                             |
| `category_operator`  | string  |    no    | Operator to compare product category terms. Allowed values: `in`, `not_in`, `and`                                                                                         |
| `tag`                | string  |    no    | Limit result set to products assigned a specific tag ID.                                                                                                                  |
| `tag_operator`       | string  |    no    | Operator to compare product tags. Allowed values: `in`, `not_in`, `and`                                                                                                   |
| `attributes`         | array   |    no    | Limit result set to specific attribute terms. Expects an array of objects containing `attribute` (taxonomy), `term_id` or `slug`, and optional `operator` for comparison. |
| `on_sale`            | boolean |    no    | Limit result set to products on sale.                                                                                                                                     |
| `min_price`          | string  |    no    | Limit result set to products based on a minimum price, provided using the smallest unit of the currency.                                                                  |
| `max_price`          | string  |    no    | Limit result set to products based on a maximum price, provided using the smallest unit of the currency.                                                                  |
| `stock_status`       | string  |    no    | Limit result set to products with specified stock status.                                                                                                                 |
| `catalog_visibility` | string  |    no    | Determines if hidden or visible catalog products are shown. Allowed values: `any`, `visible`, `catalog`, `search`, `hidden`                                               |
| `rating`             | boolean |    no    | Limit result set to products with a certain average rating.                                                                                                               |

```http
curl "https://example-store.com/wp-json/wc/store/products"
```

Example response:

```json
[
	{
		"id": 95,
		"name": "WordPress Pennant",
		"variation": "",
		"permalink": "http://local.wordpress.test/product/wordpress-pennant/",
		"sku": "wp-pennant",
		"description": "<p>This is an external product.</p>\n",
		"prices": {
			"currency_code": "GBP",
			"decimal_separator": ".",
			"thousand_separator": ",",
			"decimals": 2,
			"price_prefix": "£",
			"price_suffix": "",
			"price": 18,
			"regular_price": 18,
			"sale_price": 18,
			"price_range": null
		},
		"average_rating": "3.60",
		"review_count": 5,
		"images": [
			{
				"id": 60,
				"src": "http://local.wordpress.test/wp-content/uploads/2019/07/pennant-1.jpg",
				"name": "pennant-1.jpg",
				"alt": ""
			}
		]
	}
]
```

### Single product

Get a single product.

```http
GET /products/:id
```

| Attribute | Type    | Required | Description                        |
| :-------- | :------ | :------: | :--------------------------------- |
| `id`      | integer |   Yes    | The ID of the product to retrieve. |

```http
curl "https://example-store.com/wp-json/wc/store/products/95"
```

Example response:

```json
{
	"id": 95,
	"name": "WordPress Pennant",
	"variation": "",
	"permalink": "http://local.wordpress.test/product/wordpress-pennant/",
	"sku": "wp-pennant",
	"description": "<p>This is an external product.</p>\n",
	"prices": {
		"currency_code": "GBP",
		"decimal_separator": ".",
		"thousand_separator": ",",
		"decimals": 2,
		"price_prefix": "£",
		"price_suffix": "",
		"price": 18,
		"regular_price": 18,
		"sale_price": 18,
		"price_range": null
	},
	"average_rating": "3.60",
	"review_count": 5,
	"images": [
		{
			"id": 60,
			"src": "http://local.wordpress.test/wp-content/uploads/2019/07/pennant-1.jpg",
			"name": "pennant-1.jpg",
			"alt": ""
		}
	]
}
```

## Cart API

```http
GET /cart
```

There are no parameters required for this endpoint.

```http
curl "https://example-store.com/wp-json/wc/store/cart"
```

Example response:

```json
{
	"coupons": [],
	"items": [
		{
			"key": "6512bd43d9caa6e02c990b0a82652dca",
			"id": 11,
			"quantity": 1,
			"name": "Beanie",
			"sku": "woo-beanie",
			"permalink": "http://local.wordpress.test/product/beanie/",
			"images": [
				{
					"id": "40",
					"src": "http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2.jpg",
					"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-450x450.jpg",
					"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2.jpg 801w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-100x100.jpg 100w",
					"sizes": "(max-width: 801px) 100vw, 801px",
					"name": "beanie-2.jpg",
					"alt": ""
				}
			],
			"variation": [],
			"totals": {
				"currency_code": "GBP",
				"currency_symbol": "£",
				"currency_minor_unit": 2,
				"currency_decimal_separator": ".",
				"currency_thousand_separator": ",",
				"currency_prefix": "£",
				"currency_suffix": "",
				"line_subtotal": "1500",
				"line_subtotal_tax": "300",
				"line_total": "1500",
				"line_total_tax": "300"
			}
		},
		{
			"key": "1f0e3dad99908345f7439f8ffabdffc4",
			"id": 19,
			"quantity": 1,
			"name": "Album",
			"sku": "woo-album",
			"permalink": "http://local.wordpress.test/product/album/",
			"images": [
				{
					"id": "48",
					"src": "http://local.wordpress.test/wp-content/uploads/2019/12/album-1.jpg",
					"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/album-1-450x450.jpg",
					"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/album-1.jpg 800w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-100x100.jpg 100w",
					"sizes": "(max-width: 800px) 100vw, 800px",
					"name": "album-1.jpg",
					"alt": ""
				}
			],
			"variation": [],
			"totals": {
				"currency_code": "GBP",
				"currency_symbol": "£",
				"currency_minor_unit": 2,
				"currency_decimal_separator": ".",
				"currency_thousand_separator": ",",
				"currency_prefix": "£",
				"currency_suffix": "",
				"line_subtotal": "1250",
				"line_subtotal_tax": "250",
				"line_total": "1250",
				"line_total_tax": "250"
			}
		},
		{
			"key": "c20ad4d76fe97759aa27a0c99bff6710",
			"id": 12,
			"quantity": 1,
			"name": "Belt",
			"sku": "woo-belt",
			"permalink": "http://local.wordpress.test/product/belt/",
			"images": [
				{
					"id": "41",
					"src": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2.jpg",
					"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-450x450.jpg",
					"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2.jpg 801w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-100x100.jpg 100w",
					"sizes": "(max-width: 801px) 100vw, 801px",
					"name": "belt-2.jpg",
					"alt": ""
				}
			],
			"variation": [],
			"totals": {
				"currency_code": "GBP",
				"currency_symbol": "£",
				"currency_minor_unit": 2,
				"currency_decimal_separator": ".",
				"currency_thousand_separator": ",",
				"currency_prefix": "£",
				"currency_suffix": "",
				"line_subtotal": "4583",
				"line_subtotal_tax": "917",
				"line_total": "4583",
				"line_total_tax": "917"
			}
		}
	],
	"items_count": 3,
	"items_weight": 0,
	"needs_shipping": true,
	"totals": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"total_items": "7333",
		"total_items_tax": "1467",
		"total_fees": "0",
		"total_fees_tax": "0",
		"total_discount": "0",
		"total_discount_tax": "0",
		"total_shipping": "998",
		"total_shipping_tax": "200",
		"total_tax": "1667",
		"total_price": "9998",
		"tax_lines": [
			{
				"name": "Tax",
				"price": "1667"
			}
		]
	}
}
```

## Cart items API

### List cart items

```http
GET /cart/items
```

There are no parameters required for this endpoint.

```http
curl "https://example-store.com/wp-json/wc/store/cart/items"
```

Example response:

```json
[
	{
		"key": "6512bd43d9caa6e02c990b0a82652dca",
		"id": 11,
		"quantity": 1,
		"name": "Beanie",
		"sku": "woo-beanie",
		"permalink": "http://local.wordpress.test/product/beanie/",
		"images": [
			{
				"id": "40",
				"src": "http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2.jpg",
				"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-450x450.jpg",
				"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2.jpg 801w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/beanie-2-100x100.jpg 100w",
				"sizes": "(max-width: 801px) 100vw, 801px",
				"name": "beanie-2.jpg",
				"alt": ""
			}
		],
		"variation": [],
		"totals": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"line_subtotal": "1500",
			"line_subtotal_tax": "300",
			"line_total": "1500",
			"line_total_tax": "300"
		},
		"_links": {
			"self": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/items/6512bd43d9caa6e02c990b0a82652dca"
				}
			],
			"collection": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/items"
				}
			]
		}
	},
	{
		"key": "1f0e3dad99908345f7439f8ffabdffc4",
		"id": 19,
		"quantity": 1,
		"name": "Album",
		"sku": "woo-album",
		"permalink": "http://local.wordpress.test/product/album/",
		"images": [
			{
				"id": "48",
				"src": "http://local.wordpress.test/wp-content/uploads/2019/12/album-1.jpg",
				"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/album-1-450x450.jpg",
				"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/album-1.jpg 800w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/album-1-100x100.jpg 100w",
				"sizes": "(max-width: 800px) 100vw, 800px",
				"name": "album-1.jpg",
				"alt": ""
			}
		],
		"variation": [],
		"totals": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"line_subtotal": "1250",
			"line_subtotal_tax": "250",
			"line_total": "1250",
			"line_total_tax": "250"
		},
		"_links": {
			"self": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/items/1f0e3dad99908345f7439f8ffabdffc4"
				}
			],
			"collection": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/items"
				}
			]
		}
	},
	{
		"key": "c20ad4d76fe97759aa27a0c99bff6710",
		"id": 12,
		"quantity": 1,
		"name": "Belt",
		"sku": "woo-belt",
		"permalink": "http://local.wordpress.test/product/belt/",
		"images": [
			{
				"id": "41",
				"src": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2.jpg",
				"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-450x450.jpg",
				"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2.jpg 801w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-100x100.jpg 100w",
				"sizes": "(max-width: 801px) 100vw, 801px",
				"name": "belt-2.jpg",
				"alt": ""
			}
		],
		"variation": [],
		"totals": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"line_subtotal": "4583",
			"line_subtotal_tax": "917",
			"line_total": "4583",
			"line_total_tax": "917"
		},
		"_links": {
			"self": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/items/c20ad4d76fe97759aa27a0c99bff6710"
				}
			],
			"collection": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/items"
				}
			]
		}
	}
]
```

### Single cart item

Get a single cart item.

```http
GET /cart/items/:key
```

| Attribute | Type   | Required | Description                           |
| :-------- | :----- | :------: | :------------------------------------ |
| `key`     | string |   Yes    | The key of the cart item to retrieve. |

```http
curl "https://example-store.com/wp-json/wc/store/cart/items/11a7ec12ea1071fdecd917d6da5c87ae"
```

Example response:

```json
{
	"key": "c20ad4d76fe97759aa27a0c99bff6710",
	"id": 12,
	"quantity": 1,
	"name": "Belt",
	"sku": "woo-belt",
	"permalink": "http://local.wordpress.test/product/belt/",
	"images": [
		{
			"id": "41",
			"src": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2.jpg",
			"thumbnail": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-450x450.jpg",
			"srcset": "http://local.wordpress.test/wp-content/uploads/2019/12/belt-2.jpg 801w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-300x300.jpg 300w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-150x150.jpg 150w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-768x768.jpg 768w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-450x450.jpg 450w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-600x600.jpg 600w, http://local.wordpress.test/wp-content/uploads/2019/12/belt-2-100x100.jpg 100w",
			"sizes": "(max-width: 801px) 100vw, 801px",
			"name": "belt-2.jpg",
			"alt": ""
		}
	],
	"variation": [],
	"totals": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"line_subtotal": "4583",
		"line_subtotal_tax": "917",
		"line_total": "4583",
		"line_total_tax": "917"
	},
	"_links": {
		"self": [
			{
				"href": "http://local.wordpress.test/wp-json/wc/store/cart/items/c20ad4d76fe97759aa27a0c99bff6710"
			}
		],
		"collection": [
			{
				"href": "http://local.wordpress.test/wp-json/wc/store/cart/items"
			}
		]
	}
}
```

### New cart item

Add an item to the cart.

```http
POST /cart/items/
```

| Attribute   | Type    | Required | Description                                                                                          |
| :---------- | :------ | :------: | :--------------------------------------------------------------------------------------------------- |
| `id`        | integer |   Yes    | The cart item product or variation ID.                                                               |
| `quantity`  | integer |   Yes    | Quantity of this item in the cart.                                                                   |
| `variation` | array   |   Yes    | Chosen attributes (for variations) containing an array of objects with keys `attribute` and `value`. |

```http
curl --request POST https://example-store.com/wp-json/wc/store/cart/items?id=100&quantity=1
```

Example response:

```json
{
	"key": "3ef815416f775098fe977004015c6193",
	"id": 100,
	"quantity": 1,
	"name": "Single",
	"sku": "woo-single",
	"permalink": "http://local.wordpress.test/product/single/",
	"images": [
		{
			"id": 56,
			"src": "http://local.wordpress.test/wp-content/uploads/2019/07/single-1.jpg",
			"name": "single-1.jpg",
			"alt": ""
		}
	],
	"product_price": "5500",
	"line_subtotal": "4583",
	"line_subtotal_tax": "917",
	"line_total": "4583",
	"line_total_tax": "917",
	"variation": []
}
```

### Edit single cart item

Edit an item in the cart.

```http
PUT /cart/items/:key
```

| Attribute  | Type    | Required | Description                        |
| :--------- | :------ | :------: | :--------------------------------- |
| `key`      | string  |   Yes    | The key of the cart item to edit.  |
| `quantity` | integer |   Yes    | Quantity of this item in the cart. |

```http
curl --request PUT https://example-store.com/wp-json/wc/store/cart/items/3ef815416f775098fe977004015c6193&quantity=10
```

Example response:

```json
{
	"key": "3ef815416f775098fe977004015c6193",
	"id": 100,
	"quantity": 10,
	"name": "Single",
	"sku": "woo-single",
	"permalink": "http://local.wordpress.test/product/single/",
	"images": [
		{
			"id": 56,
			"src": "http://local.wordpress.test/wp-content/uploads/2019/07/single-1.jpg",
			"name": "single-1.jpg",
			"alt": ""
		}
	],
	"product_price": "5500",
	"line_subtotal": "4583",
	"line_subtotal_tax": "917",
	"line_total": "4583",
	"line_total_tax": "917",
	"variation": []
}
```

### Delete single cart item

Delete/remove an item from the cart.

```http
DELETE /cart/items/:key
```

| Attribute | Type   | Required | Description                       |
| :-------- | :----- | :------: | :-------------------------------- |
| `key`     | string |   Yes    | The key of the cart item to edit. |

```http
curl --request DELETE https://example-store.com/wp-json/wc/store/cart/items/3ef815416f775098fe977004015c6193
```

### Delete all cart items

Delete/remove all items from the cart.

```http
DELETE /cart/items/
```

There are no parameters required for this endpoint.

```http
curl --request DELETE https://example-store.com/wp-json/wc/store/cart/items
```

Example response:

```json
[]
```

## Cart coupons API

### List cart coupons

```http
GET /cart/coupons
```

There are no parameters required for this endpoint.

```http
curl "https://example-store.com/wp-json/wc/store/cart/items"
```

Example response:

```json
[
  {
    "code": "20off",
    "totals": {
      "currency_code": "GBP",
      "currency_symbol": "£",
      "currency_minor_unit": 2,
      "currency_decimal_separator": ".",
      "currency_thousand_separator": ",",
      "currency_prefix": "£",
      "currency_suffix": "",
      "total_discount": "1667",
      "total_discount_tax": "333"
    },
    "_links": {
      "self": [
        {
          "href": "http:\/\/local.wordpress.test\/wp-json\/wc\/store\/cart\/coupons\/20off"
        }
      ],
      "collection": [
        {
          "href": "http:\/\/local.wordpress.test\/wp-json\/wc\/store\/cart\/coupons"
        }
      ]
    }
  }
]
```

### Single cart coupon

Get a single cart coupon.

```http
GET /cart/coupons/:code
```

| Attribute | Type   | Required | Description                                     |
| :-------- | :----- | :------: | :---------------------------------------------- |
| `code`    | string |   Yes    | The coupon code of the cart coupon to retrieve. |

```http
curl "https://example-store.com/wp-json/wc/store/cart/coupons/20off"
```

Example response:

```json
{
  "code": "20off",
  "totals": {
    "currency_code": "GBP",
    "currency_symbol": "£",
    "currency_minor_unit": 2,
    "currency_decimal_separator": ".",
    "currency_thousand_separator": ",",
    "currency_prefix": "£",
    "currency_suffix": "",
    "total_discount": "1667",
    "total_discount_tax": "333"
  }
}
```

### New cart coupon

Apply a coupon to the cart.

```http
POST /cart/coupons/
```

| Attribute | Type   | Required | Description                                    |
| :-------- | :----- | :------: | :--------------------------------------------- |
| `code`    | string |   Yes    | The coupon code you wish to apply to the cart. |

```http
curl --request POST https://example-store.com/wp-json/wc/store/cart/coupons?code=20off
```

Example response:

```json
{
  "code": "20off",
  "totals": {
    "currency_code": "GBP",
    "currency_symbol": "£",
    "currency_minor_unit": 2,
    "currency_decimal_separator": ".",
    "currency_thousand_separator": ",",
    "currency_prefix": "£",
    "currency_suffix": "",
    "total_discount": "1667",
    "total_discount_tax": "333"
  }
}
```

### Delete single cart coupon

Delete/remove a coupon from the cart.

```http
DELETE /cart/coupons/:code
```

| Attribute | Type   | Required | Description                                       |
| :-------- | :----- | :------: | :------------------------------------------------ |
| `code`    | string |   Yes    | The coupon code you wish to remove from the cart. |

```http
curl --request DELETE https://example-store.com/wp-json/wc/store/cart/coupons/20off
```

### Delete all cart coupons

Delete/remove all coupons from the cart.

```http
DELETE /cart/coupons/
```

There are no parameters required for this endpoint.

```http
curl --request DELETE https://example-store.com/wp-json/wc/store/cart/coupons
```

Example response:

```json
[]
```

## Cart shipping rates API

### Get shipping rates for current cart

```http
GET /cart/shipping-rates?address_1=&address_2=&city=&state=&postcode=90210&country=US
```

| Attribute   | Type   | Required | Description                                                                              |
| :---------- | :----- | :------: | :--------------------------------------------------------------------------------------- |
| `address_1` | string |    no    | First line of the address being shipped to.                                              |
| `address_2` | string |    no    | Second line of the address being shipped to.                                             |
| `city`      | string |    no    | City of the address being shipped to.                                                    |
| `state`     | string |    no    | ISO code, or name, for the state, province, or district of the address being shipped to. |
| `postcode`  | string |    no    | Zip or Postcode of the address being shipped to.                                         |
| `country`   | string |   yes    | ISO code for the country of the address being shipped to.                                |

```http
curl "https://example-store.com/wp-json/wc/store/cart/shipping-rates?country=US&state=AL"
```

Example response:

```json
[
	{
		"destination": {
			"address_1": null,
			"address_2": null,
			"city": null,
			"state": "AL",
			"postcode": null,
			"country": "US"
		},
		"items": [ "6512bd43d9caa6e02c990b0a82652dca" ],
		"shipping-rates": [
			{
				"name": "International",
				"description": "",
				"delivery_time": "",
				"price": "20.00",
				"rate_id": "flat_rate:3",
				"instance_id": 3,
				"method_id": "flat_rate",
				"meta_data": [
					{
						"key": "Items",
						"value": "Beanie &times; 1"
					}
				]
			}
		]
	}
]
```

## Product Attributes API

```http
GET /products/attributes
```

There are no parameters required for this endpoint.

```http
curl "https://example-store.com/wp-json/wc/store/products/attributes"
```

Example response:

```json
[
	{
		"id": 1,
		"name": "Color",
		"slug": "pa_color",
		"type": "select",
		"order": "menu_order",
		"has_archives": false
	},
	{
		"id": 2,
		"name": "Size",
		"slug": "pa_size",
		"type": "select",
		"order": "menu_order",
		"has_archives": false
	}
]
```

### Single attribute

Get a single attribute taxonomy.

```http
GET /products/attributes/:id
```

| Attribute | Type    | Required | Description                          |
| :-------- | :------ | :------: | :----------------------------------- |
| `id`      | integer |   Yes    | The ID of the attribute to retrieve. |

```http
curl "https://example-store.com/wp-json/wc/store/products/attributes/1"
```

Example response:

```json
{
	"id": 1,
	"name": "Color",
	"slug": "pa_color",
	"type": "select",
	"order": "menu_order",
	"has_archives": false
}
```

## Product Attribute Terms API

```http
GET /products/attributes/:id/terms
GET /products/attributes/:id/terms&orderby=slug
```

| Attribute | Type    | Required | Description                                                                   |
| :-------- | :------ | :------: | :---------------------------------------------------------------------------- |
| `id`      | integer |   Yes    | The ID of the attribute to retrieve terms for.                                |
| `order`   | string  |    no    | Order ascending or descending. Allowed values: `asc`, `desc`                  |
| `orderby` | string  |    no    | Sort collection by object attribute. Allowed values: `name`, `slug`, `count`. |

```http
curl "https://example-store.com/wp-json/wc/store/products/attributes/1/terms"
```

Example response:

```json
[
	{
		"id": 22,
		"name": "Blue",
		"slug": "blue",
		"count": 5
	},
	{
		"id": 48,
		"name": "Burgundy",
		"slug": "burgundy",
		"count": 1
	}
]
```
