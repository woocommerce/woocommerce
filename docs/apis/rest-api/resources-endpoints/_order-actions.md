# Order actions #

The order actions API allows you to perform specific actions with existing orders like you can from the Edit Order screen in the web app.

_Note: currently only some actions are available, other actions will be introduced at a later time._

## Send order details to customer ##

This endpoint allows you to trigger an email to the customer with the details of their order. In case the order doesn't yet have a billing email set, you can specify an email recipient. However, if the order does have an existing billing email, this will return an error, unless you also specify that the existing email should be overwritten by using the `force_email_update` parameter.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/actions/send_order_details</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/orders/723/actions/send_order_details \
	-u consumer_key:consumer_secret \
	-d '{
	  "email": "somebody@example.com",
	  "force_email_update": true
	}'
```

```javascript
const data = {
	email: "somebody@example.com",
    force_email_update: true
};

WooCommerce.post("orders/723/actions/send_order_details", data)
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
$data = [
    'email' => 'somebody@example.com',
    'force_email_update' => true,
];

print_r($woocommerce->post('orders/723/actions/send_order_details', $data));
?>
```

```python
data = {
    "email": "somebody@example.com",
    "force_email_update": true
}

print(wcapi.post("orders/723/actions/send_order_details", data).json())
```

```ruby
data = {
    "email": "somebody@example.com",
    "force_email_update": true
}

woocommerce.post("orders/723/actions/send_order_details", data).parsed_response
```

> JSON response examples:

```json
{
  "message": "Billing email updated to somebody@example.com. Order details sent to somebody@example.com, via REST API."
}
```

```json
{
	"code": "woocommerce_rest_missing_email",
	"message": "Order does not have an email address.",
	"data": {
		"status": 400
	}
}
```

## Send order notification email to customer ##

This endpoint allows you to trigger an email to a customer about the status of their order. This is similar to the [`send_order_details`](#send-order-details-to-customer) endpoint, but allows you to specify which email template to send, based on which email templates are relevant to the order. For example, an order that is on hold has the `customer_on_hold_order` template available. A completed order that also has a partial refund has both the `customer_completed_order` and `customer_refunded_order` templates available. Specifying the `customer_invoice` template is the same as using the `send_order_details` endpoint.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/actions/send_email</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/orders/723/actions/send_email \
	-u consumer_key:consumer_secret \
	-d '{
	  "template_id": "customer_completed_order",
	  "email": "somebody@example.com",
	  "force_email_update": true
	}'
```

```javascript
const data = {
	template_id: "customer_completed_order",
	email: "somebody@example.com",
    force_email_update: true
};

WooCommerce.post("orders/723/actions/send_email", data)
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
$data = [
    'template_id' => 'customer_completed_order',
    'email' => 'somebody@example.com',
    'force_email_update' => true,
];

print_r($woocommerce->post('orders/723/actions/send_email', $data));
?>
```

```python
data = {
    "template_id": "customer_completed_order",
    "email": "somebody@example.com",
    "force_email_update": true
}

print(wcapi.post("orders/723/actions/send_email", data).json())
```

```ruby
data = {
    "template_id": "customer_completed_order",
    "email": "somebody@example.com",
    "force_email_update": true
}

woocommerce.post("orders/723/actions/send_email", data).parsed_response
```

> JSON response examples:

```json
{
  "message": "Billing email updated to somebody@example.com. Email template &quot;Completed order&quot; sent to somebody@example.com, via REST API."
}
```

```json
{
	"code": "woocommerce_rest_invalid_email_template",
	"message": "customer_completed_order is not a valid template for this order.",
	"data": {
		"status": 400
	}
}
```

## Get available email templates for an order ##

This endpoint allows you to retrieve a list of email templates that are available for the specified order. You can also get this data embedded in the response for the [`orders` endpoint](#list-all-orders).

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/actions/email_templates</h6>
	</div>
</div>

```shell
curl -X GET https://example.com/wp-json/wc/v3/orders/723/actions/email_templates \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("orders/723/actions/email_templates")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
print_r($woocommerce->get('orders/723/actions/email_templates'));
?>
```

```python
print(wcapi.get("orders/723/actions/email_templates").json())
```

```ruby
woocommerce.post("orders/723/actions/email_templates").parsed_response
```

> JSON response examples:

```json
[
	{
		"id": "customer_completed_order",
		"title": "Completed order",
		"description": "Order complete emails are sent to customers when their orders are marked completed and usually indicate that their orders have been shipped."
	},
	{
		"id": "customer_invoice",
		"title": "Order details",
		"description": "Order detail emails can be sent to customers containing their order information and payment links."
	}
]
```
