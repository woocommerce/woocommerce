# Cart Coupons API <!-- omit in toc -->

- [List Cart Coupons](#list-cart-coupons)
- [Single Cart Coupon](#single-cart-coupon)
- [Add Cart Coupon](#add-cart-coupon)
- [Delete Single Cart Coupon](#delete-single-cart-coupon)
- [Delete All Cart Coupons](#delete-all-cart-coupons)

## List Cart Coupons

```http
GET /cart/coupons
```

There are no parameters required for this endpoint.

```http
curl "https://example-store.com/wp-json/wc/store/cart/coupons"
```

**Example response:**

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
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/coupons/20off"
				}
			],
			"collection": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/cart/coupons"
				}
			]
		}
	}
]
```

## Single Cart Coupon

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

**Example response:**

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

## Add Cart Coupon

Apply a coupon to the cart. Returns the new coupon object that was applied, or an error if it was not applied.

```http
POST /cart/coupons/
```

| Attribute | Type   | Required | Description                                    |
| :-------- | :----- | :------: | :--------------------------------------------- |
| `code`    | string |   Yes    | The coupon code you wish to apply to the cart. |

```http
curl --request POST https://example-store.com/wp-json/wc/store/cart/coupons?code=20off
```

**Example response:**

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

## Delete Single Cart Coupon

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

## Delete All Cart Coupons

Delete/remove all coupons from the cart.

```http
DELETE /cart/coupons/
```

There are no parameters required for this endpoint.

```http
curl --request DELETE https://example-store.com/wp-json/wc/store/cart/coupons
```

**Example response:**

```json
[]
```
