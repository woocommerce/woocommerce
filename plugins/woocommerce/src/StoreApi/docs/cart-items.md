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

There are no extra parameters needed to use this endpoint.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart/items"
```

**Example response:**

```json
[
	{
		"key": "c74d97b01eae257e44aa9d5bade97baf",
		"id": 16,
		"quantity": 1,
		"type": "simple",
		"quantity_limits": {
			"minimum": 1,
			"maximum": 1,
			"multiple_of": 1,
			"editable": false
		},
		"name": "Beanie",
		"short_description": "<p>This is a simple product.</p>",
		"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
		"sku": "woo-beanie",
		"low_stock_remaining": null,
		"backorders_allowed": false,
		"show_backorder_badge": false,
		"sold_individually": true,
		"permalink": "https://store.local/product/beanie/",
		"images": [
			{
				"id": 45,
				"src": "https://store.local/wp-content/uploads/2023/01/beanie-2.jpg",
				"thumbnail": "https://store.local/wp-content/uploads/2023/01/beanie-2-450x450.jpg",
				"srcset": "https://store.local/wp-content/uploads/2023/01/beanie-2.jpg 801w, https://store.local/wp-content/uploads/2023/01/beanie-2-450x450.jpg 450w, https://store.local/wp-content/uploads/2023/01/beanie-2-100x100.jpg 100w, https://store.local/wp-content/uploads/2023/01/beanie-2-600x600.jpg 600w, https://store.local/wp-content/uploads/2023/01/beanie-2-300x300.jpg 300w, https://store.local/wp-content/uploads/2023/01/beanie-2-150x150.jpg 150w, https://store.local/wp-content/uploads/2023/01/beanie-2-768x768.jpg 768w",
				"sizes": "(max-width: 801px) 100vw, 801px",
				"name": "beanie-2.jpg",
				"alt": ""
			}
		],
		"variation": [],
		"item_data": [],
		"prices": {
			"price": "1800",
			"regular_price": "2000",
			"sale_price": "1800",
			"price_range": null,
			"currency_code": "USD",
			"currency_symbol": "$",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "$",
			"currency_suffix": "",
			"raw_prices": {
				"precision": 6,
				"price": "18000000",
				"regular_price": "20000000",
				"sale_price": "18000000"
			}
		},
		"totals": {
			"line_subtotal": "1800",
			"line_subtotal_tax": "360",
			"line_total": "1800",
			"line_total_tax": "360",
			"currency_code": "USD",
			"currency_symbol": "$",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "$",
			"currency_suffix": ""
		},
		"catalog_visibility": "visible",
		"extensions": {},
		"_links": {
			"self": [
				{
					"href": "https://store.local/wp-json/wc/store/v1/cart/items/c74d97b01eae257e44aa9d5bade97baf"
				}
			],
			"collection": [
				{
					"href": "https://store.local/wp-json/wc/store/v1/cart/items"
				}
			]
		}
	},
	{
		"key": "e03e407f41901484125496b5ec69a76f",
		"id": 29,
		"quantity": 1,
		"type": "variation",
		"quantity_limits": {
			"minimum": 1,
			"maximum": 9999,
			"multiple_of": 1,
			"editable": true
		},
		"name": "Hoodie",
		"short_description": "",
		"description": "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.</p>",
		"sku": "woo-hoodie-red",
		"low_stock_remaining": null,
		"backorders_allowed": false,
		"show_backorder_badge": false,
		"sold_individually": false,
		"permalink": "https://store.local/product/hoodie/?attribute_pa_color=red&attribute_logo=No",
		"images": [
			{
				"id": 40,
				"src": "https://store.local/wp-content/uploads/2023/01/hoodie-2.jpg",
				"thumbnail": "https://store.local/wp-content/uploads/2023/01/hoodie-2-450x450.jpg",
				"srcset": "https://store.local/wp-content/uploads/2023/01/hoodie-2.jpg 801w, https://store.local/wp-content/uploads/2023/01/hoodie-2-450x450.jpg 450w, https://store.local/wp-content/uploads/2023/01/hoodie-2-100x100.jpg 100w, https://store.local/wp-content/uploads/2023/01/hoodie-2-600x600.jpg 600w, https://store.local/wp-content/uploads/2023/01/hoodie-2-300x300.jpg 300w, https://store.local/wp-content/uploads/2023/01/hoodie-2-150x150.jpg 150w, https://store.local/wp-content/uploads/2023/01/hoodie-2-768x768.jpg 768w",
				"sizes": "(max-width: 801px) 100vw, 801px",
				"name": "hoodie-2.jpg",
				"alt": ""
			}
		],
		"variation": [
			{
				"attribute": "Color",
				"value": "Red"
			},
			{
				"attribute": "Logo",
				"value": "No"
			}
		],
		"item_data": [],
		"prices": {
			"price": "4200",
			"regular_price": "4500",
			"sale_price": "4200",
			"price_range": null,
			"currency_code": "USD",
			"currency_symbol": "$",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "$",
			"currency_suffix": "",
			"raw_prices": {
				"precision": 6,
				"price": "42000000",
				"regular_price": "45000000",
				"sale_price": "42000000"
			}
		},
		"totals": {
			"line_subtotal": "4200",
			"line_subtotal_tax": "840",
			"line_total": "4200",
			"line_total_tax": "840",
			"currency_code": "USD",
			"currency_symbol": "$",
			"currency_minor_unit": 2,
			"currency_decimal_separator": ".",
			"currency_thousand_separator": ",",
			"currency_prefix": "$",
			"currency_suffix": ""
		},
		"catalog_visibility": "visible",
		"extensions": {},
		"_links": {
			"self": [
				{
					"href": "https://store.local/wp-json/wc/store/v1/cart/items/e03e407f41901484125496b5ec69a76f"
				}
			],
			"collection": [
				{
					"href": "https://store.local/wp-json/wc/store/v1/cart/items"
				}
			]
		}
	}
]
```

## Single Cart Item

Get a single cart item by its key.

```http
GET /cart/items/:key
```

| Attribute | Type   | Required | Description                           |
| :-------- | :----- | :------: | :------------------------------------ |
| `key`     | string |   Yes    | The key of the cart item to retrieve. |

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart/items/c74d97b01eae257e44aa9d5bade97baf"
```

