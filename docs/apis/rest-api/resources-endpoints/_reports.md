# Reports #

The reports API allows you to view all types of reports available.

## List all reports ##

This API lets you retrieve and view a simple list of available reports.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('reports')); ?>
```

```python
print(wcapi.get("reports").json())
```

```ruby
woocommerce.get("reports").parsed_response
```

> JSON response example:

```json
[
	{
		"slug": "sales",
		"description": "List of sales reports.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/sales"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "top_sellers",
		"description": "List of top sellers products.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/top_sellers"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "orders/totals",
		"description": "Orders totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/orders/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "products/totals",
		"description": "Products totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/products/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "customers/totals",
		"description": "Customers totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/customers/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "coupons/totals",
		"description": "Coupons totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/coupons/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "reviews/totals",
		"description": "Reviews totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/reviews/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "categories/totals",
		"description": "Categories totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/categories/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "tags/totals",
		"description": "Tags totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/tags/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	},
	{
		"slug": "attributes/totals",
		"description": "Attributes totals.",
		"_links": {
			"self": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports/attributes/totals"
				}
			],
			"collection": [
				{
					"href": "https://example.com/wp-json/wc/v3/reports"
				}
			]
		}
	}
]
```

## Retrieve sales report ##

This API lets you retrieve and view a sales report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/sales</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/sales?date_min=2016-05-03&date_max=2016-05-04 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/sales", {
  date_min: "2016-05-03",
  date_max: "2016-05-04"
})
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
$query = [
    'date_min' => '2016-05-03', 
    'date_max' => '2016-05-04'
];

print_r($woocommerce->get('reports/sales', $query));
?>
```

```python
print(wcapi.get("reports/sales?date_min=2016-05-03&date_max=2016-05-04").json())
```

```ruby
query = {
  date_min: "2016-05-03",
  date_max: "2016-05-04"
}

woocommerce.get("reports/sales", query).parsed_response
```

> JSON response example:

```json
[
  {
    "total_sales": "14.00",
    "net_sales": "4.00",
    "average_sales": "2.00",
    "total_orders": 3,
    "total_items": 6,
    "total_tax": "0.00",
    "total_shipping": "10.00",
    "total_refunds": 0,
    "total_discount": "0.00",
    "totals_grouped_by": "day",
    "totals": {
      "2016-05-03": {
        "sales": "14.00",
        "orders": 3,
        "items": 6,
        "tax": "0.00",
        "shipping": "10.00",
        "discount": "0.00",
        "customers": 0
      },
      "2016-05-04": {
        "sales": "0.00",
        "orders": 0,
        "items": 0,
        "tax": "0.00",
        "shipping": "0.00",
        "discount": "0.00",
        "customers": 0
      }
    },
    "total_customers": 0,
    "_links": {
      "about": [
        {
          "href": "https://example.com/wp-json/wc/v3/reports"
        }
      ]
    }
  }
]
```

#### Sales report properties ####

| Attribute           | Type    | Description                                                           |
|---------------------|---------|-----------------------------------------------------------------------|
| `total_sales`       | string  | Gross sales in the period. <i class="label label-info">read-only</i>  |
| `net_sales`         | string  | Net sales in the period. <i class="label label-info">read-only</i>    |
| `average_sales`     | string  | Average net daily sales. <i class="label label-info">read-only</i>    |
| `total_orders`      | integer | Total of orders placed. <i class="label label-info">read-only</i>     |
| `total_items`       | integer | Total of items purchased. <i class="label label-info">read-only</i>   |
| `total_tax`         | string  | Total charged for taxes. <i class="label label-info">read-only</i>    |
| `total_shipping`    | string  | Total charged for shipping. <i class="label label-info">read-only</i> |
| `total_refunds`     | integer | Total of refunded orders. <i class="label label-info">read-only</i>   |
| `total_discount`    | integer | Total of coupons used. <i class="label label-info">read-only</i>      |
| `totals_grouped_by` | string  | Group type. <i class="label label-info">read-only</i>                 |
| `totals`            | array   | Totals. <i class="label label-info">read-only</i>                     |

#### Available parameters ####

