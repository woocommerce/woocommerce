# Shipping zones #

The shipping zones API allows you to create, view, update, and delete individual shipping zones.

## Shipping zone properties ##

| Attribute | Type    | Description                                                                   |
| --------- | ------- | ----------------------------------------------------------------------------- |
| `id`      | integer | Unique identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string  | Shipping zone name. <i class="label label-info">mandatory</i>                 |
| `order`   | integer | Shipping zone order.                                                          |

## Create a shipping zone ##

This API helps you to create a new shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/shipping/zones</h6>
	</div>
</div>

> JSON response example:

```shell
curl -X POST https://example.com/wp-json/wc/v3/shipping/zones \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "name": "Brazil"
}'
```

```javascript
const data = {
  name: "Brazil"
};

WooCommerce.post("shipping/zones", data)
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
    'name' => 'Brazil'
];

print_r($woocommerce->post('shipping/zones', $data));
?>
```

```python
data = {
    "name": "Brazil"
}

print(wcapi.post("shipping/zones", data).json())
```

```ruby
data = {
  name: "Brazil"
}

woocommerce.post("shipping/zones", data).parsed_response
```

> JSON response example:

```json
{
  "id": 5,
  "name": "Brazil",
  "order": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones"
      }
    ],
    "describedby": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/locations"
      }
    ]
  }
}
```

## Retrieve a shipping zone ##

This API lets you retrieve and view a specific shipping zone by ID.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/shipping/zones/5 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("shipping/zones/5")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('shipping/zones/5')); ?>
```

```python
print(wcapi.get("shipping/zones/5").json())
```

```ruby
woocommerce.get("shipping/zones/5").parsed_response
```

> JSON response example:

```json
{
  "id": 5,
  "name": "Brazil",
  "order": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones"
      }
    ],
    "describedby": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/locations"
      }
    ]
  }
}
```

## List all shipping zones ##

This API helps you to view all the shipping zones.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/shipping/zones</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/shipping/zones \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("shipping/zones")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('shipping/zones')); ?>
```

```python
print(wcapi.get("shipping/zones").json())
```

```ruby
woocommerce.get("shipping/zones").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 0,
    "name": "Rest of the World",
    "order": 0,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/0"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones"
        }
      ],
      "describedby": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/0/locations"
        }
      ]
    }
  },
  {
    "id": 5,
    "name": "Brazil",
    "order": 0,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones"
        }
      ],
      "describedby": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/locations"
        }
      ]
    }
  }
]
```

## Update a shipping zone ##

This API lets you make changes to a shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/shipping/zones/5 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "order": 1
}'
```

```javascript
const data = {
  order: 1
};

WooCommerce.put("shipping/zones/5", data)
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
    'order' => 1
];

print_r($woocommerce->put('shipping/zones/5', $data));
?>
```

```python
data = {
    "order": 1
}

print(wcapi.put("shipping/zones/5", data).json())
```

```ruby
data = {
  order: 1
}

woocommerce.put("shipping/zones/5", data).parsed_response
```

> JSON response example:

```json
{
  "id": 5,
  "name": "Brazil",
  "order": 1,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones"
      }
    ],
    "describedby": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/locations"
      }
    ]
  }
}
```

## Delete a shipping zone ##

This API helps you delete a shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/shipping/zones/5?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("shipping/zones/5", {
  force: true
})
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->delete('shipping/zones/5', ['force' => true])); ?>
```

```python
print(wcapi.delete("shipping/zones/5", params={"force": True}).json())
```

```ruby
woocommerce.delete("shipping/zones/5", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 5,
  "name": "Brazil",
  "order": 1,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones"
      }
    ],
    "describedby": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/locations"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |
