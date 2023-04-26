# Cart API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Responses](#responses)
    -   [Cart Response](#cart-response)
    -   [Error Response](#error-response)
-   [Get Cart](#get-cart)
-   [Add Item](#add-item)
-   [Remove Item](#remove-item)
-   [Update Item](#update-item)
-   [Apply Coupon](#apply-coupon)
-   [Remove Coupon](#remove-coupon)
-   [Update Customer](#update-customer)
-   [Select Shipping Rate](#select-shipping-rate)

The cart API returns the current state of the cart for the current session or logged in user.

All POST endpoints require [Nonce Tokens](nonce-tokens.md) and return the updated state of the full cart once complete.

## Responses

All endpoints under `/cart` (listed in this doc) return responses in the same format; a cart object which includes cart items, applied coupons, shipping addresses and rates, and non-sensitive customer data.

### Cart Response

```json
{
	"coupons": [
		{
			"code": "discount20",
			"totals": {
				"currency_code": "GBP",
				"currency_symbol": "£",
				"currency_minor_unit": 2,
				"currency_decimal_separator": ".",
				"currency_thousand_separator": ",",
				"currency_prefix": "£",
				"currency_suffix": "",
				"total_discount": "421",
				"total_discount_tax": "0"
			}
		}
	],
	"shipping_rates": [
		{
			"package_id": 0,
			"name": "Shipping",
			"destination": {
				"address_1": "550 Central Park West",
				"address_2": "Corner Penthouse Spook Central",
				"city": "New York",
				"state": "NY",
				"postcode": "10023",
				"country": "US"
			},
			"items": [
				{
					"key": "9bf31c7ff062936a96d3c8bd1f8f2ff3",
					"name": "Beanie",
					"quantity": 1
				},
				{
					"key": "e369853df766fa44e1ed0ff613f563bd",
					"name": "WordPress Pennant",
					"quantity": 1
				}
			],
			"shipping_rates": [
				{
					"rate_id": "flat_rate:4",
					"name": "Flat rate",
					"description": "",
					"delivery_time": "",
					"price": "500",
					"instance_id": 4,
					"method_id": "flat_rate",
					"meta_data": [
						{
							"key": "Items",
							"value": "Beanie &times; 1, WordPress Pennant &times; 1"
						}
					],
					"selected": true,
					"currency_code": "GBP",
					"currency_symbol": "£",
					"currency_minor_unit": 2,
					"currency_decimal_separator": ".",
					"currency_thousand_separator": ",",
					"currency_prefix": "£",
					"currency_suffix": ""
				}
			]
		}
	],
	"shipping_address": {
		"first_name": "Peter",
		"last_name": "Venkman",
		"company": "",
		"address_1": "550 Central Park West",
		"address_2": "Corner Penthouse Spook Central",
		"city": "New York",
		"state": "NY",
		"postcode": "10023",
		"country": "US"
	},
	"items": [
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
			}
		}
	],
	"items_count": 2,
	"items_weight": 0,
	"needs_payment": true,
	"needs_shipping": true,
	"has_calculated_shipping": true,
	"totals": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"total_items": "2105",
		"total_items_tax": "0",
		"total_fees": "0",
		"total_fees_tax": "0",
		"total_discount": "421",
		"total_discount_tax": "0",
		"total_shipping": "500",
		"total_shipping_tax": "0",
		"total_price": "2184",
		"total_tax": "0",
		"tax_lines": []
	},
	"errors": [],
	"payment_methods": [ "cod", "bacs", "cheque" ],
	"payment_requirements": [ "products" ],
	"extensions": {}
}
```

### Error Response

If a cart action cannot be performed, an error response will be returned. This will include a reason code and an error message:

```json
{
	"code": "woocommerce_rest_cart_invalid_product",
	"message": "This product cannot be added to the cart.",
	"data": {
		"status": 400
	}
}
```

Some error responses indicate conflicts (error 409), for example, when an item cannot be found or a coupon is no longer applied. When this type of response is returned, the current state of the cart from the server is also returned as part of the error data:

```json
{
  "code": "woocommerce_rest_cart_invalid_key",
  "message": "Cart item no longer exists or is invalid.",
  "data": {
    "status": 409,
    "cart": { ... }
  }
}
```

This allows the client to remain in sync with the cart data without additional requests, should the cart change or become outdated.

## Get Cart

```http
GET /cart
```

There are no parameters required for this endpoint.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart"
```

Returns the full cart object response (see [Cart Response](#cart-response)).

## Add Item

Add an item to the cart and return the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/add-item
```

| Attribute   | Type    | Required | Description                                                                                                                               |
| :---------- | :------ | :------: | :---------------------------------------------------------------------------------------------------------------------------------------- |
| `id`        | integer |   Yes    | The cart item product or variation ID.                                                                                                    |
| `quantity`  | integer |   Yes    | Quantity of this item in the cart.                                                                                                        |
| `variation` | array   |   Yes    | Chosen attributes (for variations) containing an array of objects with keys `attribute` and `value`. See notes on attribute naming below. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/cart/add-item?id=100&quantity=1
```

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

If you want to add supplemental cart item data before it is passed into `CartController::add_to_cart` use the [`woocommerce_store_api_add_to_cart_data`](https://github.com/woocommerce/woocommerce-blocks/blob/4d1c295a2bace9a4f6397cfd5469db31083d477a/docs/third-party-developers/extensibility/hooks/filters.md#woocommerce_store_api_add_to_cart_data) filter.

**Variation attribute naming:**

When adding variations to the cart, the naming of the attribute is important.

For global attributes, the attribute posted to the API should be the slug of the attribute. This should have a `pa_` prefix. For example, if you have an attribute named `Color`, the slug will be `pa_color`.

For product specific attributes, the attribute posted to the API should be the name of the attribute. For example, if you have an attribute named `Size`, the name will be `Size`. This is case-sensitive.

**Example POST body:**

```json
{
	"id": 13,
	"quantity": 1,
	"variation": [
		{
			"attribute": "pa_color",
			"value": "blue"
		},
		{
			"attribute": "Logo",
			"value": "Yes"
		}
	]
}
```

The above example adds a product variation to the cart with attributes size and color.

**Batching:**

If you want to add multiple items at once, you need to use the batch endpoint:

```http
POST /wc/store/v1/batch
```

The JSON payload for adding multiple items to the cart would look like this:

```json
{
	"requests": [
		{
			"path": "/wc/store/v1/cart/add-item",
			"method": "POST",
			"cache": "no-store",
			"body": {
				"id": 26,
				"quantity": 1
			},
			"headers": {
				"Nonce": "1db1d13784"
			}
		},
		{
			"path": "/wc/store/v1/cart/add-item",
			"method": "POST",
			"cache": "no-store",
			"body": {
				"id": 27,
				"quantity": 1
			},
			"headers": {
				"Nonce": "1db1d13784"
			}
		}
	]
}
```

## Remove Item

Remove an item from the cart and return the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/remove-item
```

| Attribute | Type   | Required | Description                       |
| :-------- | :----- | :------: | :-------------------------------- |
| `key`     | string |   Yes    | The key of the cart item to edit. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/cart/remove-item?key=e369853df766fa44e1ed0ff613f563bd
```

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

## Update Item

Update an item in the cart and return the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/update-item
```

| Attribute  | Type    | Required | Description                        |
| :--------- | :------ | :------: | :--------------------------------- |
| `key`      | string  |   Yes    | The key of the cart item to edit.  |
| `quantity` | integer |   Yes    | Quantity of this item in the cart. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/cart/update-item?key=e369853df766fa44e1ed0ff613f563bd&quantity=10
```

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

## Apply Coupon

Apply a coupon to the cart and return the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/apply-coupon/
```

| Attribute | Type   | Required | Description                                    |
| :-------- | :----- | :------: | :--------------------------------------------- |
| `code`    | string |   Yes    | The coupon code you wish to apply to the cart. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/cart/apply-coupon?code=20off
```

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

## Remove Coupon

Remove a coupon from the cart and return the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/remove-coupon/
```

| Attribute | Type   | Required | Description                                       |
| :-------- | :----- | :------: | :------------------------------------------------ |
| `code`    | string |   Yes    | The coupon code you wish to remove from the cart. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/cart/remove-coupon?code=20off
```

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

## Update Customer

Update customer data and return the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/update-customer
```

| Attribute                     | Type   | Required | Description                                                                              |
| :---------------------------- | :----- | :------: | :--------------------------------------------------------------------------------------- |
| `billing_address`             | object |    no    | Customer billing address.                                                                |
| `billing_address.first_name`  | string |    no    | Customer first name.                                                                     |
| `billing_address.last_name`   | string |    no    | Customer last name.                                                                      |
| `billing_address.address_1`   | string |    no    | First line of the address being shipped to.                                              |
| `billing_address.address_2`   | string |    no    | Second line of the address being shipped to.                                             |
| `billing_address.city`        | string |    no    | City of the address being shipped to.                                                    |
| `billing_address.state`       | string |    no    | ISO code, or name, for the state, province, or district of the address being shipped to. |
| `billing_address.postcode`    | string |    no    | Zip or Postcode of the address being shipped to.                                         |
| `billing_address.country`     | string |    no    | ISO code for the country of the address being shipped to.                                |
| `billing_address.email`       | string |    no    | Email for the customer.                                                                  |
| `billing_address.phone`       | string |    no    | Phone number of the customer.                                                            |
| `shipping_address`            | object |    no    | Customer shipping address.                                                               |
| `shipping_address.first_name` | string |    no    | Customer first name.                                                                     |
| `shipping_address.last_name`  | string |    no    | Customer last name.                                                                      |
| `shipping_address.address_1`  | string |    no    | First line of the address being shipped to.                                              |
| `shipping_address.address_2`  | string |    no    | Second line of the address being shipped to.                                             |
| `shipping_address.city`       | string |    no    | City of the address being shipped to.                                                    |
| `shipping_address.state`      | string |    no    | ISO code, or name, for the state, province, or district of the address being shipped to. |
| `shipping_address.postcode`   | string |    no    | Zip or Postcode of the address being shipped to.                                         |
| `shipping_address.country`    | string |    no    | ISO code for the country of the address being shipped to.                                |

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

## Select Shipping Rate

Selects an available shipping rate for a package, then returns the full cart response, or an error.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /cart/select-shipping-rate
```

| Attribute    | Type    | Required | Description                                     |
| :----------- | :------ | :------: | :---------------------------------------------- |
| `package_id` | integer |   yes    | The ID of the shipping package within the cart. |
| `rate_id`    | string  |   yes    | The chosen rate ID for the package.             |

```sh
curl --header "Nonce: 12345" --request POST /cart/select-shipping-rate?package_id=1&rate_id=flat_rate:1
```

Returns the full [Cart Response](#cart-response) on success, or an [Error Response](#error-response) on failure.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/cart.md)

<!-- /FEEDBACK -->
