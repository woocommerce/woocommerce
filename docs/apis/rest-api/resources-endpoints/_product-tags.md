# Product tags #

The product tags API allows you to create, view, update, and delete individual, or a batch, of product tags.

## Product tag properties ##

| Attribute     | Type    | Description                                                                              |
| ------------- | ------- | ---------------------------------------------------------------------------------------- |
| `id`          | integer | Unique identifier for the resource. <i class="label label-info">read-only</i>            |
| `name`        | string  | Tag name. <i class="label label-info">mandatory</i>                                      |
| `slug`        | string  | An alphanumeric identifier for the resource unique to its type.                          |
| `description` | string  | HTML description of the resource.                                                        |
| `count`       | integer | Number of published products for the resource. <i class="label label-info">read-only</i> |

## Create a product tag ##

This API helps you to create a new product tag.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/tags</h6>
	</div>
</div>

> Example of how to create a product tag:

```shell
curl -X POST https://example.com/wp-json/wc/v3/products/tags \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "name": "Leather Shoes"
}'
```

```javascript
const data = {
  name: "Leather Shoes"
};

WooCommerce.post("products/tags", data)
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
    'name' => 'Leather Shoes'
];

print_r($woocommerce->post('products/tags', $data));
?>
```

```python
data = {
    "name": "Leather Shoes"
}

print(wcapi.post("products/tags", data).json())
```

```ruby
data = {
  name: "Leather Shoes"
}

woocommerce.post("products/tags", data).parsed_response
```

> JSON response example:

```json
{
  "id": 34,
  "name": "Leather Shoes",
  "slug": "leather-shoes",
  "description": "",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags/34"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags"
      }
    ]
  }
}
```

## Retrieve a product tag ##

This API lets you retrieve a product tag by ID.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/tags/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/tags/34 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/tags/34")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/tags/34')); ?>
```

```python
print(wcapi.get("products/tags/34").json())
```

```ruby
woocommerce.get("products/tags/34").parsed_response
```

> JSON response example:

```json
{
  "id": 34,
  "name": "Leather Shoes",
  "slug": "leather-shoes",
  "description": "",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags/34"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags"
      }
    ]
  }
}
```

## List all product tags ##

This API lets you retrieve all product tag.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/tags</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/tags \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/tags")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/tags')); ?>
```

```python
print(wcapi.get("products/tags").json())
```

```ruby
woocommerce.get("products/tags").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 34,
    "name": "Leather Shoes",
    "slug": "leather-shoes",
    "description": "",
    "count": 0,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/tags/34"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/tags"
        }
      ]
    }
  },
  {
    "id": 35,
    "name": "Oxford Shoes",
    "slug": "oxford-shoes",
    "description": "",
    "count": 0,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/tags/35"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/tags"
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

## Update a product tag ##

This API lets you make changes to a product tag.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/products/tags/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/products/tags/34 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "description": "Genuine leather."
}'
```

```javascript
const data = {
  description: "Genuine leather."
};

WooCommerce.put("products/tags/34", data)
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
    'description': 'Genuine leather.'
];

print_r($woocommerce->put('products/tags/34', $data));
?>
```

```python
data = {
    "description": "Genuine leather."
}

print(wcapi.put("products/tags/34", data).json())
```

```ruby
data = {
  description: "Genuine leather."
}

woocommerce.put("products/tags/34", data).parsed_response
```

> JSON response example:

```json
{
  "id": 34,
  "name": "Leather Shoes",
  "slug": "leather-shoes",
  "description": "Genuine leather.",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags/34"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags"
      }
    ]
  }
}
```

## Delete a product tag ##

This API helps you delete a product tag.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/products/tags/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/products/tags/34?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("products/tags/34", {
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
<?php print_r($woocommerce->delete('products/tags/34', ['force' => true])); ?>
```

```python
print(wcapi.delete("products/tags/34", params={"force": True}).json())
```

```ruby
woocommerce.delete("products/tags/34", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 34,
  "name": "Leather Shoes",
  "slug": "leather-shoes",
  "description": "Genuine leather.",
  "count": 0,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags/34"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/tags"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |

## Batch update product tags ##

This API helps you to batch create, update and delete multiple product tags.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/tags/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com//wp-json/wc/v3/products/tags/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "name": "Round toe"
    },
    {
      "name": "Flat"
    }
  ],
  "update": [
    {
      "id": 34,
      "description": "Genuine leather."
    }
  ],
  "delete": [
    35
  ]
}'
```

```javascript
const data = {
  create: [
    {
      name: "Round toe"
    },
    {
      name: "Flat"
    }
  ],
  update: [
    {
      id: 34,
      description: "Genuine leather."
    }
  ],
  delete: [
    35
  ]
};

WooCommerce.post("products/tags/batch", data)
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
            'name' => 'Round toe'
        ],
        [
            'name' => 'Flat'
        ]
    ],
    'update' => [
        [
            'id' => 34,
            'description' => 'Genuine leather.'
        ]
    ],
    'delete' => [
        35
    ]
];

print_r($woocommerce->post('products/tags/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "name": "Round toe"
        },
        {
            "name": "Flat"
        }
    ],
    "update": [
        {
            "id": 34,
            "description": "Genuine leather."
        }
    ],
    "delete": [
        35
    ]
}

print(wcapi.post("products/tags/batch", data).json())
```

```ruby
data = {
  create: [
    {
      name: "Round toe"
    },
    {
      name: "Flat"
    }
  ],
  update: [
    {
      id: 34,
      description: "Genuine leather."
    }
  ],
  delete: [
    35
  ]
}

woocommerce.post("products/tags/batch", data).parsed_response
```

> JSON response example:

```json
{
  "create": [
    {
      "id": 36,
      "name": "Round toe",
      "slug": "round-toe",
      "description": "",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags/36"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags"
          }
        ]
      }
    },
    {
      "id": 37,
      "name": "Flat",
      "slug": "flat",
      "description": "",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags/37"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags"
          }
        ]
      }
    }
  ],
  "update": [
    {
      "id": 34,
      "name": "Leather Shoes",
      "slug": "leather-shoes",
      "description": "Genuine leather.",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags/34"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags"
          }
        ]
      }
    }
  ],
  "delete": [
    {
      "id": 35,
      "name": "Oxford Shoes",
      "slug": "oxford-shoes",
      "description": "",
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags/35"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/tags"
          }
        ]
      }
    }
  ]
}
```