**Example response:**

```json
{
	"key": "c74d97b01eae257e44aa9d5bade97baf",
	"id": 16,
	"quantity": 1,
	"quantity_limits": {
		"minimum": 1,
		"maximum": 1,
		"multiple_of": 1,
		"editable": false
	},
	"name": "Beanie",
	"short_description": "<p>This is a simple product.</p>",
	"description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>",
	"sku": "woo-beanie",
	"low_stock_remaining": null,
	"backorders_allowed": false,
	"show_backorder_badge": false,
	"sold_individually": true,
	"permalink": "https://store.local/product/beanie/",
	"images": [
		{
			"id": 45,
			"src": "https://store.local/wp-content/uploads/2023/01/beanie-2.jpg",
			"thumbnail": "https://store.local/wp-content/uploads/2023/01/beanie-2-450x450.jpg",
			"srcset": "https://store.local/wp-content/uploads/2023/01/beanie-2.jpg 801w, https://store.local/wp-content/uploads/2023/01/beanie-2-450x450.jpg 450w, https://store.local/wp-content/uploads/2023/01/beanie-2-100x100.jpg 100w, https://store.local/wp-content/uploads/2023/01/beanie-2-600x600.jpg 600w, https://store.local/wp-content/uploads/2023/01/beanie-2-300x300.jpg 300w, https://store.local/wp-content/uploads/2023/01/beanie-2-150x150.jpg 150w, https://store.local/wp-content/uploads/2023/01/beanie-2-768x768.jpg 768w",
			"sizes": "(max-width: 801px) 100vw, 801px",
			"name": "beanie-2.jpg",
			"alt": ""
		}
	],
	"variation": [],
	"item_data": [],
	"prices": {
		"price": "1800",
		"regular_price": "2000",
		"sale_price": "1800",
		"price_range": null,
		"currency_code": "USD",
		"currency_symbol": "$",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "$",
		"currency_suffix": "",
		"raw_prices": {
			"precision": 6,
			"price": "18000000",
			"regular_price": "20000000",
			"sale_price": "18000000"
		}
	},
	"totals": {
		"line_subtotal": "1800",
		"line_subtotal_tax": "360",
		"line_total": "1800",
		"line_total_tax": "360",
		"currency_code": "USD",
		"currency_symbol": "$",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "$",
		"currency_suffix": ""
	},
	"catalog_visibility": "visible",
	"extensions": {},
	"_links": {
		"self": [
			{
				"href": "https://store.local/wp-json/wc/store/v1/cart/items/(?P<key>[\\w-]{32})/c74d97b01eae257e44aa9d5bade97baf"
			}
		],
		"collection": [
			{
				"href": "https://store.local/wp-json/wc/store/v1/cart/items/(?P<key>[\\w-]{32})"
			}
		]
	}
}
```

## Add Cart Item

Add an item to the cart. Returns the new cart item that was added, or an error response.

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

If you're looking to add multiple items to the cart at once, please take a look at [batching](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/src/StoreApi/docs/cart.md#add-item).

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

Removes an item from the cart by its key.

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

Removes all items from the cart at once.

```http
DELETE /cart/items/
```

There are no extra parameters needed to use this endpoint.

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

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./src/StoreApi/docs/cart-items.md)

<!-- /FEEDBACK -->
