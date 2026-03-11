# Product reviews #

The product reviews API allows you to create, view, update, and delete individual, or a batch, of product reviews.

## Product review properties ##

| Attribute          | Type    | Description                                                                                                         |
|--------------------|---------|---------------------------------------------------------------------------------------------------------------------|
| `id`               | integer | Unique identifier for the resource. <i class="label label-info">read-only</i>                                       |
| `date_created`     | string  | The date the review was created, in the site's timezone. <i class="label label-info">read-only</i>                  |
| `date_created_gmt` | string  | The date the review was created, as GMT. <i class="label label-info">read-only</i>                                  |
| `product_id`       | integer | Unique identifier for the product that the review belongs to.                                                       |
| `status`           | string  | Status of the review. Options: `approved`, `hold`, `spam`, `unspam`, `trash` and `untrash`. Defaults to `approved`. |
| `reviewer`         | string  | Reviewer name.                                                                                                      |
| `reviewer_email`   | string  | Reviewer email.                                                                                                     |
| `review`           | string  | The content of the review.                                                                                          |
| `rating`           | integer | Review rating (0 to 5).                                                                                             |
| `verified`         | boolean | Shows if the reviewer bought the product or not.                                                                    |

## Create a product review ##

This API helps you to create a new product review.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/reviews</h6>
	</div>
</div>

> Example of how to create a product review:

```shell
curl -X POST https://example.com/wp-json/wc/v3/products/reviews \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "product_id": 22,
  "review": "Nice album!",
  "reviewer": "John Doe",
  "reviewer_email": "john.doe@example.com",
  "rating": 5
}'
```

```javascript
const data = {
  product_id: 22,
  review: "Nice album!",
  reviewer: "John Doe",
  reviewer_email: "john.doe@example.com",
  rating: 5
};

WooCommerce.post("products/reviews", data)
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
    'product_id' => 22,
    'review' => 'Nice album!',
    'reviewer' => 'John Doe',
    'reviewer_email' => 'john.doe@example.com',
    'rating' => 5
];

print_r($woocommerce->post('products/reviews', $data));
?>
```

```python
data = {
    "product_id": 22,
    "review": "Nice album!",
    "reviewer": "John Doe",
    "reviewer_email": "john.doe@example.com",
    "rating": 5,
}

print(wcapi.post("products/reviews", data).json())
```

```ruby
data = {
  product_id: 22,
  review: "Nice album!",
  reviewer: "John Doe",
  reviewer_email: "john.doe@example.com",
  rating: 5
}

woocommerce.post("products/reviews", data).parsed_response
```

> JSON response example:

```json
{
	"id": 22,
	"date_created": "2018-10-18T17:59:17",
	"date_created_gmt": "2018-10-18T20:59:17",
	"product_id": 22,
	"status": "approved",
	"reviewer": "John Doe",
	"reviewer_email": "john.doe@example.com",
	"review": "Nice album!",
	"rating": 5,
	"verified": false,
	"reviewer_avatar_urls": {
		"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
		"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
		"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
	},
	"_links": {
		"self": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/reviews/22"
			}
		],
		"collection": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/reviews"
			}
		],
		"up": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/22"
			}
		]
	}
}
```

## Retrieve a product review ##

This API lets you retrieve a product review by ID.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/reviews/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/reviews/22 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/reviews/22")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/reviews/22')); ?>
```

```python
print(wcapi.get("products/reviews/22").json())
```

```ruby
woocommerce.get("products/reviews/22").parsed_response
```

> JSON response example:

```json
{
	"id": 22,
	"date_created": "2018-10-18T17:59:17",
	"date_created_gmt": "2018-10-18T20:59:17",
	"product_id": 22,
	"status": "approved",
	"reviewer": "John Doe",
	"reviewer_email": "john.doe@example.com",
	"review": "Nice album!",
	"rating": 5,
	"verified": false,
	"reviewer_avatar_urls": {
		"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
		"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
		"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
	},
	"_links": {
		"self": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/reviews/22"
			}
		],
		"collection": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/reviews"
			}
		],
		"up": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/22"
			}
		]
	}
}
```

## List all product reviews ##

This API lets you retrieve all product review.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/products/reviews</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/reviews \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/reviews")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/reviews')); ?>
```

