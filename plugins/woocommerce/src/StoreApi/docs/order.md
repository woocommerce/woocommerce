# Order API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Get Order](#get-order)
-   [Responses](#responses)
    -   [Order Response](#order-response)
    -   [Error Response](#error-response)

The order API returns the pay-for-order order.

## Get Order

```http
GET /order/{ORDER_ID}?key={KEY}&billing_email={BILLING_EMAIL}
```

There is one required parameter for this endpoint which is `key`. `billing_email` must be added for guest orders.

```sh
curl "https://example-store.com/wp-json/wc/store/v1/order/{ORDER_ID}?key={KEY}&billing_email={BILLING_EMAIL}"
```

Returns the full order object response (see [Order Response](#order-response)).

## Responses

Order endpoints return responses in the same format as `/cart`; an order object which includes order items, applied coupons, shipping addresses and rates, and non-sensitive customer data.

### Order Response

```json
{
	"id": 147,
	"status": "pending",
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
	"shipping_address": {
		"first_name": "Peter",
		"last_name": "Venkman",
		"company": "",
		"address_1": "550 Central Park West",
		"address_2": "Corner Penthouse Spook Central",
		"city": "New York",
		"state": "NY",
		"postcode": "10023",
		"country": "US",
		"phone": "555-2368"
	},
	"billing_address": {
		"first_name": "Peter",
		"last_name": "Venkman",
		"company": "",
		"address_1": "550 Central Park West",
		"address_2": "Corner Penthouse Spook Central",
		"city": "New York",
		"state": "NY",
		"postcode": "10023",
		"country": "US",
		"email": "admin@example.com",
		"phone": "555-2368"
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
			"item_data": [],
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
			"catalog_visibility": "view"
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
			"item_data": [],
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
			"catalog_visibility": "view"
		}
	],
	"needs_payment": true,
	"needs_shipping": true,
	"totals": {
		"subtotal":"2105",
		"total_discount": "421",
		"total_shipping": "500",
		"total_fees": "0",
		"total_tax": "0",
		"total_refund": "0",
		"total_price": "2184",
		"total_items": "2105",
		"total_items_tax": "0",
		"total_fees_tax": "0",
		"total_discount_tax": "0",
		"total_shipping_tax": "0",
		"tax_lines": []
	},
	"errors": [],
	"payment_requirements": [ "products" ],
}
```

### Error Response

If an order action cannot be performed, an error response will be returned. This will include a reason code and an error message:

```json
{
	"code": "woocommerce_rest_invalid_order",
	"message": "Invalid order ID or key provided.",
	"data": {
		"status": 401
	}
}
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./src/StoreApi/docs/order.md)

<!-- /FEEDBACK -->

