# Cart Items API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [List Cart Items](#list-cart-items)
- [Single Cart Item](#single-cart-item)
- [Add Cart Item](#add-cart-item)
- [Edit Single Cart Item](#edit-single-cart-item)
- [Delete Single Cart Item](#delete-single-cart-item)
- [Delete All Cart Items](#delete-all-cart-items)

## List Cart Items

```http
GET /cart/items
```

There are no parameters required for this endpoint.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart/items"
```

**Example response:**

```json
[
	{
		"key": "9bf31c7ff062936a96d3c8bd1f8f2ff3",
		"id": 15,
		"quantity": 1,
		"quantity_limits": {
			"minimum": 1,
			"maximum": 99,
			"multiple_of": 1,
			"editable": true
		},
		"name": "Beanie",
		"summary": "<p>This is a simple product.</p>",
		"short_description": "<p>This is a simple product.</p>",
		"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
		"sku": "woo-beanie",
		"low_stock_remaining": null,
		"backorders_allowed": false,
		"show_backorder_badge": false,
		"sold_individually": false,
		"permalink": "https://local.wordpress.test/product/beanie/",
		"images": [
			{
				"id": 44,
				"src": "https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2.jpg",
				"thumbnail": "https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-324x324.jpg",
				"srcset": "https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2.jpg 801w, https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-324x324.jpg 324w, https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-100x100.jpg 100w, https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-416x416.jpg 416w, https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-300x300.jpg 300w, https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-150x150.jpg 150w, https://local.wordpress.test/wp-content/uploads/2020/03/beanie-2-768x768.jpg 768w",
				"sizes": "(max-width: 801px) 100vw, 801px",
				"name": "beanie-2.jpg",
				"alt": ""
			}
		],
		"variation": [],
		"item_data": [],
		"prices": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"price": "1000",
			"regular_price": "2000",
			"sale_price": "1000",
			"price_range": null,
			"raw_prices": {
				"precision": 6,
				"price": "10000000",
				"regular_price": "20000000",
				"sale_price": "10000000"
			}
		},
		"totals": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"line_subtotal": "1000",
			"line_subtotal_tax": "0",
			"line_total": "800",
			"line_total_tax": "0"
		},
		"_links": {
			"self": [
				{
					"href": "https://local.wordpress.test/wp-json/wc/store/v1/cart/items/9bf31c7ff062936a96d3c8bd1f8f2ff3"
				}
			],
			"collection": [
				{
					"href": "https://local.wordpress.test/wp-json/wc/store/v1/cart/items"
				}
			]
		}
	},
	{
		"key": "e369853df766fa44e1ed0ff613f563bd",
		"id": 34,
		"quantity": 1,
		"quantity_limits": {
			"minimum": 1,
			"maximum": 99,
			"multiple_of": 1,
			"editable": true
		},
		"name": "WordPress Pennant",
		"summary": "<p>This is an external product.</p>",
		"short_description": "<p>This is an external product.</p>",
		"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
		"sku": "wp-pennant",
		"low_stock_remaining": null,
		"backorders_allowed": false,
		"show_backorder_badge": false,
		"sold_individually": false,
		"permalink": "https://local.wordpress.test/product/wordpress-pennant/",
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
		"variation": [],
		"item_data": [],
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
			"price_range": null,
			"raw_prices": {
				"precision": 6,
				"price": "11050000",
				"regular_price": "11050000",
				"sale_price": "11050000"
			}
		},
		"totals": {
			"currency_code": "GBP",
			"currency_symbol": "£",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "£",
			"currency_suffix": "",
			"line_subtotal": "1105",
			"line_subtotal_tax": "0",
			"line_total": "884",
			"line_total_tax": "0"
		},
		"_links": {
			"self": [
				{
					"href": "https://local.wordpress.test/wp-json/wc/store/v1/cart/items/e369853df766fa44e1ed0ff613f563bd"
				}
			],
			"collection": [
				{
					"href": "https://local.wordpress.test/wp-json/wc/store/v1/cart/items"
				}
			]
		}
	}
]
```

## Single Cart Item

Get a single cart item.

```http
GET /cart/items/:key
```

| Attribute | Type   | Required | Description                           |
| :-------- | :----- | :------: | :------------------------------------ |
| `key`     | string |   Yes    | The key of the cart item to retrieve. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart/items/e369853df766fa44e1ed0ff613f563bd"
```

**Example response:**

```json
{
	"key": "e369853df766fa44e1ed0ff613f563bd",
	"id": 34,
	"quantity": 1,
	"quantity_limits": {
		"minimum": 1,
		"maximum": 99,
		"multiple_of": 1,
		"editable": true
	},
	"name": "WordPress Pennant",
	"summary": "<p>This is an external product.</p>",
	"short_description": "<p>This is an external product.</p>",
	"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
	"sku": "wp-pennant",
	"low_stock_remaining": null,
	"backorders_allowed": false,
	"show_backorder_badge": false,
	"sold_individually": false,
	"permalink": "https://local.wordpress.test/product/wordpress-pennant/",
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
	"variation": [],
	"item_data": [],
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
		"price_range": null,
		"raw_prices": {
			"precision": 6,
			"price": "11050000",
			"regular_price": "11050000",
			"sale_price": "11050000"
		}
	},
	"totals": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"line_subtotal": "1105",
		"line_subtotal_tax": "0",
		"line_total": "884",
		"line_total_tax": "0"
	},
	"_links": {
		"self": [
			{
				"href": "https://local.wordpress.test/wp-json/wc/store/v1/cart/items/(?P<key>[\\w-]{32})/e369853df766fa44e1ed0ff613f563bd"
			}
		],
		"collection": [
			{
				"href": "https://local.wordpress.test/wp-json/wc/store/v1/cart/items/(?P<key>[\\w-]{32})"
			}
		]
	}
}
```

## Add Cart Item

Add an item to the cart. Returns the new item object that was added, or an error if it was not added.

```http
POST /cart/items/
```

| Attribute   | Type    | Required | Description                                                                                          |
| :---------- | :------ | :------: | :--------------------------------------------------------------------------------------------------- |
| `id`        | integer |   Yes    | The cart item product or variation ID.                                                               |
| `quantity`  | integer |   Yes    | Quantity of this item in the cart.                                                                   |
| `variation` | array   |   Yes    | Chosen attributes (for variations) containing an array of objects with keys `attribute` and `value`. |

```sh
curl --request POST https://example-store.com/wp-json/wc/store/v1/cart/items?id=100&quantity=1
```

For an example response, see [Single Cart Item](#single-cart-item).

## Edit Single Cart Item

Edit an item in the cart.

```http
PUT /cart/items/:key
```

| Attribute  | Type    | Required | Description                        |
| :--------- | :------ | :------: | :--------------------------------- |
| `key`      | string  |   Yes    | The key of the cart item to edit.  |
| `quantity` | integer |   Yes    | Quantity of this item in the cart. |

```sh
curl --request PUT https://example-store.com/wp-json/wc/store/v1/cart/items/e369853df766fa44e1ed0ff613f563bd?quantity=10
```

For an example response, see [Single Cart Item](#single-cart-item).

## Delete Single Cart Item

Delete/remove an item from the cart.

```http
DELETE /cart/items/:key
```

| Attribute | Type   | Required | Description                       |
| :-------- | :----- | :------: | :-------------------------------- |
| `key`     | string |   Yes    | The key of the cart item to edit. |

```sh
curl --request DELETE https://example-store.com/wp-json/wc/store/v1/cart/items/e369853df766fa44e1ed0ff613f563bd
```

## Delete All Cart Items

Delete/remove all items from the cart.

```http
DELETE /cart/items/
```

There are no parameters required for this endpoint.

```sh
curl --request DELETE https://example-store.com/wp-json/wc/store/v1/cart/items
```

**Example response:**

```json
[]
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/cart-items.md)

<!-- /FEEDBACK -->