| Parameter  | Type   | Description                                                                                                       |
|------------|--------|-------------------------------------------------------------------------------------------------------------------|
| `context`  | string | Scope under which the request is made; determines fields present in response. Default is `view`. Options: `view`. |
| `period`   | string | Report period. Default is today's date. Options: `week`, `month`, `last_month` and `year`                         |
| `date_min` | string | Return sales for a specific start date, the date need to be in the YYYY-MM-DD format.                             |
| `date_max` | string | Return sales for a specific end date, the date need to be in the YYYY-MM-DD format.                               |

## Retrieve top sellers report ##

This API lets you retrieve and view a list of top sellers report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/top_sellers</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/top_sellers?period=last_month \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/top_sellers", {
  period: "last_month"
})
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
$query = [
    'period' => 'last_month'
];

print_r($woocommerce->get('reports/top_sellers', $query));
?>
```

```python
print(wcapi.get("reports/top_sellers?period=last_month").json())
```

```ruby
query = {
  period: "last_month"
}

woocommerce.get("reports/top_sellers", query).parsed_response
```

> JSON response example:

```json
[
  {
    "title": "Happy Ninja",
    "product_id": 37,
    "quantity": 1,
    "_links": {
      "about": [
        {
          "href": "https://example.com/wp-json/wc/v3/reports"
        }
      ],
      "product": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/37"
        }
      ]
    }
  },
  {
    "title": "Woo Album #4",
    "product_id": 96,
    "quantity": 1,
    "_links": {
      "about": [
        {
          "href": "https://example.com/wp-json/wc/v3/reports"
        }
      ],
      "product": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/96"
        }
      ]
    }
  }
]
```

#### Top sellers report properties ####

| Attribute    | Type    | Description                                                          |
|--------------|---------|----------------------------------------------------------------------|
| `title`      | string  | Product title. <i class="label label-info">read-only</i>             |
| `product_id` | integer | Product ID. <i class="label label-info">read-only</i>                |
| `quantity`   | integer | Total number of purchases. <i class="label label-info">read-only</i> |

#### Available parameters ####

| Parameter  | Type   | Description                                                                                                       |
|------------|--------|-------------------------------------------------------------------------------------------------------------------|
| `context`  | string | Scope under which the request is made; determines fields present in response. Default is `view`. Options: `view`. |
| `period`   | string | Report period. Default is `week`. Options: `week`, `month`, `last_month` and `year`                               |
| `date_min` | string | Return sales for a specific start date, the date need to be in the YYYY-MM-DD format.                             |
| `date_max` | string | Return sales for a specific end date, the date need to be in the YYYY-MM-DD format.                               |

## Retrieve coupons totals ##

This API lets you retrieve and view coupons totals report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/coupons/totals</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/coupons/totals \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/coupons/totals")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
print_r($woocommerce->get('reports/coupons/totals'));
?>
```

```python
print(wcapi.get("reports/coupons/totals").json())
```

```ruby
woocommerce.get("reports/coupons/totals").parsed_response
```

> JSON response example:

```json
[
	{
		"slug": "percent",
		"name": "Percentage discount",
		"total": 2
	},
	{
		"slug": "fixed_cart",
		"name": "Fixed cart discount",
		"total": 1
	},
	{
		"slug": "fixed_product",
		"name": "Fixed product discount",
		"total": 1
	}
]
```

#### Coupons totals properties ####

| Attribute | Type   | Description                                                                            |
|-----------|--------|----------------------------------------------------------------------------------------|
| `slug`    | string | An alphanumeric identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string | Coupon type name. <i class="label label-info">read-only</i>                            |
| `total`   | string | Amount of coupons. <i class="label label-info">read-only</i>                           |

## Retrieve customers totals ##

