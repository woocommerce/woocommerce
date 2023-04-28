# Products API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [List Products](#list-products)
- [Single Product](#single-product)

The store products API provides public product data so it can be rendered on the client side.

## List Products

```http
GET /products
GET /products?search=product%20name
GET /products?slug=slug-1,slug-2
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
GET /products?category=22
GET /products?product-taxonomy=product-taxonomy-term-id
GET /products?tag=special-items
GET /products?attributes[0][attribute]=pa_color&attributes[0][slug]=red
GET /products?on_sale=true
GET /products?min_price=5000
GET /products?max_price=10000
GET /products?stock_status=['outofstock']
GET /products?catalog_visibility=search
GET /products?rating=4,5
GET /products?return_price_range=true
GET /products?return_attribute_counts=pa_size,pa_color
GET /products?return_rating_counts=true
```

| Attribute                                   | Type    | Required | Description                                                                                                                                                                |
|:--------------------------------------------|:--------| :------: |:---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `search`                                    | integer |    no    | Limit results to those matching a string.                                                                                                                                  |
| `slug`                                      | string  |    no    | Limit result set to products with specific slug(s). Use commas to separate.                                                                                                |
| `after`                                     | string  |    no    | Limit response to resources created after a given ISO8601 compliant date.                                                                                                  |
| `before`                                    | string  |    no    | Limit response to resources created before a given ISO8601 compliant date.                                                                                                 |
| `date_column`                               | string  |    no    | When limiting response using after/before, which date column to compare against. Allowed values: `date`, `date_gmt`, `modified`, `modified_gmt`                            |
| `exclude`                                   | array   |    no    | Ensure result set excludes specific IDs.                                                                                                                                   |
| `include`                                   | array   |    no    | Limit result set to specific ids.                                                                                                                                          |
| `offset`                                    | integer |    no    | Offset the result set by a specific number of items.                                                                                                                       |
| `order`                                     | string  |    no    | Order sort attribute ascending or descending. Allowed values: `asc`, `desc`                                                                                                |
| `orderby`                                   | string  |    no    | Sort collection by object attribute. Allowed values : `date`, `modified`, `id`, `include`, `title`, `slug`, `price`, `popularity`, `rating`, `menu_order`, `comment_count` |
| `parent`                                    | array   |    no    | Limit result set to those of particular parent IDs.                                                                                                                        |
| `parent_exclude`                            | array   |    no    | Limit result set to all items except those of a particular parent ID.                                                                                                      |
| `type`                                      | string  |    no    | Limit result set to products assigned a specific type.                                                                                                                     |
| `sku`                                       | string  |    no    | Limit result set to products with specific SKU(s). Use commas to separate.                                                                                                 |
| `featured`                                  | boolean |    no    | Limit result set to featured products.                                                                                                                                     |
| `category`                                  | string  |    no    | Limit result set to products assigned a specific category ID.                                                                                                              |
| `category_operator`                         | string  |    no    | Operator to compare product category terms. Allowed values: `in`, `not_in`, `and`                                                                                          |
| `_unstable_tax_[product-taxonomy]`          | string  |    no    | Limit result set to products assigned to the term ID of that custom product taxonomy. `[product-taxonomy]` should be the key of the custom product taxonomy registered.    |
| `_unstable_tax_[product-taxonomy]_operator` | string  |    no    | Operator to compare custom product taxonomy terms. Allowed values: `in`, `not_in`, `and`                                                                                   |
| `tag`                                       | string  |    no    | Limit result set to products assigned a specific tag ID.                                                                                                                   |
| `tag_operator`                              | string  |    no    | Operator to compare product tags. Allowed values: `in`, `not_in`, `and`                                                                                                    |
| `on_sale`                                   | boolean |    no    | Limit result set to products on sale.                                                                                                                                      |
| `min_price`                                 | string  |    no    | Limit result set to products based on a minimum price, provided using the smallest unit of the currency. E.g. provide 10025 for 100.25 USD, which is a two-decimal currency, and 1025 for 1025 JPY, which is a zero-decimal currency. |
| `max_price`                                 | string  |    no    | Limit result set to products based on a maximum price, provided using the smallest unit of the currency. E.g. provide 10025 for 100.25 USD, which is a two-decimal currency, and 1025 for 1025 JPY, which is a zero-decimal currency. |
| `stock_status`                              | array   |    no    | Limit result set to products with specified stock statuses. Expects an array of strings containing 'instock', 'outofstock' or 'onbackorder'.                               |
| `attributes`                                | array   |    no    | Limit result set to specific attribute terms. Expects an array of objects containing `attribute` (taxonomy), `term_id` or `slug`, and optional `operator` for comparison.  |
| `attribute_relation`                        | string  |    no    | The logical relationship between attributes when filtering across multiple at once.                                                                                        |
| `catalog_visibility`                        | string  |    no    | Determines if hidden or visible catalog products are shown. Allowed values: `any`, `visible`, `catalog`, `search`, `hidden`                                                |
| `rating`                                    | boolean |    no    | Limit result set to products with a certain average rating.                                                                                                                |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products"
```

**Example response:**

```json
[
	{
		"id": 34,
		"name": "WordPress Pennant",
		"slug": "wordpress-pennant",
		"variation": "",
		"permalink": "https://local.wordpress.test/product/wordpress-pennant/",
		"sku": "wp-pennant",
		"summary": "<p>This is an external product.</p>",
		"short_description": "<p>This is an external product.</p>",
		"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
		"on_sale": false,
		"prices": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"price": "1105",
			"regular_price": "1105",
			"sale_price": "1105",
			"price_range": null
		},
		"average_rating": "0",
		"review_count": 0,
		"images": [
			{
				"id": 57,
				"src": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1.jpg",
				"thumbnail": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-324x324.jpg",
				"srcset": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1.jpg 800w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-324x324.jpg 324w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-100x100.jpg 100w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-416x416.jpg 416w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-300x300.jpg 300w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-150x150.jpg 150w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-768x768.jpg 768w",
				"sizes": "(max-width: 800px) 100vw, 800px",
				"name": "pennant-1.jpg",
				"alt": ""
			}
		],
		"has_options": false,
		"is_purchasable": true,
		"is_in_stock": true,
		"low_stock_remaining": null,
		"add_to_cart": {
			"text": "Add to cart",
			"description": "Add &ldquo;WordPress Pennant&rdquo; to your cart"
		}
	}
]
```

## Single Product by ID

Get a single product by id.

```http
GET /products/:id
```

| Attribute | Type    | Required | Description                        |
| :-------- | :------ | :------: | :--------------------------------- |
| `id`      | integer |   Yes    | The ID of the product to retrieve. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/34"
```

