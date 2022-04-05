# Checkout API <!-- omit in toc -->

The checkout API facilitates the creation of orders (from the current cart) and handling payments for payment methods.

All checkout endpoints require [Nonce Tokens](nonce-tokens.md).

-   [Get Checkout Data](#get-checkout-data)
-   [Process Order and Payment](#process-order-and-payment)

## Get Checkout Data

Returns data required for the checkout. This includes a draft order (created from the current cart) and customer billing and shipping addresses. The payment information will be empty, as it's only persisted when the order gets updated via POST requests (right before payment processing).

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
GET /wc/store/v1/checkout
```

There are no parameters required for this endpoint.

```sh
curl --header "Nonce: 12345" --request GET https://example-store.com/wp-json/wc/store/v1/checkout
```

**Example response:**

```json
{
	"order_id": 146,
	"status": "checkout-draft",
	"order_key": "wc_order_VPffqyvgWVqWL",
	"customer_note": "",
	"customer_id": 1,
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
	"payment_method": "",
	"payment_result": {
		"payment_status": "",
		"payment_details": [],
		"redirect_url": ""
	}
}
```

## Process Order and Payment

Accepts the final customer addresses and chosen payment method, and any additional payment data, then attempts payment and
returns the result.

This endpoint will return an error unless a valid [Nonce Token](nonce-tokens.md) is provided.

```http
POST /wc/store/v1/checkout
```

| Attribute          | Type    | Required | Description                                                         |
| :----------------- | :------ | :------: | :------------------------------------------------------------------ |
| `billing_address`  | array   |   Yes    | Array of updated billing address data for the customer.             |
| `shipping_address` | integer |   Yes    | Array of updated shipping address data for the customer.            |
| `customer_note`    | string  |    No    | Note added to the order by the customer during checkout.            |
| `payment_method`   | string  |   Yes    | The ID of the payment method being used to process the payment.     |
| `payment_data`     | array   |    No    | Data to pass through to the payment method when processing payment. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/checkout?payment_method=paypal&payment_data[0][key]=test-key&payment_data[0][value]=test-value
```

**Example response:**

```json
{
	"order_id": 146,
	"status": "on-hold",
	"order_key": "wc_order_VPffqyvgWVqWL",
	"customer_note": "",
	"customer_id": 1,
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
	"payment_method": "cheque",
	"payment_result": {
		"payment_status": "success",
		"payment_details": [],
		"redirect_url": "https://local.wordpress.test/block-checkout/order-received/146/?key=wc_order_VPffqyvgWVqWL"
	}
}
```

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/checkout.md)
<!-- /FEEDBACK -->

