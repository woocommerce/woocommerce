# Refunds #

The refunds API is a simple, read-only endpoint that allows you to retrieve a list of refunds outside the context of an existing order. To create, view, and delete individual refunds, check out the [order refunds API](#order-refunds).

## Refund properties ##

All properties are the same as those in the [order refunds endpoint](#order-refund-properties), but with one additional property:

| Attribute   | Type    | Description                                        |
|-------------|---------|----------------------------------------------------|
| `parent_id` | integer | The ID of the order the refund is associated with. |

## Retrieve a list of refunds ##

This API lets you retrieve and view refunds from your store, regardless of which order they are associated with.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/refunds</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/refunds \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("refunds")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('refunds')); ?>
```

```python
print(wcapi.get("refunds").json())
```

```ruby
woocommerce.get("refunds").parsed_response
```

> JSON response example:

```json
[
	{
		"id": 726,
        "parent_id": 124,
		"date_created": "2017-03-21T17:07:11",
		"date_created_gmt": "2017-03-21T20:07:11",
		"amount": "10.00",
		"reason": "",
		"refunded_by": 1,
		"refunded_payment": false,
		"meta_data": [],
		"line_items": [],
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/orders/723/refunds/726"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/orders/723/refunds"
				}
			],
			"up": [
				{
					"href": "https://example.com/wp-json/wc/v3/orders/723"
				}
			]
		}
	},
	{
		"id": 724,
        "parent_id": 63,
		"date_created": "2017-03-21T16:55:37",
		"date_created_gmt": "2017-03-21T19:55:37",
		"amount": "9.00",
		"reason": "",
		"refunded_by": 1,
		"refunded_payment": false,
		"meta_data": [],
		"line_items": [
			{
				"id": 314,
				"name": "Woo Album #2",
				"product_id": 87,
				"variation_id": 0,
				"quantity": -1,
				"tax_class": "",
				"subtotal": "-9.00",
				"subtotal_tax": "0.00",
				"total": "-9.00",
				"total_tax": "0.00",
				"taxes": [],
				"meta_data": [
					{
						"id": 2076,
						"key": "_refunded_item_id",
						"value": "311"
					}
				],
				"sku": "",
				"price": -9
			}
		],
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/orders/723/refunds/724"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/orders/723/refunds"
				}
			],
			"up": [
				{
					"href": "https://example.com/wp-json/wc/v3/orders/723"
				}
			]
		}
	}
]
```

#### Available parameters ####

| Parameter        | Type    | Description                                                                                                                  |
|------------------|---------|------------------------------------------------------------------------------------------------------------------------------|
| `context`        | string  | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`. |
| `page`           | integer | Current page of the collection. Default is `1`.                                                                              |
| `per_page`       | integer | Maximum number of items to be returned in result set. Default is `10`.                                                       |
| `search`         | string  | Limit results to those matching a string.                                                                                    |
| `after`          | string  | Limit response to resources published after a given ISO8601 compliant date.                                                  |
| `before`         | string  | Limit response to resources published before a given ISO8601 compliant date.                                                 |
| `exclude`        | array   | Ensure result set excludes specific IDs.                                                                                     |
| `include`        | array   | Limit result set to specific ids.                                                                                            |
| `offset`         | integer | Offset the result set by a specific number of items.                                                                         |
| `order`          | string  | Order sort attribute ascending or descending. Options: `asc` and `desc`. Default is `desc`.                                  |
| `orderby`        | string  | Sort collection by object attribute. Options: `date`, `modified`, `id`, `include`, `title` and `slug`. Default is `date`.    |
| `parent`         | array   | Limit result set to those of particular parent IDs.                                                                          |
| `parent_exclude` | array   | Limit result set to all items except those of a particular parent ID.                                                        |
| `dp`             | integer | Number of decimal points to use in each resource. Default is `2`.                                                            |