**Example response:**

```json
{
	"id": 34,
	"name": "WordPress Pennant",
	"slug": "wordpress-pennant",
	"variation": "",
	"permalink": "https://local.wordpress.test/product/wordpress-pennant/",
	"sku": "wp-pennant",
	"summary": "<p>This is an external product.</p>",
	"short_description": "<p>This is an external product.</p>",
	"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
	"on_sale": false,
	"prices": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"price": "1105",
		"regular_price": "1105",
		"sale_price": "1105",
		"price_range": null
	},
	"average_rating": "0",
	"review_count": 0,
	"images": [
		{
			"id": 57,
			"src": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1.jpg",
			"thumbnail": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-324x324.jpg",
			"srcset": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1.jpg 800w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-324x324.jpg 324w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-100x100.jpg 100w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-416x416.jpg 416w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-300x300.jpg 300w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-150x150.jpg 150w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-768x768.jpg 768w",
			"sizes": "(max-width: 800px) 100vw, 800px",
			"name": "pennant-1.jpg",
			"alt": ""
		}
	],
	"has_options": false,
	"is_purchasable": true,
	"is_in_stock": true,
	"low_stock_remaining": null,
	"add_to_cart": {
		"text": "Add to cart",
		"description": "Add &ldquo;WordPress Pennant&rdquo; to your cart"
	}
}
```

## Single Product by slug

Get a single product by slug.

```http
GET /products/:slug
```

| Attribute | Type   | Required | Description                          |
|:----------|:-------| :------: |:-------------------------------------|
| `slug`    | string |   Yes    | The slug of the product to retrieve. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products/wordpress-pennant"
```

**Example response:**

```json
{
	"id": 34,
	"name": "WordPress Pennant",
	"slug": "wordpress-pennant",
	"variation": "",
	"permalink": "https://local.wordpress.test/product/wordpress-pennant/",
	"sku": "wp-pennant",
	"summary": "<p>This is an external product.</p>",
	"short_description": "<p>This is an external product.</p>",
	"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
	"on_sale": false,
	"prices": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"price": "1105",
		"regular_price": "1105",
		"sale_price": "1105",
		"price_range": null
	},
	"average_rating": "0",
	"review_count": 0,
	"images": [
		{
			"id": 57,
			"src": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1.jpg",
			"thumbnail": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-324x324.jpg",
			"srcset": "https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1.jpg 800w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-324x324.jpg 324w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-100x100.jpg 100w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-416x416.jpg 416w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-300x300.jpg 300w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-150x150.jpg 150w, https://local.wordpress.test/wp-content/uploads/2020/03/pennant-1-768x768.jpg 768w",
			"sizes": "(max-width: 800px) 100vw, 800px",
			"name": "pennant-1.jpg",
			"alt": ""
		}
	],
	"has_options": false,
	"is_purchasable": true,
	"is_in_stock": true,
	"low_stock_remaining": null,
	"add_to_cart": {
		"text": "Add to cart",
		"description": "Add &ldquo;WordPress Pennant&rdquo; to your cart"
	}
}
```

## Product Variations

By default, Store API excludes product variations. You can retrieve the variations for a product by using the `type=variation`.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/products?type=variation"
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/products.md)

<!-- /FEEDBACK -->