```python
print(wcapi.get("products/reviews").json())
```

```ruby
woocommerce.get("products/reviews").parsed_response
```

> JSON response example:

```json
[
	{
		"id": 22,
		"date_created": "2018-10-18T17:59:17",
		"date_created_gmt": "2018-10-18T20:59:17",
		"product_id": 22,
		"status": "approved",
		"reviewer": "John Doe",
		"reviewer_email": "john.doe@example.com",
		"review": "<p>Nice album!</p>\n",
		"rating": 5,
		"verified": false,
		"reviewer_avatar_urls": {
			"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
			"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
			"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
		},
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/products/reviews/22"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/products/reviews"
				}
			],
			"up": [
				{
					"href": "https://example.com/wp-json/wc/v3/products/22"
				}
			]
		}
	},
	{
		"id": 20,
		"date_created": "2018-09-08T21:47:19",
		"date_created_gmt": "2018-09-09T00:47:19",
		"product_id": 31,
		"status": "approved",
		"reviewer": "Claudio Sanches",
		"reviewer_email": "john.doe@example.com",
		"review": "<p>Now works just fine.</p>\n",
		"rating": 1,
		"verified": true,
		"reviewer_avatar_urls": {
			"24": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=24&d=mm&r=g",
			"48": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=48&d=mm&r=g",
			"96": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=96&d=mm&r=g"
		},
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/products/reviews/20"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/products/reviews"
				}
			],
			"up": [
				{
					"href": "https://example.com/wp-json/wc/v3/products/31"
				}
			],
			"reviewer": [
				{
					"embeddable": true,
					"href": "https://example.com/wp-json/wp/v2/users/1"
				}
			]
		}
	}
]
```

#### Available parameters ####

| Parameter          | Type    | Description                                                                                                                            |
|--------------------|---------|----------------------------------------------------------------------------------------------------------------------------------------|
| `context`          | string  | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`.           |
| `page`             | integer | Current page of the collection. Default is `1`.                                                                                        |
| `per_page`         | integer | Maximum number of items to be returned in result set. Default is `10`.                                                                 |
| `search`           | string  | Limit results to those matching a string.                                                                                              |
| `after`            | string  | Limit response to reviews published after a given ISO8601 compliant date.                                                              |
| `before`           | string  | Limit response to reviews published before a given ISO8601 compliant date.                                                             |
| `dates_are_gmt`    | boolean | Interpret `after` and `before` as UTC dates when `true`.                                                                               |
| `exclude`          | array   | Ensure result set excludes specific ids.                                                                                               |
| `include`          | array   | Limit result set to specific ids.                                                                                                      |
| `offset`           | integer | Offset the result set by a specific number of items.                                                                                   |
| `order`            | string  | Order sort attribute ascending or descending. Options: `asc` and `desc`. Default is `desc`.                                            |
| `orderby`          | string  | Sort collection by resource attribute. Options: `date`, `date_gmt`, `id`, `slug`, `include` and `product`. Default is `date_gmt`.      |
| `reviewer`         | array   | Limit result set to reviews assigned to specific user IDs.                                                                             |
| `reviewer_exclude` | array   | Ensure result set excludes reviews assigned to specific user IDs.                                                                      |
| `reviewer_email`   | array   | Limit result set to that from a specific author email.                                                                                 |
| `product`          | array   | Limit result set to reviews assigned to specific product IDs.                                                                          |
| `status`           | string  | Limit result set to reviews assigned a specific status. Options: `all`, `hold`, `approved`, `spam` and `trash`. Default is `approved`. |

## Update a product review ##

This API lets you make changes to a product review.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/products/reviews/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/products/reviews/20 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "rating": 5
}'
```

```javascript
const data = {
  rating: 5
};

WooCommerce.put("products/reviews/20", data)
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
    'rating': 5
];

print_r($woocommerce->put('products/reviews/20', $data));
?>
```

