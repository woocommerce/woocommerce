# Product categories #

The product categories API allows you to create, view, update, and delete individual, or a batch, of categories.

## Product category properties ##

| Attribute     | Type    | Description                                                                                                      |
|---------------|---------|------------------------------------------------------------------------------------------------------------------|
| `id`          | integer | Unique identifier for the resource. <i class="label label-info">read-only</i>                                    |
| `name`        | string  | Category name. <i class="label label-info">mandatory</i>                                                         |
| `slug`        | string  | An alphanumeric identifier for the resource unique to its type.                                                  |
| `parent`      | integer | The ID for the parent of the resource.                                                                           |
| `description` | string  | HTML description of the resource.                                                                                |
| `display`     | string  | Category archive display type. Options: `default`, `products`, `subcategories` and `both`. Default is `default`. |
| `image`       | object  | Image data. See [Product category - Image properties](#product-category-image-properties)                        |
| `menu_order`  | integer | Menu order, used to custom sort the resource.                                                                    |
| `count`       | integer | Number of published products for the resource. <i class="label label-info">read-only</i>                         |

### Product category - Image properties ###

| Attribute           | Type      | Description                                                                                             |
|---------------------|-----------|---------------------------------------------------------------------------------------------------------|
| `id`                | integer   | Image ID.                                                                                               |
| `date_created`      | date-time | The date the image was created, in the site's timezone. <i class="label label-info">read-only</i>       |
| `date_created_gmt`  | date-time | The date the image was created, as GMT <i class="label label-info">read-only</i>                        |
| `date_modified`     | date-time | The date the image was last modified, in the site's timezone. <i class="label label-info">read-only</i> |
| `date_modified_gmt` | date-time | The date the image was last modified, as GMT. <i class="label label-info">read-only</i>                 |
| `src`               | string    | Image URL.                                                                                              |
| `name`              | string    | Image name.                                                                                             |
| `alt`               | string    | Image alternative text.                                                                                 |

## Create a product category ##

This API helps you to create a new product category.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/categories</h6>
	</div>
</div>

> Example of how to create a product category:

```shell
curl -X POST https://example.com/wp-json/wc/v3/products/categories \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "name": "Clothing",
  "image": {
    "src": "http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg"
  }
}'
```

```javascript
const data = {
  name: "Clothing",
  image: {
    src: "http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg"
  }
};

WooCommerce.post("products/categories", data)
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
    "name' => 'Clothing',
    'image' => [
        'src' => 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg'
    ]
];

print_r($woocommerce->post('products/categories', $data));
?>
```

```python
data = {
    "name": "Clothing",
    "image": {
        "src": "http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg"
    }
}

print(wcapi.post("products/categories", data).json())
```

```ruby
data = {
  name: "Clothing",
  image: {
    src: "http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg"
  }
}

woocommerce.post("products/categories", data).parsed_response
```

> JSON response example:

```json
{
  "id": 9,
  "name": "Clothing",
  "slug": "clothing",
  "parent": 0,
  "description": "",
  "display": "default",
  "image": {
    "id": 730,
    "date_created": "2017-03-23T00:01:07",
    "date_created_gmt": "2017-03-23T03:01:07",
    "date_modified": "2017-03-23T00:01:07",
    "date_modified_gmt": "2017-03-23T03:01:07",
    "src": "https://example.com/wp-content/uploads/2017/03/T_2_front.jpg",
    "name": "",
    "alt": ""
  },
  "menu_order": 0,
  "count": 36,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories/9"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories"
      }
    ]
  }
}
```

## Retrieve a product category ##

This API lets you retrieve a product category by ID.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/categories/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/categories/9 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/categories/9")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/categories/9')); ?>
```

```python
print(wcapi.get("products/categories/9").json())
```

```ruby
woocommerce.get("products/categories/9").parsed_response
```

> JSON response example:

```json
{
  "id": 9,
  "name": "Clothing",
  "slug": "clothing",
  "parent": 0,
  "description": "",
  "display": "default",
  "image": {
    "id": 730,
    "date_created": "2017-03-23T00:01:07",
    "date_created_gmt": "2017-03-23T03:01:07",
    "date_modified": "2017-03-23T00:01:07",
    "date_modified_gmt": "2017-03-23T03:01:07",
    "src": "https://example.com/wp-content/uploads/2017/03/T_2_front.jpg",
    "name": "",
    "alt": ""
  },
  "menu_order": 0,
  "count": 36,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories/9"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories"
      }
    ]
  }
}
```

## List all product categories ##

This API lets you retrieve all product categories.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/categories</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/categories \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/categories")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/categories')); ?>
```

