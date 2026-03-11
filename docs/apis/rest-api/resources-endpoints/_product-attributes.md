# Product attributes #

The product attributes API allows you to create, view, update, and delete individual, or a batch, of product attributes.

## Product attribute properties ##

| Attribute      | Type    | Description                                                                                      |
| -------------- | ------- | ------------------------------------------------------------------------------------------------ |
| `id`           | integer | Unique identifier for the resource. <i class="label label-info">read-only</i>                    |
| `name`         | string  | Attribute name. <i class="label label-info">mandatory</i>                                        |
| `slug`         | string  | An alphanumeric identifier for the resource unique to its type.                                  |
| `type`         | string  | Type of attribute. By default only `select` is supported.                           |
| `order_by`     | string  | Default sort order. Options: `menu_order`, `name`, `name_num` and `id`. Default is `menu_order`. |
| `has_archives` | boolean | Enable/Disable attribute archives. Default is `false`.                                           |

## Create a product attribute ##

This API helps you to create a new product attribute.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/attributes</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/products/attributes \
    -u consumer_key:consumer_secret \
    -H "Content-Type: application/json" \
    -d '{
  "name": "Color",
  "slug": "pa_color",
  "type": "select",
  "order_by": "menu_order",
  "has_archives": true
}'
```

```javascript
const data = {
  name: "Color",
  slug: "pa_color",
  type: "select",
  order_by: "menu_order",
  has_archives: true
};

WooCommerce.post("products/attributes", data)
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
    'name' => 'Color',
    'slug' => 'pa_color',
    'type' => 'select',
    'order_by' => 'menu_order',
    'has_archives' => true
];

print_r($woocommerce->post('products/attributes', $data));
?>
```

```python
data = {
    "name": "Color",
    "slug": "pa_color",
    "type": "select",
    "order_by": "menu_order",
    "has_archives": True
}

print(wcapi.post("products/attributes", data).json())
```

```ruby
data = {
  name: "Color",
  slug: "pa_color",
  type: "select",
  order_by: "menu_order",
  has_archives: true
}

woocommerce.post("products/attributes", data).parsed_response
```

> JSON response example:

```json
{
  "id": 1,
  "name": "Color",
  "slug": "pa_color",
  "type": "select",
  "order_by": "menu_order",
  "has_archives": true,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes/6"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes"
      }
    ]
  }
}
```

## Retrieve a product attribute ##

This API lets you retrieve and view a specific product attribute by ID.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/attributes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/attributes/1 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/attributes/1")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/attributes/1')); ?>
```

```python
print(wcapi.get("products/attributes/1").json())
```

```ruby
woocommerce.get("products/attributes/1").parsed_response
```

> JSON response example:

```json
{
  "id": 1,
  "name": "Color",
  "slug": "pa_color",
  "type": "select",
  "order_by": "menu_order",
  "has_archives": true,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes/6"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes"
      }
    ]
  }
}
```

## List all product attributes ##

This API helps you to view all the product attributes.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/attributes</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/attributes \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/attributes")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/attributes')); ?>
```

```python
print(wcapi.get("products/attributes").json())
```

```ruby
woocommerce.get("products/attributes").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 1,
    "name": "Color",
    "slug": "pa_color",
    "type": "select",
    "order_by": "menu_order",
    "has_archives": true,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/attributes/6"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/attributes"
        }
      ]
    }
  },
  {
    "id": 2,
    "name": "Size",
    "slug": "pa_size",
    "type": "select",
    "order_by": "menu_order",
    "has_archives": false,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/attributes/2"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/attributes"
        }
      ]
    }
  }
]
```

#### Available parameters ####

| Parameter | Type   | Description                                                                                                                  |
| --------- | ------ | ---------------------------------------------------------------------------------------------------------------------------- |
| `context` | string | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`. |

## Update a product attribute ##

This API lets you make changes to a product attribute.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/products/attributes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/products/attributes/1 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "order_by": "name"
}'
```

```javascript
const data = {
  order_by: "name"
};

WooCommerce.put("products/attributes/1", data)
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
    'order_by' => 'name'
];

print_r($woocommerce->put('products/attributes/1', $data));
?>
```

```python
data = {
    "order_by": "name"
}

print(wcapi.put("products/attributes/1", data).json())
```

```ruby
data = {
  order_by: "name"
}

woocommerce.put("products/attributes/1", data).parsed_response
```

> JSON response example:

```json
{
  "id": 1,
  "name": "Color",
  "slug": "pa_color",
  "type": "select",
  "order_by": "name",
  "has_archives": true,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes/6"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes"
      }
    ]
  }
}
```

## Delete a product attribute ##

This API helps you delete a product attribute.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/products/attributes/&lt;id&gt;</h6>
	</div>
</div>

<aside class="warning">
	This also will delete all terms from the selected attribute.
</aside>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/products/attributes/1?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("products/attributes/1", {
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
<?php print_r($woocommerce->delete('products/attributes/1', ['force' => true])); ?>
```

```python
print(wcapi.delete("products/attributes/1", params={"force": True}).json())
```

```ruby
woocommerce.delete("products/attributes/1", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 1,
  "name": "Color",
  "slug": "pa_color",
  "type": "select",
  "order_by": "menu_order",
  "has_archives": true,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes/6"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/attributes"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |

## Batch update product attributes ##

This API helps you to batch create, update and delete multiple product attributes.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/attributes/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com//wp-json/wc/v3/products/attributes/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "name": "Brand"
    },
    {
      "name": "Publisher"
    }
  ],
  "update": [
    {
      "id": 2,
      "order_by": "name"
    }
  ],
  "delete": [
    1
  ]
}'
```

```javascript
const data = {
  create: [
    {
      name: "Brand"
    },
    {
      name: "Publisher"
    }
  ],
  update: [
    {
      id: 2,
      order_by: "name"
    }
  ],
  delete: [
    1
  ]
};

WooCommerce.post("products/attributes/batch", data)
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
            'name' => 'Brand'
        ],
        [
            'name' => 'Publisher'
        ]
    ],
    'update' => [
        [
            'id' => 2,
            'order_by' => 'name'
        ]
    ],
    'delete' => [
        1
    ]
];

print_r($woocommerce->post('products/attributes/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "name": "Brand"
        },
        {
            "name": "Publisher"
        }
    ],
    "update": [
        {
            "id": 2,
            "order_by": "name"
        }
    ],
    "delete": [
        1
    ]
}

print(wcapi.post("products/attributes/batch", data).json())
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
      id: 2,
      order_by: "name"
    }
  ],
  delete: [
    1
  ]
}

woocommerce.post("products/attributes/batch", data).parsed_response
```

> JSON response example:

```json
{
  "create": [
    {
      "id": 7,
      "name": "Brand",
      "slug": "pa_brand",
      "type": "select",
      "order_by": "menu_order",
      "has_archives": false,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes/7"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes"
          }
        ]
      }
    },
    {
      "id": 8,
      "name": "Publisher",
      "slug": "pa_publisher",
      "type": "select",
      "order_by": "menu_order",
      "has_archives": false,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes/8"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes"
          }
        ]
      }
    }
  ],
  "update": [
    {
      "id": 2,
      "name": "Size",
      "slug": "pa_size",
      "type": "select",
      "order_by": "menu_order",
      "has_archives": false,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes/2"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes"
          }
        ]
      }
    }
  ],
  "delete": [
    {
      "id": 1,
      "name": "Color",
      "slug": "pa_color",
      "type": "select",
      "order_by": "menu_order",
      "has_archives": true,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes/6"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/attributes"
          }
        ]
      }
    }
  ]
}
```
