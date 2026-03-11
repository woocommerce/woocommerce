# Product shipping classes #

The product shipping class API allows you to create, view, update, and delete individual, or a batch, of shipping classes.

## Product shipping class properties ##

| Attribute     | Type    | Description                                                                              |
| ------------- | ------- | ---------------------------------------------------------------------------------------- |
| `id`          | integer | Unique identifier for the resource. <i class="label label-info">read-only</i>            |
| `name`        | string  | Shipping class name. <i class="label label-info">mandatory</i>                           |
| `slug`        | string  | An alphanumeric identifier for the resource unique to its type.                          |
| `description` | string  | HTML description of the resource.                                                        |
| `count`       | integer | Number of published products for the resource. <i class="label label-info">read-only</i> |

## Create a shipping class ##

This API helps you to create a new product shipping class.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/shipping_classes</h6>
	</div>
</div>

> Example of how to create a product shipping class:

```shell
curl -X POST https://example.com/wp-json/wc/v3/products/shipping_classes \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "name": "Priority"
}'
```

```javascript
const data = {
  name: "Priority"
};

WooCommerce.post("products/shipping_classes", data)
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
    'name' => 'Priority'
];

print_r($woocommerce->post('products/shipping_classes', $data));
?>
```

```python
data = {
    "name": "Priority"
}

print(wcapi.post("products/shipping_classes", data).json())
```

```ruby
data = {
  name: "Priority"
}

woocommerce.post("products/shipping_classes", data).parsed_response
```

> JSON response example:

```json
{
  "id": 32,
  "name": "Priority",
  "slug": "priority",
  "description": "",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/32"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
      }
    ]
  }
}
```

## Retrieve a shipping class ##

This API lets you retrieve a product shipping class by ID.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/shipping_classes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/shipping_classes/32 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/shipping_classes/32")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/shipping_classes/32')); ?>
```

```python
print(wcapi.get("products/shipping_classes/32").json())
```

```ruby
woocommerce.get("products/shipping_classes/32").parsed_response
```

> JSON response example:

```json
{
  "id": 32,
  "name": "Priority",
  "slug": "priority",
  "description": "",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/32"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
      }
    ]
  }
}
```

## List all shipping classes ##

This API lets you retrieve all product shipping classes.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/shipping_classes</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/shipping_classes \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/shipping_classes")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/shipping_classes')); ?>
```

```python
print(wcapi.get("products/shipping_classes").json())
```

```ruby
woocommerce.get("products/shipping_classes").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 33,
    "name": "Express",
    "slug": "express",
    "description": "",
    "count": 0,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/33"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
        }
      ]
    }
  },
  {
    "id": 32,
    "name": "Priority",
    "slug": "priority",
    "description": "",
    "count": 0,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/32"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
        }
      ]
    }
  }
]
```

#### Available parameters ####

| Parameter    | Type    | Description                                                                                                                                  |
| ------------ | ------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `context`    | string  | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`.                 |
| `page`       | integer | Current page of the collection. Default is `1`.                                                                                              |
| `per_page`   | integer | Maximum number of items to be returned in result set. Default is `10`.                                                                       |
| `search`     | string  | Limit results to those matching a string.                                                                                                    |
| `exclude`    | array   | Ensure result set excludes specific ids.                                                                                                     |
| `include`    | array   | Limit result set to specific ids.                                                                                                            |
| `offset`     | integer | Offset the result set by a specific number of items.                                                                                         |
| `order`      | string  | Order sort attribute ascending or descending. Options: `asc` and `desc`. Default is `asc`.                                                   |
| `orderby`    | string  | Sort collection by resource attribute. Options: `id`, `include`, `name`, `slug`, `term_group`, `description` and `count`. Default is `name`. |
| `hide_empty` | boolean | Whether to hide resources not assigned to any products. Default is `false`.                                                                  |
| `product`    | integer | Limit result set to resources assigned to a specific product.                                                                                |
| `slug`       | string  | Limit result set to resources with a specific slug.                                                                                          |

## Update a shipping class ##

This API lets you make changes to a product shipping class.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/products/shipping_classes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/products/shipping_classes/32 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "description": "Priority mail."
}'
```

```javascript
const data = {
  description: "Priority mail."
};

WooCommerce.put("products/shipping_classes/32", data)
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
    'description' => 'Priority mail.'
];

print_r($woocommerce->put('products/shipping_classes/32', $data));
?>
```

```python
data = {
    "description": "Priority mail."
}

print(wcapi.put("products/shipping_classes/32", data).json())
```

```ruby
data = {
  description: "Priority mail."
}

woocommerce.put("products/shipping_classes/32", data).parsed_response
```

> JSON response example:

```json
{
  "id": 32,
  "name": "Priority",
  "slug": "priority",
  "description": "Priority mail.",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/32"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
      }
    ]
  }
}
```

## Delete a shipping class ##

This API helps you delete a product shipping class.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/products/shipping_classes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/products/shipping_classes/32?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("products/shipping_classes/32", {
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
<?php print_r($woocommerce->delete('products/shipping_classes/32', ['force' => true])); ?>
```

```python
print(wcapi.delete("products/shipping_classes/32", params={"force": True}).json())
```

```ruby
woocommerce.delete("products/shipping_classes/32", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 32,
  "name": "Priority",
  "slug": "priority",
  "description": "Priority mail.",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/32"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |

## Batch update shipping classes ##

This API helps you to batch create, update and delete multiple product shipping classes.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/shipping_classes/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com//wp-json/wc/v3/products/shipping_classes/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "name": "Small items"
    },
    {
      "name": "Large items"
    }
  ],
  "update": [
    {
      "id": 33,
      "description": "Express shipping"
    }
  ],
  "delete": [
    32
  ]
}'
```

```javascript
const data = {
  create: [
    {
      name: "Small items"
    },
    {
      name: "Large items"
    }
  ],
  update: [
    {
      id: 33,
      description: "Express shipping"
    }
  ],
  delete: [
    32
  ]
};

WooCommerce.post("products/shipping_classes/batch", data)
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
    'create' => [
        [
            'name' => 'Small items'
        ],
        [
            'name' => 'Large items'
        ]
    ],
    'update' => [
        [
            'id' => 33,
            'description' => 'Express shipping'
        ]
    ],
    'delete' => [
        32
    ]
];

print_r($woocommerce->post('products/shipping_classes/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "name": "Small items"
        },
        {
            "name": "Large items"
        }
    ],
    "update": [
        {
            "id": 33,
            "description": "Express shipping"
        }
    ],
    "delete": [
        32
    ]
}

print(wcapi.post("products/shipping_classes/batch", data).json())
```

```ruby
data = {
  create: [
    {
      name: "Small items"
    },
    {
      name: "Large items"
    }
  ],
  update: [
    {
      id: 33,
      description: "Express shipping"
    }
  ],
  delete: [
    32
  ]
}

woocommerce.post("products/shipping_classes/batch", data).parsed_response
```

> JSON response example:

```json
{
  "create": [
    {
      "id": 34,
      "name": "Small items",
      "slug": "small-items",
      "description": "",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/34"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
          }
        ]
      }
    },
    {
      "id": 35,
      "name": "Large items",
      "slug": "large-items",
      "description": "",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/35"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
          }
        ]
      }
    }
  ],
  "update": [
    {
      "id": 33,
      "name": "Express",
      "slug": "express",
      "description": "Express shipping",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/33"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
          }
        ]
      }
    }
  ],
  "delete": [
    {
      "id": 32,
      "name": "Priority",
      "slug": "priority",
      "description": "",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes/32"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/shipping_classes"
          }
        ]
      }
    }
  ]
}
```