```python
print(wcapi.get("products/categories").json())
```

```ruby
woocommerce.get("products/categories").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 15,
    "name": "Albums",
    "slug": "albums",
    "parent": 11,
    "description": "",
    "display": "default",
    "image": [],
    "menu_order": 0,
    "count": 4,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/15"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/11"
        }
      ]
    }
  },
  {
    "id": 9,
    "name": "Clothing",
    "slug": "clothing",
    "parent": 0,
    "description": "",
    "display": "default",
    "image": {
      "id": 730,
      "date_created": "2017-03-23T00:01:07",
      "date_created_gmt": "2017-03-23T03:01:07",
      "date_modified": "2017-03-23T00:01:07",
      "date_modified_gmt": "2017-03-23T03:01:07",
      "src": "https://example.com/wp-content/uploads/2017/03/T_2_front.jpg",
      "name": "",
      "alt": ""
    },
    "menu_order": 0,
    "count": 36,
    "_links": {
      "self": [
        {
          "href": "https://example/wp-json/wc/v3/products/categories/9"
        }
      ],
      "collection": [
        {
          "href": "https://example/wp-json/wc/v3/products/categories"
        }
      ]
    }
  },
  {
    "id": 10,
    "name": "Hoodies",
    "slug": "hoodies",
    "parent": 9,
    "description": "",
    "display": "default",
    "image": [],
    "menu_order": 0,
    "count": 6,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/10"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/9"
        }
      ]
    }
  },
  {
    "id": 11,
    "name": "Music",
    "slug": "music",
    "parent": 0,
    "description": "",
    "display": "default",
    "image": [],
    "menu_order": 0,
    "count": 7,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/11"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories"
        }
      ]
    }
  },
  {
    "id": 12,
    "name": "Posters",
    "slug": "posters",
    "parent": 0,
    "description": "",
    "display": "default",
    "image": [],
    "menu_order": 0,
    "count": 5,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/12"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories"
        }
      ]
    }
  },
  {
    "id": 13,
    "name": "Singles",
    "slug": "singles",
    "parent": 11,
    "description": "",
    "display": "default",
    "image": [],
    "menu_order": 0,
    "count": 3,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/13"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/11"
        }
      ]
    }
  },
  {
    "id": 14,
    "name": "T-shirts",
    "slug": "t-shirts",
    "parent": 9,
    "description": "",
    "display": "default",
    "image": [],
    "menu_order": 0,
    "count": 6,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/14"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/categories/9"
        }
      ]
    }
  }
]
```

#### Available parameters ####

| Parameter    | Type    | Description                                                                                                                                  |
|--------------|---------|----------------------------------------------------------------------------------------------------------------------------------------------|
| `context`    | string  | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`.                 |
| `page`       | integer | Current page of the collection. Default is `1`.                                                                                              |
| `per_page`   | integer | Maximum number of items to be returned in result set. Default is `10`.                                                                       |
| `search`     | string  | Limit results to those matching a string.                                                                                                    |
| `exclude`    | array   | Ensure result set excludes specific ids.                                                                                                     |
| `include`    | array   | Limit result set to specific ids.                                                                                                            |
| `order`      | string  | Order sort attribute ascending or descending. Options: `asc` and `desc`. Default is `asc`.                                                   |
| `orderby`    | string  | Sort collection by resource attribute. Options: `id`, `include`, `name`, `slug`, `term_group`, `description` and `count`. Default is `name`. |
| `hide_empty` | boolean | Whether to hide resources not assigned to any products. Default is `false`.                                                                  |
| `parent`     | integer | Limit result set to resources assigned to a specific parent.                                                                                 |
| `product`    | integer | Limit result set to resources assigned to a specific product.                                                                                |
| `slug`       | string  | Limit result set to resources with a specific slug.                                                                                          |

## Update a product category ##

This API lets you make changes to a product category.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/products/categories/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/products/categories/9 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "description": "All kinds of clothes."
}'
```

```javascript
const data = {
  description: "All kinds of clothes."
};

WooCommerce.put("products/categories/9", data)
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
    'description' => 'All kinds of clothes.'
];

print_r($woocommerce->put('products/categories/9', $data));
?>
```

```python
data = {
    "description": "All kinds of clothes."
}

print(wcapi.put("products/categories/9", data).json())
```

```ruby
data = {
  description: "All kinds of clothes."
}

woocommerce.put("products/categories/9", data).parsed_response
```

> JSON response example:

