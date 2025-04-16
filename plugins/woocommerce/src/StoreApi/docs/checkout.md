# Checkout API <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Get Checkout Data](#get-checkout-data)
- [Process Order and Payment](#process-order-and-payment)

The checkout API facilitates the creation of orders (from the current cart) and handling payments for payment methods.

All checkout endpoints require either a [Nonce Token](nonce-tokens.md) or a [Cart Token](cart-tokens.md) otherwise these endpoints will return an error.

## Get Checkout Data

Returns data required for the checkout. This includes a draft order (created from the current cart) and customer billing and shipping addresses. The payment information will be empty, as it's only persisted when the order gets updated via POST requests (right before payment processing).

```http
GET /wc/store/v1/checkout
```

There are no parameters required for this endpoint.

```sh
curl --header "Nonce: 12345" --request GET https://example-store.com/wp-json/wc/store/v1/checkout
```

### Example Response

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

## Update checkout data

This endpoint allows you to update the checkout data for the current order. This can be called from the frontend to persist checkout fields, for example.

```http
PUT /wc/store/v1/checkout?__experimental_calc_totals=true
```

Note the `__experimental_calc_totals` parameter. This is used to determine if the cart totals should be recalculated. This should be set to true if the cart totals are being updated in response to a PUT request, false otherwise.

| Attribute           | Type   | Required | Description                                         |
| :------------------ | :----- | :------: | :-------------------------------------------------- |
| `additional_fields` | object |    No    | Name => value pairs of additional fields to update. |
| `payment_method`    | string |    No    | The ID of the payment method selected.              |
| `order_notes`       | string |    No    | Order notes.                                        |

```sh
curl --header "Nonce: 12345" --request PUT https://example-store.com/wp-json/wc/store/v1/checkout?additional_fields[plugin-namespace/leave-on-porch]=true&additional_fields[plugin-namespace/location-on-porch]=dsdd&payment_method=bacs&order_notes=Please%20leave%20package%20on%20back%20porch
```

### Example Request

```json
{
  "additional_fields": {
    "plugin-namespace/leave-on-porch": true,
    "plugin-namespace/location-on-porch": "dsdd"
  },
  "payment_method": "bacs",
  "order_notes": "Please leave package on back porch"
}
```

### Example Response

```json
{
    "order_id": 1486,
    "status": "checkout-draft",
    "order_key": "wc_order_KLpMaJ054PVlb",
    "order_number": "1486",
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
    "payment_method": "bacs",
    "payment_result": null,
    "additional_fields": {
        "plugin-namespace/leave-on-porch": true,
        "plugin-namespace/location-on-porch": "dsdd"
    },
    "__experimentalCart": { ... },
    "extensions": {}
}
```

Note the `__experimentalCart` field that is returned as part of the response. Totals will be updated on the front-end following a PUT request. This makes it possible to manipulate cart totals in response to fields persisted via the PUT request.

## Process Order and Payment

Accepts the final customer addresses and chosen payment method, and any additional payment data, then attempts payment and
returns the result.

```http
POST /wc/store/v1/checkout
```

| Attribute           | Type   | Required | Description                                                         |
| :------------------ | :----- | :------: | :------------------------------------------------------------------ |
| `billing_address`   | object |   Yes    | Object of updated billing address data for the customer.            |
| `shipping_address`  | object |   Yes    | Object of updated shipping address data for the customer.           |
| `customer_note`     | string |    No    | Note added to the order by the customer during checkout.            |
| `payment_method`    | string |   Yes    | The ID of the payment method being used to process the payment.     |
| `payment_data`      | array  |    No    | Data to pass through to the payment method when processing payment. |
| `customer_password` | string |    No    | Optionally define a password for new accounts.                      |

```sh
curl --header "Nonce: 12345" --request POST https://example-store.com/wp-json/wc/store/v1/checkout?payment_method=paypal&payment_data[0][key]=test-key&payment_data[0][value]=test-value
```

### Example Request

```json
{
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
  "customer_note": "Test notes on order.",
  "create_account": false,
  "payment_method": "cheque",
  "payment_data": [],
  "extensions": {
    "some-extension-name": {
      "some-data-key": "some data value"
    }
  }
}
```

### Example Response

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

## Payment Data

There are many payment gateways available for merchants to use, and each one will be expecting different `payment_data`. We cannot comprehensively list all expected requests for all payment gateways, and we would recommend reaching out to the authors of the payment gateway plugins you're working with for further information.

An example of the payment data sent to the Checkout endpoint when using the [WooCommerce Stripe Payment Gateway](https://wordpress.org/plugins/woocommerce-gateway-stripe/) is shown below.

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

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./src/StoreApi/docs/checkout.md)

<!-- /FEEDBACK -->
