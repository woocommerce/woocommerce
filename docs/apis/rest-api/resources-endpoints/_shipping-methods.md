# Shipping methods #

The shipping methods API allows you to view individual shipping methods.

## Shipping method properties ##

| Attribute     | Type   | Description                                                            |
| ------------- | ------ | ---------------------------------------------------------------------- |
| `id`          | string | Method ID. <i class="label label-info">read-only</i>                   |
| `title`       | string | Shipping method title. <i class="label label-info">read-only</i>       |
| `description` | string | Shipping method description. <i class="label label-info">read-only</i> |

## Retrieve a shipping method ##

This API lets you retrieve and view a specific shipping method.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/shipping_methods/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/shipping_methods/flat_rate \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("shipping_methods/flat_rate")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('shipping_methods/flat_rate')); ?>
```

```python
print(wcapi.get("shipping_methods/flat_rate").json())
```

```ruby
woocommerce.get("shipping_methods/flat_rate").parsed_response
```

> JSON response example:

```json
{
  "id": "flat_rate",
  "title": "Flat rate",
  "description": "Lets you charge a fixed rate for shipping.",
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping_methods/flat_rate"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping_methods"
      }
    ]
  }
}
```

## List all shipping methods ##

This API helps you to view all the shipping methods.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/shipping_methods</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/shipping_methods \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("shipping_methods")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('shipping_methods')); ?>
```

```python
print(wcapi.get("shipping_methods").json())
```

```ruby
woocommerce.get("shipping_methods").parsed_response
```

> JSON response example:

```json
[
  {
    "id": "flat_rate",
    "title": "Flat rate",
    "description": "Lets you charge a fixed rate for shipping.",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping_methods/flat_rate"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping_methods"
        }
      ]
    }
  },
  {
    "id": "free_shipping",
    "title": "Free shipping",
    "description": "Free shipping is a special method which can be triggered with coupons and minimum spends.",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping_methods/free_shipping"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping_methods"
        }
      ]
    }
  },
  {
    "id": "local_pickup",
    "title": "Local pickup",
    "description": "Allow customers to pick up orders themselves. By default, when using local pickup store base taxes will apply regardless of customer address.",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping_methods/local_pickup"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping_methods"
        }
      ]
    }
  }
]
```
