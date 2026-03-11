# Product custom fields #

The product custom fields API allows you to view the custom field names that have been recorded.

## Custom fields available parameters ##

| Parameter  | Type     | Description                                                                                                                  |
| ---------- | -------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `context`  | string   | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`. |
| `page`     | integer  | Current page of the collection. Default is `1`.                                                                              |
| `per_page` | integer  | Maximum number of items to be returned in result set. Default is `10`.                                                       |
| `search`   | string   | Limit results to those matching a string.                                                                                    |
| `order`    | string   | Order sort attribute ascending or descending. Options: `asc` and `desc`. Default is `desc`.                                  |

## Retrieve product custom field names ##

This API lets you retrieve filtered custom field names.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/custom-fields/names</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/custom-fields/names \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/custom-fields/names")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/custom-fields/names')); ?>
```

```python
print(wcapi.get("products/custom-fields/names").json())
```

```ruby
woocommerce.get("products/custom-fields/names").parsed_response
```

> JSON response example:

```json
{
	[
		"Custom field 1",
		"Custom field 2",
		"Custom field 3",
		"Custom field 4"
	]
}
```
