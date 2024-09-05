# Checkout order API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Process Order and Payment](#process-order-and-payment)
-   [Payment Data](#payment-data)

The checkout order API facilitates the processing of existing orders and handling payments.

All checkout order endpoints require a [Nonce Token](nonce-tokens.md) or a [Cart Token](cart-tokens.md) otherwise these endpoints will return an error.

## Process Order and Payment

Accepts the final chosen payment method, and any additional payment data, then attempts payment and
returns the result.

```http
POST /wc/store/v1/checkout/{ORDER_ID}
```

| Attribute          | Type   | Required | Description                                                         |
| :----------------- | :----- | :------: | :------------------------------------------------------------------ |
| `key`              | string |   Yes    | The key for the order verification.                                 |
| `billing_email`    | string |   No     | The email address used to verify guest orders.                      |
| `billing_address`  | object |   Yes    | Object of updated billing address data for the customer.            |
| `shipping_address` | object |   Yes    | Object of updated shipping address data for the customer.           |
| `payment_method`   | string |   Yes    | The ID of the payment method being used to process the payment.     |
| `payment_data`     | array  |    No    | Data to pass through to the payment method when processing payment. |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/checkout/{ORDER_ID} -d '{"key":"wc_order_oFmQYREzh9Tfv","billing_email":"admin@example.com","payment_method":"cheque","billing_address":{...},"shipping_address":{...}'
```

**Example request:**

```json
{
	"key": "wc_order_oFmQYREzh9Tfv",
	"billing_email": "admin@example.com",
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
		"country": "US",
		"phone": "555-2368"
	},
	"payment_method": "cheque",
	"payment_data": []
}
```

**Example response:**

```json
{
	"order_id": 146,
	"status": "on-hold",
	"order_key": "wc_order_oFmQYREzh9Tfv",
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
		"country": "US",
		"phone": "555-2368"
	},
	"payment_method": "cheque",
	"payment_result": {
		"payment_status": "success",
		"payment_details": [],
		"redirect_url": "https://local.wordpress.test/block-checkout/order-received/146/?key=wc_order_VPffqyvgWVqWL"
	}
}
```

## Payment Data

There are many payment gateways available for merchants to use, and each one will be expecting different `payment_data`. We cannot comprehensively list all expected requests for all payment gateways, and we would recommend reaching out to the authors of the payment gateway plugins you're working with for further information.

An example of the payment data sent to the Checkout Order endpoint when using the [WooCommerce Stripe Payment Gateway](https://wordpress.org/plugins/woocommerce-gateway-stripe/) is shown below.

For further information on generating a `stripe_source` please check [the Stripe documentation](https://stripe.com/docs).

```json
{
	"payment_data": [
		{
			"key": "stripe_source",
			"value": "src_xxxxxxxxxxxxx"
		},
		{
			"key": "billing_email",
			"value": "myemail@email.com"
		},
		{
			"key": "billing_first_name",
			"value": "Jane"
		},
		{
			"key": "billing_last_name",
			"value": "Doe"
		},
		{
			"key": "paymentMethod",
			"value": "stripe"
		},
		{
			"key": "paymentRequestType",
			"value": "cc"
		},
		{
			"key": "wc-stripe-new-payment-method",
			"value": true
		}
	]
}
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/checkout-order.md)

<!-- /FEEDBACK -->

