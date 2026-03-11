# Tax classes #

The tax classes API allows you to create, view, and delete individual tax classes.

## Tax class properties ##

| Attribute |  Type  |                                  Description                                  |
|-----------|--------|-------------------------------------------------------------------------------|
| `slug`    | string | Unique identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string | Tax class name. <i class="label label-info">required</i>                      |

## Create a tax class ##

This API helps you to create a new tax class.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/taxes/classes</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/taxes/classes \
    -u consumer_key:consumer_secret \
    -H "Content-Type: application/json" \
    -d '{
  "name": "Zero Rate"
}'
```

```javascript
const data = {
  name: "Zero Rate"
};

WooCommerce.post("taxes/classes", data)
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
    'name' => 'Zero Rate'
];

print_r($woocommerce->post('taxes/classes', $data));
?>
```

```python
data = {
    "name": "Zero Rate"
}

print(wcapi.post("taxes/classes", data).json())
```

```ruby
data = {
  name: "Zero Rate"
}

woocommerce.post("taxes/classes", data).parsed_response
```

> JSON response example:

```json
{
  "slug": "zero-rate",
  "name": "Zero Rate",
  "_links": {
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes/classes"
      }
    ]
  }
}
```

## List all tax classes ##

This API helps you to view all tax classes.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/taxes/classes</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/taxes/classes \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("taxes/classes")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('taxes/classes')); ?>
```

```python
print(wcapi.get("taxes/classes").json())
```

```ruby
woocommerce.get("taxes/classes").parsed_response
```

> JSON response example:

```json
[
  {
    "slug": "standard",
    "name": "Standard Rate",
    "_links": {
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/classes"
        }
      ]
    }
  },
  {
    "slug": "reduced-rate",
    "name": "Reduced Rate",
    "_links": {
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/classes"
        }
      ]
    }
  },
  {
    "slug": "zero-rate",
    "name": "Zero Rate",
    "_links": {
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/classes"
        }
      ]
    }
  }
]
```

## Delete a tax class ##

This API helps you delete a tax class.

<aside class="warning">
	This also will delete all tax rates from the selected class.
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/taxes/classes/&lt;slug&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/taxes/classes/zero-rate?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("taxes/classes/zero-rate", {
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
<?php print_r($woocommerce->delete('taxes/classes/zero-rate', ['force' => true])); ?>
```

```python
print(wcapi.delete("taxes/classes/zero-rate", params={"force": True}).json())
```

```ruby
woocommerce.delete("taxes/classes/zero-rate", force: true).parsed_response
```

> JSON response example:

```json
{
  "slug": "zero-rate",
  "name": "Zero Rate",
  "_links": {
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes/classes"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, since this resource does not support trashing. |