```json
{
  "id": 9,
  "name": "Clothing",
  "slug": "clothing",
  "parent": 0,
  "description": "All kinds of clothes.",
  "display": "default",
  "image": {
    "id": 730,
    "date_created": "2017-03-23T00:01:07",
    "date_created_gmt": "2017-03-23T03:01:07",
    "date_modified": "2017-03-23T00:01:07",
    "date_modified_gmt": "2017-03-23T03:01:07",
    "src": "https://example.com/wp-content/uploads/2017/03/T_2_front.jpg",
    "name": "",
    "alt": ""
  },
  "menu_order": 0,
  "count": 36,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories/9"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories"
      }
    ]
  }
}
```

## Delete a product category ##

This API helps you delete a product category.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/products/categories/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/products/categories/9?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("products/categories/9", {
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
<?php print_r($woocommerce->delete('products/categories/9', ['force' => true])); ?>
```

```python
print(wcapi.delete("products/categories/9", params={"force": True}).json())
```

```ruby
woocommerce.delete("products/categories/9", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 9,
  "name": "Clothing",
  "slug": "clothing",
  "parent": 0,
  "description": "All kinds of clothes.",
  "display": "default",
  "image": {
    "id": 730,
    "date_created": "2017-03-23T00:01:07",
    "date_created_gmt": "2017-03-23T03:01:07",
    "date_modified": "2017-03-23T00:01:07",
    "date_modified_gmt": "2017-03-23T03:01:07",
    "src": "https://example.com/wp-content/uploads/2017/03/T_2_front.jpg",
    "name": "",
    "alt": ""
  },
  "menu_order": 0,
  "count": 36,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories/9"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/products/categories"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter | Type   | Description                                                   |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |

## Batch update product categories ##

This API helps you to batch create, update and delete multiple product categories.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/categories/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com//wp-json/wc/v3/products/categories/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "name": "Albums"
    },
    {
      "name": "Clothing"
    }
  ],
  "update": [
    {
      "id": 10,
      "description": "Nice hoodies"
    }
  ],
  "delete": [
    11,
    12
  ]
}'
```

```javascript
const data = {
  create: [
    {
      name: "Albums"
    },
    {
      name: "Clothing"
    }
  ],
  update: [
    {
      id: 10,
      description: "Nice hoodies"
    }
  ],
  delete: [
    11,
    12
  ]
};

WooCommerce.post("products/categories/batch", data)
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
            'name' => 'Albums'
        ],
        [
            'name' => 'Clothing'
        ]
    ],
    'update' => [
        [
            'id' => 10,
            'description' => 'Nice hoodies'
        ]
    ],
    'delete' => [
        11,
        12
    ]
];

print_r($woocommerce->post('products/categories/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "name": "Albums"
        },
        {
            "name": "Clothing"
        }
    ],
    "update": [
        {
            "id": 10,
            "description": "Nice hoodies"
        }
    ],
    "delete": [
        11,
        12
    ]
}

print(wcapi.post("products/categories/batch", data).json())
```

```ruby
data = {
  create: [
    {
      name: "Albums"
    },
    {
      name: "Clothing"
    }
  ],
  update: [
    {
      id: 10,
      description: "Nice hoodies"
    }
  ],
  delete: [
    11,
    12
  ]
}

woocommerce.post("products/categories/batch", data).parsed_response
```

> JSON response example:

```json
{
  "create": [
    {
      "id": 15,
      "name": "Albums",
      "slug": "albums",
      "parent": 11,
      "description": "",
      "display": "default",
      "image": [],
      "menu_order": 0,
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/15"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories"
          }
        ],
        "up": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/11"
          }
        ]
      }
    },
    {
      "id": 9,
      "name": "Clothing",
      "slug": "clothing",
      "parent": 0,
      "description": "",
      "display": "default",
      "image": [],
      "menu_order": 0,
      "count": 0,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/9"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories"
          }
        ]
      }
    }
  ],
  "update": [
    {
      "id": 10,
      "name": "Hoodies",
      "slug": "hoodies",
      "parent": 9,
      "description": "Nice hoodies",
      "display": "default",
      "image": [],
      "menu_order": 0,
      "count": 6,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/10"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories"
          }
        ],
        "up": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/9"
          }
        ]
      }
    }
  ],
  "delete": [
    {
      "id": 11,
      "name": "Music",
      "slug": "music",
      "parent": 0,
      "description": "",
      "display": "default",
      "image": [],
      "menu_order": 0,
      "count": 7,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/11"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories"
          }
        ]
      }
    },
    {
      "id": 12,
      "name": "Posters",
      "slug": "posters",
      "parent": 0,
      "description": "",
      "display": "default",
      "image": [],
      "menu_order": 0,
      "count": 5,
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories/12"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/products/categories"
          }
        ]
      }
    }
  ]
}
```