```python
data = {
    "rating": 5
}

print(wcapi.put("products/reviews/20", data).json())
```

```ruby
data = {
  rating: 5
}

woocommerce.put("products/reviews/20", data).parsed_response
```

> JSON response example:

```json
{
	"id": 20,
	"date_created": "2018-09-08T21:47:19",
	"date_created_gmt": "2018-09-09T00:47:19",
	"product_id": 31,
	"status": "approved",
	"reviewer": "Claudio Sanches",
	"reviewer_email": "john.doe@example.com",
	"review": "Now works just fine.",
	"rating": 5,
	"verified": true,
	"reviewer_avatar_urls": {
		"24": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=24&d=mm&r=g",
		"48": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=48&d=mm&r=g",
		"96": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=96&d=mm&r=g"
	},
	"_links": {
		"self": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/reviews/20"
			}
		],
		"collection": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/reviews"
			}
		],
		"up": [
			{
				"href": "https://example.com/wp-json/wc/v3/products/31"
			}
		],
		"reviewer": [
			{
				"embeddable": true,
				"href": "https://example.com/wp-json/wp/v2/users/1"
			}
		]
	}
}
```

## Delete a product review ##

This API helps you delete a product review.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/products/reviews/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/products/reviews/34?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("products/reviews/20", {
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
<?php print_r($woocommerce->delete('products/reviews/20', ['force' => true])); ?>
```

```python
print(wcapi.delete("products/reviews/20", params={"force": True}).json())
```

```ruby
woocommerce.delete("products/reviews/20", force: true).parsed_response
```

> JSON response example:

```json
{
	"deleted": true,
	"previous": {
		"id": 20,
		"date_created": "2018-09-08T21:47:19",
		"date_created_gmt": "2018-09-09T00:47:19",
		"product_id": 31,
		"status": "trash",
		"reviewer": "Claudio Sanches",
		"reviewer_email": "john.doe@example.com",
		"review": "Now works just fine.",
		"rating": 5,
		"verified": true,
		"reviewer_avatar_urls": {
			"24": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=24&d=mm&r=g",
			"48": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=48&d=mm&r=g",
			"96": "https://secure.gravatar.com/avatar/908480753c07509e76322dc17d305c8b?s=96&d=mm&r=g"
		}
	}
}
```

#### Available parameters ####

| Parameter | Type   | Description                                                   |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |

## Batch update product reviews ##

This API helps you to batch create, update and delete multiple product reviews.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/products/reviews/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com//wp-json/wc/v3/products/reviews/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "product_id": 22,
      "review": "Looks fine",
      "reviewer": "John Doe",
      "reviewer_email": "john.doe@example.com",
      "rating": 4
    },
    {
      "product_id": 22,
      "review": "I love this album",
      "reviewer": "John Doe",
      "reviewer_email": "john.doe@example.com",
      "rating": 5
    }
  ],
  "update": [
    {
      "id": 7,
      "reviewer": "John Doe",
      "reviewer_email": "john.doe@example.com"
    }
  ],
  "delete": [
    22
  ]
}'
```

```javascript
const data = {
  create: [
    {
      product_id: 22,
      review: "Looks fine",
      reviewer: "John Doe",
      reviewer_email: "john.doe@example.com",
      rating: 4
    },
    {
      product_id: 22,
      review: "I love this album",
      reviewer: "John Doe",
      reviewer_email: "john.doe@example.com",
      rating: 5
    }
  ],
  update: [
    {
      id: 7,
      reviewer: "John Doe",
      reviewer_email: "john.doe@example.com"
    }
  ],
  delete: [
    22
  ]
};

WooCommerce.post("products/reviews/batch", data)
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
            'product_id' => 22,
            'review' => 'Looks fine',
            'reviewer' => 'John Doe',
            'reviewer_email' => 'john.doe@example.com',
            'rating' => 4
        ],
        [
            'product_id' => 22,
            'review' => 'I love this album',
            'reviewer' => 'John Doe',
            'reviewer_email' => 'john.doe@example.com',
            'rating' => 5
        ]
    ],
    'update' => [
        [
            'id' => 7,
            'reviewer' => 'John Doe',
            'reviewer_email' => 'john.doe@example.com',
        ]
    ],
    'delete' => [
        22
    ]
];

print_r($woocommerce->post('products/reviews/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "product_id": 22,
            "review": "Looks fine",
            "reviewer": "John Doe",
            "reviewer_email": "john.doe@example.com",
            "rating": 4
        },
        {
            "product_id": 22,
            "review": "I love this album",
            "reviewer": "John Doe",
            "reviewer_email": "john.doe@example.com",
            "rating": 5
        }
    ],
    "update": [
        {
            "id": 7,
            "reviewer": "John Doe",
            "reviewer_email": "john.doe@example.com"
        }
    ],
    "delete": [
        22
    ]
}

print(wcapi.post("products/reviews/batch", data).json())
```

```ruby
data = {
  create: [
    {
      product_id: "22",
      review: "Looks fine",
      reviewer: "John Doe",
      reviewer_email: "john.doe@example.com",
      rating: "4"
    },
    {
      product_id: "22",
      review: "I love this album",
      reviewer: "John Doe",
      reviewer_email: "john.doe@example.com",
      rating: "5"
    }
  ],
  update: [
    {
      id: 7,
      reviewer: "John Doe"
      reviewer_email: "john.doe@example.com"
    }
  ],
  delete: [
    22
  ]
}

woocommerce.post("products/reviews/batch", data).parsed_response
```

> JSON response example:

```json
{
	"create": [
		{
			"id": 25,
			"date_created": "2018-10-18T18:37:35",
			"date_created_gmt": "2018-10-18T21:37:35",
			"product_id": 22,
			"status": "approved",
			"reviewer": "John Doe",
			"reviewer_email": "john.doe@example.com",
			"review": "Looks fine",
			"rating": 4,
			"verified": false,
			"reviewer_avatar_urls": {
				"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
				"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
				"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
			},
			"_links": {
				"self": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/reviews/25"
					}
				],
				"collection": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/reviews"
					}
				],
				"up": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/22"
					}
				]
			}
		},
		{
			"id": 26,
			"date_created": "2018-10-18T18:37:35",
			"date_created_gmt": "2018-10-18T21:37:35",
			"product_id": 22,
			"status": "approved",
			"reviewer": "John Doe",
			"reviewer_email": "john.doe@example.com",
			"review": "I love this album",
			"rating": 5,
			"verified": false,
			"reviewer_avatar_urls": {
				"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
				"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
				"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
			},
			"_links": {
				"self": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/reviews/26"
					}
				],
				"collection": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/reviews"
					}
				],
				"up": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/22"
					}
				]
			}
		}
	],
	"update": [
		{
			"id": 7,
			"date_created": "2018-07-26T19:29:21",
			"date_created_gmt": "2018-07-26T22:29:21",
			"product_id": 66,
			"status": "approved",
			"reviewer": "John Doe",
			"reviewer_email": "john.doe@example.com",
			"review": "Not so bad :(",
			"rating": 3,
			"verified": false,
			"reviewer_avatar_urls": {
				"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
				"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
				"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
			},
			"_links": {
				"self": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/reviews/7"
					}
				],
				"collection": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/reviews"
					}
				],
				"up": [
					{
						"href": "https://example.com/wp-json/wc/v3/products/66"
					}
				]
			}
		}
	],
	"delete": [
		{
			"deleted": true,
			"previous": {
				"id": 22,
				"date_created": "2018-10-18T17:59:17",
				"date_created_gmt": "2018-10-18T20:59:17",
				"product_id": 22,
				"status": "approved",
				"reviewer": "John Doe",
				"reviewer_email": "john.doe@example.com",
				"review": "Nice album!",
				"rating": 5,
				"verified": false,
				"reviewer_avatar_urls": {
					"24": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=24&d=mm&r=g",
					"48": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=48&d=mm&r=g",
					"96": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96&d=mm&r=g"
				}
			}
		}
	]
}
```
