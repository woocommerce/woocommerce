# Cart Coupons API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

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

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart/coupons"
```

**Example response:**

```json
[
	{
		"code": "20off",
		"type": "fixed_cart",
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
					"href": "http://local.wordpress.test/wp-json/wc/store/v1/cart/coupons/20off"
				}
			],
			"collection": [
				{
					"href": "http://local.wordpress.test/wp-json/wc/store/v1/cart/coupons"
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

```sh
curl "https://example-store.com/wp-json/wc/store/v1/cart/coupons/20off"
```

**Example response:**

```json
{
	"code": "halfprice",
	"type": "percent",
	"totals": {
		"currency_code": "GBP",
		"currency_symbol": "£",
		"currency_minor_unit": 2,
		"currency_decimal_separator": ".",
		"currency_thousand_separator": ",",
		"currency_prefix": "£",
		"currency_suffix": "",
		"total_discount": "9950",
		"total_discount_tax": "0"
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

```sh
curl --request POST https://example-store.com/wp-json/wc/store/v1/cart/coupons?code=20off
```

**Example response:**

```json
{
	"code": "20off",
	"type": "percent",
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

```sh
curl --request DELETE https://example-store.com/wp-json/wc/store/v1/cart/coupons/20off
```

## Delete All Cart Coupons

Delete/remove all coupons from the cart.

```http
DELETE /cart/coupons/
```

There are no parameters required for this endpoint.

```sh
curl --request DELETE https://example-store.com/wp-json/wc/store/v1/cart/coupons
```

**Example response:**

```json
[]
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/cart-coupons.md)

<!-- /FEEDBACK -->