This API lets you retrieve and view customers totals report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/customers/totals</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/customers/totals \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/customers/totals")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
print_r($woocommerce->get('reports/customers/totals'));
?>
```

```python
print(wcapi.get("reports/customers/totals").json())
```

```ruby
woocommerce.get("reports/customers/totals").parsed_response
```

> JSON response example:

```json
[
	{
		"slug": "paying",
		"name": "Paying customer",
		"total": 2
	},
	{
		"slug": "non_paying",
		"name": "Non-paying customer",
		"total": 1
	}
]
```

#### Customers totals properties ####

| Attribute | Type   | Description                                                                            |
|-----------|--------|----------------------------------------------------------------------------------------|
| `slug`    | string | An alphanumeric identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string | Customer type name. <i class="label label-info">read-only</i>                          |
| `total`   | string | Amount of customers. <i class="label label-info">read-only</i>                         |

## Retrieve orders totals ##

This API lets you retrieve and view orders totals report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/orders/totals</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/orders/totals \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/orders/totals")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
print_r($woocommerce->get('reports/orders/totals'));
?>
```

```python
print(wcapi.get("reports/orders/totals").json())
```

```ruby
woocommerce.get("reports/orders/totals").parsed_response
```

> JSON response example:

```json
[
	{
		"slug": "pending",
		"name": "Pending payment",
		"total": 7
	},
	{
		"slug": "processing",
		"name": "Processing",
		"total": 2
	},
	{
		"slug": "on-hold",
		"name": "On hold",
		"total": 1
	},
	{
		"slug": "completed",
		"name": "Completed",
		"total": 3
	},
	{
		"slug": "cancelled",
		"name": "Cancelled",
		"total": 0
	},
	{
		"slug": "refunded",
		"name": "Refunded",
		"total": 0
	},
	{
		"slug": "failed",
		"name": "Failed",
		"total": 0
	}
]
```

#### Orders totals properties ####

| Attribute | Type   | Description                                                                            |
|-----------|--------|----------------------------------------------------------------------------------------|
| `slug`    | string | An alphanumeric identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string | Orders status name. <i class="label label-info">read-only</i>                          |
| `total`   | string | Amount of orders. <i class="label label-info">read-only</i>                            |

## Retrieve products totals ##

This API lets you retrieve and view products totals report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/products/totals</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/products/totals \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/products/totals")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
print_r($woocommerce->get('reports/products/totals'));
?>
```

```python
print(wcapi.get("reports/products/totals").json())
```

```ruby
woocommerce.get("reports/products/totals").parsed_response
```

> JSON response example:

```json
[
	{
		"slug": "external",
		"name": "External/Affiliate product",
		"total": 1
	},
	{
		"slug": "grouped",
		"name": "Grouped product",
		"total": 1
	},
	{
		"slug": "simple",
		"name": "Simple product",
		"total": 21
	},
	{
		"slug": "variable",
		"name": "Variable product",
		"total": 3
	}
]
```

#### Products totals properties ####

| Attribute | Type   | Description                                                                            |
|-----------|--------|----------------------------------------------------------------------------------------|
| `slug`    | string | An alphanumeric identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string | Product type name. <i class="label label-info">read-only</i>                           |
| `total`   | string | Amount of products. <i class="label label-info">read-only</i>                          |

## Retrieve reviews totals ##

This API lets you retrieve and view reviews totals report.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/reports/reviews/totals</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/reports/reviews/totals \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("reports/reviews/totals")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
print_r($woocommerce->get('reports/reviews/totals'));
?>
```

```python
print(wcapi.get("reports/reviews/totals").json())
```

```ruby
woocommerce.get("reports/reviews/totals").parsed_response
```

> JSON response example:

```json
[
	{
		"slug": "rated_1_out_of_5",
		"name": "Rated 1 out of 5",
		"total": 1
	},
	{
		"slug": "rated_2_out_of_5",
		"name": "Rated 2 out of 5",
		"total": 0
	},
	{
		"slug": "rated_3_out_of_5",
		"name": "Rated 3 out of 5",
		"total": 3
	},
	{
		"slug": "rated_4_out_of_5",
		"name": "Rated 4 out of 5",
		"total": 0
	},
	{
		"slug": "rated_5_out_of_5",
		"name": "Rated 5 out of 5",
		"total": 4
	}
]
```

#### Reviews totals properties ####

| Attribute | Type   | Description                                                                            |
|-----------|--------|----------------------------------------------------------------------------------------|
| `slug`    | string | An alphanumeric identifier for the resource. <i class="label label-info">read-only</i> |
| `name`    | string | Review type name. <i class="label label-info">read-only</i>                            |
| `total`   | string | Amount of reviews. <i class="label label-info">read-only</i>                           |
