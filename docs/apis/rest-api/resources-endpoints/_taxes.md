# Tax rates #

The taxes API allows you to create, view, update, and delete individual tax rates, or a batch of tax rates.

## Tax rate properties ##

| Attribute  |   Type  | Description                                                                                                                                                                    |
|------------|---------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`       | integer | Unique identifier for the resource. <i class="label label-info">read-only</i>                                                                                                  |
| `country`  | string  | Country ISO 3166 code. See [ISO 3166 Codes (Countries)](http://www.chemie.fu-berlin.de/diverse/doc/ISO_3166.html) for more details                                             |
| `state`    | string  | State code.                                                                                                                                                                    |
| `postcode` | string  | Postcode/ZIP, it doesn't support multiple values. Deprecated as of WooCommerce 5.3, `postcodes` should be used instead.                                                        |
| `city`     | string  | City name, it doesn't support multiple values. Deprecated as of WooCommerce 5.3, `postcodes` should be used instead.                                                           |
| `postcodes`| string[]  | Postcodes/ZIPs. Introduced in WooCommerce 5.3.                                                                                                                                 |
| `cities`   | string[]  | City names. Introduced in WooCommerce 5.3.                                                                                                                                     |
| `rate`     | string  | Tax rate.                                                                                                                                                                      |
| `name`     | string  | Tax rate name.                                                                                                                                                                 |
| `priority` | integer | Tax priority. Only 1 matching rate per priority will be used. To define multiple tax rates for a single area you need to specify a different priority per rate. Default is `1`. |
| `compound` | boolean | Whether or not this is a compound tax rate. Compound rates are applied on top of other tax rates. Default is `false`.                                                       |
| `shipping` | boolean | Whether or not this tax rate also gets applied to shipping. Default is `true`.                                                                                                 |
| `order`    | integer | Indicates the order that will appear in queries.                                                                                                                               |
| `class`    | string  | Tax class. Default is `standard`.                                                                                                                                              |

## Create a tax rate ##

This API helps you to create a new tax rate.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/taxes</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/taxes \
    -u consumer_key:consumer_secret \
    -H "Content-Type: application/json" \
    -d '{
  "country": "US",
  "state": "AL",
  "cities": ["Alpine", "Brookside", "Cardiff"],
  "postcodes": ["35014", "35036", "35041"],
  "rate": "4",
  "name": "State Tax",
  "shipping": false
}'
```

```javascript
const data = {
  country: "US",
  state: "AL",
  cities: ["Alpine", "Brookside", "Cardiff"],
  postcodes: ["35014", "35036", "35041"],
  rate: "4",
  name: "State Tax",
  shipping: false
};

WooCommerce.post("taxes", data)
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
    'country' => 'US',
    'state' => 'AL',
    'cities' => ['Alpine', 'Brookside', 'Cardiff'],
    'postcodes' => ['35014', '35036', '35041'],
    'rate' => '4',
    'name' => 'State Tax',
    'shipping' => false
];

print_r($woocommerce->post('taxes', $data));
?>
```

```python
data = {
    "country": "US",
    "state": "AL",
    "cities": ["Alpine", "Brookside", "Cardiff"],
    "postcodes": ["35014", "35036", "35041"],
    "rate": "4",
    "name": "State Tax",
    "shipping": False
}

print(wcapi.post("taxes", data).json())
```

```ruby
data = {
  country: "US",
  state: "AL",
  cities: ["Alpine", "Brookside", "Cardiff"],
  postcodes: ["35014", "35036", "35041"],
  rate: "4",
  name: "State Tax",
  shipping: false
}

woocommerce.post("taxes", data).parsed_response
```

> JSON response example:

```json
{
  "id": 72,
  "country": "US",
  "state": "AL",
  "postcode": "35041",
  "city": "Cardiff",
  "postcodes": [
    "35014",
    "35036",
    "35041"
  ],
  "cities": [
    "Alpine",
    "Brookside",
    "Cardiff"
  ],
  "rate": "4.0000",
  "name": "State Tax",
  "priority": 0,
  "compound": false,
  "shipping": false,
  "order": 1,
  "class": "standard",
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes/72"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes"
      }
    ]
  }
}
```

## Retrieve a tax rate ##

This API lets you retrieve and view a specific tax rate by ID.

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/taxes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/taxes/72 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("taxes/72")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('taxes/72')); ?>
```

```python
print(wcapi.get("taxes/72").json())
```

```ruby
woocommerce.get("taxes/72").parsed_response
```

> JSON response example:

```json
{
  "id": 72,
  "country": "US",
  "state": "AL",
  "postcode": "35041",
  "city": "Cardiff",
  "postcodes": [
    "35014",
    "35036",
    "35041"
  ],
  "cities": [
    "Alpine",
    "Brookside",
    "Cardiff"
  ],
  "rate": "4.0000",
  "name": "State Tax",
  "priority": 0,
  "compound": false,
  "shipping": false,
  "order": 1,
  "class": "standard",
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes/72"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes"
      }
    ]
  }
}
```

## List all tax rates ##

This API helps you to view all the tax rates.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/taxes</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/taxes \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("taxes")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('taxes')); ?>
```

```python
print(wcapi.get("taxes").json())
```

```ruby
woocommerce.get("taxes").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 72,
    "country": "US",
    "state": "AL",
    "postcode": "35041",
    "city": "Cardiff",
    "postcodes": [
      "35014",
      "35036",
      "35041"
    ],
    "cities": [
      "Alpine",
      "Brookside",
      "Cardiff"
    ],
    "rate": "4.0000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": false,
    "order": 1,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/72"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 73,
    "country": "US",
    "state": "AZ",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "5.6000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": false,
    "order": 2,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/73"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 74,
    "country": "US",
    "state": "AR",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "6.5000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": true,
    "order": 3,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/74"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 75,
    "country": "US",
    "state": "CA",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "7.5000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": false,
    "order": 4,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/75"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 76,
    "country": "US",
    "state": "CO",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "2.9000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": false,
    "order": 5,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/76"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 77,
    "country": "US",
    "state": "CT",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "6.3500",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": true,
    "order": 6,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/77"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 78,
    "country": "US",
    "state": "DC",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "5.7500",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": true,
    "order": 7,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/78"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 79,
    "country": "US",
    "state": "FL",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "6.0000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": true,
    "order": 8,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/79"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 80,
    "country": "US",
    "state": "GA",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "4.0000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": true,
    "order": 9,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/80"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  },
  {
    "id": 81,
    "country": "US",
    "state": "GU",
    "postcode": "",
    "city": "",
    "postcodes": [],
    "cities": [],
    "rate": "4.0000",
    "name": "State Tax",
    "priority": 0,
    "compound": false,
    "shipping": false,
    "order": 10,
    "class": "standard",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes/81"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/taxes"
        }
      ]
    }
  }
]
```

#### Available parameters ####

| Parameter  |   Type  |                                                                Description                                                                 |
|------------|---------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `context`  | string  | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`.                                  |
| `page`     | integer | Current page of the collection.                                                                                                            |
| `per_page` | integer | Maximum number of items to be returned in result set.                                                                                      |
| `offset`   | integer | Offset the result set by a specific number of items.                                                                                       |
| `order`    | string  | Order sort attribute ascending or descending. Default is `asc`. Options: `asc` and `desc`.                                                 |
| `orderby`  | string  | Sort collection by object attribute. Default is `order`. Options: `id`, `order` and `priority`.                                            |
| `class`    | string  | Retrieve only tax rates of this Tax class.                                                                                                 |

## Update a tax rate ##

This API lets you make changes to a tax rate.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/taxes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/taxes/72 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "name": "US Tax"
}'
```

```javascript
const data = {
  name: "US Tax"
};

WooCommerce.put("taxes/72", data)
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
    'name' => 'US Tax'
];

print_r($woocommerce->put('taxes/72', $data));
?>
```

```python
data = {
    "name": "US Tax"
}

print(wcapi.put("taxes/72", data).json())
```

```ruby
data = {
  name: "US Tax"
}

woocommerce.put("taxes/72", data).parsed_response
```

> JSON response example:

```json
{
  "id": 72,
  "country": "US",
  "state": "AL",
  "postcode": "35041",
  "city": "Cardiff",
  "postcodes": [
    "35014",
    "35036",
    "35041"
  ],
  "cities": [
    "Alpine",
    "Brookside",
    "Cardiff"
  ],
  "rate": "4.0000",
  "name": "US Tax",
  "priority": 0,
  "compound": false,
  "shipping": false,
  "order": 1,
  "class": "standard",
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes/72"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes"
      }
    ]
  }
}
```

## Delete a tax rate ##

This API helps you delete a tax rate.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/taxes/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/taxes/72?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("taxes/72", {
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
<?php print_r($woocommerce->delete('taxes/72', ['force' => true])); ?>
```

```python
print(wcapi.delete("taxes/72", params={"force": True}).json())
```

```ruby
woocommerce.delete("taxes/72", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 72,
  "country": "US",
  "state": "AL",
  "postcode": "35041",
  "city": "Cardiff",
  "postcodes": [
    "35014",
    "35036",
    "35041"
  ],
  "cities": [
    "Alpine",
    "Brookside",
    "Cardiff"
  ],
  "rate": "4.0000",
  "name": "US Tax",
  "priority": 0,
  "compound": false,
  "shipping": false,
  "order": 1,
  "class": "standard",
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes/72"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/taxes"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |

## Batch update tax rates ##

This API helps you to batch create, update and delete multiple tax rates.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/taxes/batch</h6>
	</div>
</div>

> Example batch creating all US taxes:

```shell
curl -X POST https://example.com/wp-json/wc/v3/taxes/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "country": "US",
      "state": "AL",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 1
    },
    {
      "country": "US",
      "state": "AZ",
      "rate": "5.6000",
      "name": "State Tax",
      "shipping": false,
      "order": 2
    },
    {
      "country": "US",
      "state": "AR",
      "rate": "6.5000",
      "name": "State Tax",
      "shipping": true,
      "order": 3
    },
    {
      "country": "US",
      "state": "CA",
      "rate": "7.5000",
      "name": "State Tax",
      "shipping": false,
      "order": 4
    },
    {
      "country": "US",
      "state": "CO",
      "rate": "2.9000",
      "name": "State Tax",
      "shipping": false,
      "order": 5
    },
    {
      "country": "US",
      "state": "CT",
      "rate": "6.3500",
      "name": "State Tax",
      "shipping": true,
      "order": 6
    },
    {
      "country": "US",
      "state": "DC",
      "rate": "5.7500",
      "name": "State Tax",
      "shipping": true,
      "order": 7
    },
    {
      "country": "US",
      "state": "FL",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 8
    },
    {
      "country": "US",
      "state": "GA",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 9
    },
    {
      "country": "US",
      "state": "GU",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 10
    },
    {
      "country": "US",
      "state": "HI",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 11
    },
    {
      "country": "US",
      "state": "ID",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 12
    },
    {
      "country": "US",
      "state": "IL",
      "rate": "6.2500",
      "name": "State Tax",
      "shipping": false,
      "order": 13
    },
    {
      "country": "US",
      "state": "IN",
      "rate": "7.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 14
    },
    {
      "country": "US",
      "state": "IA",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 15
    },
    {
      "country": "US",
      "state": "KS",
      "rate": "6.1500",
      "name": "State Tax",
      "shipping": true,
      "order": 16
    },
    {
      "country": "US",
      "state": "KY",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 17
    },
    {
      "country": "US",
      "state": "LA",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 18
    },
    {
      "country": "US",
      "state": "ME",
      "rate": "5.5000",
      "name": "State Tax",
      "shipping": false,
      "order": 19
    },
    {
      "country": "US",
      "state": "MD",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 20
    },
    {
      "country": "US",
      "state": "MA",
      "rate": "6.2500",
      "name": "State Tax",
      "shipping": false,
      "order": 21
    },
    {
      "country": "US",
      "state": "MI",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 22
    },
    {
      "country": "US",
      "state": "MN",
      "rate": "6.8750",
      "name": "State Tax",
      "shipping": true,
      "order": 23
    },
    {
      "country": "US",
      "state": "MS",
      "rate": "7.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 24
    },
    {
      "country": "US",
      "state": "MO",
      "rate": "4.2250",
      "name": "State Tax",
      "shipping": false,
      "order": 25
    },
    {
      "country": "US",
      "state": "NE",
      "rate": "5.5000",
      "name": "State Tax",
      "shipping": true,
      "order": 26
    },
    {
      "country": "US",
      "state": "NV",
      "rate": "6.8500",
      "name": "State Tax",
      "shipping": false,
      "order": 27
    },
    {
      "country": "US",
      "state": "NJ",
      "rate": "7.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 28
    },
    {
      "country": "US",
      "state": "NM",
      "rate": "5.1250",
      "name": "State Tax",
      "shipping": true,
      "order": 29
    },
    {
      "country": "US",
      "state": "NY",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 30
    },
    {
      "country": "US",
      "state": "NC",
      "rate": "4.7500",
      "name": "State Tax",
      "shipping": true,
      "order": 31
    },
    {
      "country": "US",
      "state": "ND",
      "rate": "5.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 32
    },
    {
      "country": "US",
      "state": "OH",
      "rate": "5.7500",
      "name": "State Tax",
      "shipping": true,
      "order": 33
    },
    {
      "country": "US",
      "state": "OK",
      "rate": "4.5000",
      "name": "State Tax",
      "shipping": false,
      "order": 34
    },
    {
      "country": "US",
      "state": "PA",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 35
    },
    {
      "country": "US",
      "state": "PR",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 36
    },
    {
      "country": "US",
      "state": "RI",
      "rate": "7.0000",
      "name": "State Tax",
      "shipping": false,
      "order": 37
    },
    {
      "country": "US",
      "state": "SC",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 38
    },
    {
      "country": "US",
      "state": "SD",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 39
    },
    {
      "country": "US",
      "state": "TN",
      "rate": "7.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 40
    },
    {
      "country": "US",
      "state": "TX",
      "rate": "6.2500",
      "name": "State Tax",
      "shipping": true,
      "order": 41
    },
    {
      "country": "US",
      "state": "UT",
      "rate": "5.9500",
      "name": "State Tax",
      "shipping": false,
      "order": 42
    },
    {
      "country": "US",
      "state": "VT",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 43
    },
    {
      "country": "US",
      "state": "VA",
      "rate": "5.3000",
      "name": "State Tax",
      "shipping": false,
      "order": 44
    },
    {
      "country": "US",
      "state": "WA",
      "rate": "6.5000",
      "name": "State Tax",
      "shipping": true,
      "order": 45
    },
    {
      "country": "US",
      "state": "WV",
      "rate": "6.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 46
    },
    {
      "country": "US",
      "state": "WI",
      "rate": "5.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 47
    },
    {
      "country": "US",
      "state": "WY",
      "rate": "4.0000",
      "name": "State Tax",
      "shipping": true,
      "order": 48
    }
  ]
}'
```

```javascript
const data = {
  create: [
    {
      country: "US",
      state: "AL",
      rate: "4.0000",
      name: "State Tax",
      shipping: false,
      order: 1
    },
    {
      country: "US",
      state: "AZ",
      rate: "5.6000",
      name: "State Tax",
      shipping: false,
      order: 2
    },
    {
      country: "US",
      state: "AR",
      rate: "6.5000",
      name: "State Tax",
      shipping: true,
      order: 3
    },
    {
      country: "US",
      state: "CA",
      rate: "7.5000",
      name: "State Tax",
      shipping: false,
      order: 4
    },
    {
      country: "US",
      state: "CO",
      rate: "2.9000",
      name: "State Tax",
      shipping: false,
      order: 5
    },
    {
      country: "US",
      state: "CT",
      rate: "6.3500",
      name: "State Tax",
      shipping: true,
      order: 6
    },
    {
      country: "US",
      state: "DC",
      rate: "5.7500",
      name: "State Tax",
      shipping: true,
      order: 7
    },
    {
      country: "US",
      state: "FL",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 8
    },
    {
      country: "US",
      state: "GA",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 9
    },
    {
      country: "US",
      state: "GU",
      rate: "4.0000",
      name: "State Tax",
      shipping: false,
      order: 10
    },
    {
      country: "US",
      state: "HI",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 11
    },
    {
      country: "US",
      state: "ID",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 12
    },
    {
      country: "US",
      state: "IL",
      rate: "6.2500",
      name: "State Tax",
      shipping: false,
      order: 13
    },
    {
      country: "US",
      state: "IN",
      rate: "7.0000",
      name: "State Tax",
      shipping: false,
      order: 14
    },
    {
      country: "US",
      state: "IA",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 15
    },
    {
      country: "US",
      state: "KS",
      rate: "6.1500",
      name: "State Tax",
      shipping: true,
      order: 16
    },
    {
      country: "US",
      state: "KY",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 17
    },
    {
      country: "US",
      state: "LA",
      rate: "4.0000",
      name: "State Tax",
      shipping: false,
      order: 18
    },
    {
      country: "US",
      state: "ME",
      rate: "5.5000",
      name: "State Tax",
      shipping: false,
      order: 19
    },
    {
      country: "US",
      state: "MD",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 20
    },
    {
      country: "US",
      state: "MA",
      rate: "6.2500",
      name: "State Tax",
      shipping: false,
      order: 21
    },
    {
      country: "US",
      state: "MI",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 22
    },
    {
      country: "US",
      state: "MN",
      rate: "6.8750",
      name: "State Tax",
      shipping: true,
      order: 23
    },
    {
      country: "US",
      state: "MS",
      rate: "7.0000",
      name: "State Tax",
      shipping: true,
      order: 24
    },
    {
      country: "US",
      state: "MO",
      rate: "4.2250",
      name: "State Tax",
      shipping: false,
      order: 25
    },
    {
      country: "US",
      state: "NE",
      rate: "5.5000",
      name: "State Tax",
      shipping: true,
      order: 26
    },
    {
      country: "US",
      state: "NV",
      rate: "6.8500",
      name: "State Tax",
      shipping: false,
      order: 27
    },
    {
      country: "US",
      state: "NJ",
      rate: "7.0000",
      name: "State Tax",
      shipping: true,
      order: 28
    },
    {
      country: "US",
      state: "NM",
      rate: "5.1250",
      name: "State Tax",
      shipping: true,
      order: 29
    },
    {
      country: "US",
      state: "NY",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 30
    },
    {
      country: "US",
      state: "NC",
      rate: "4.7500",
      name: "State Tax",
      shipping: true,
      order: 31
    },
    {
      country: "US",
      state: "ND",
      rate: "5.0000",
      name: "State Tax",
      shipping: true,
      order: 32
    },
    {
      country: "US",
      state: "OH",
      rate: "5.7500",
      name: "State Tax",
      shipping: true,
      order: 33
    },
    {
      country: "US",
      state: "OK",
      rate: "4.5000",
      name: "State Tax",
      shipping: false,
      order: 34
    },
    {
      country: "US",
      state: "PA",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 35
    },
    {
      country: "US",
      state: "PR",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 36
    },
    {
      country: "US",
      state: "RI",
      rate: "7.0000",
      name: "State Tax",
      shipping: false,
      order: 37
    },
    {
      country: "US",
      state: "SC",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 38
    },
    {
      country: "US",
      state: "SD",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 39
    },
    {
      country: "US",
      state: "TN",
      rate: "7.0000",
      name: "State Tax",
      shipping: true,
      order: 40
    },
    {
      country: "US",
      state: "TX",
      rate: "6.2500",
      name: "State Tax",
      shipping: true,
      order: 41
    },
    {
      country: "US",
      state: "UT",
      rate: "5.9500",
      name: "State Tax",
      shipping: false,
      order: 42
    },
    {
      country: "US",
      state: "VT",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 43
    },
    {
      country: "US",
      state: "VA",
      rate: "5.3000",
      name: "State Tax",
      shipping: false,
      order: 44
    },
    {
      country: "US",
      state: "WA",
      rate: "6.5000",
      name: "State Tax",
      shipping: true,
      order: 45
    },
    {
      country: "US",
      state: "WV",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 46
    },
    {
      country: "US",
      state: "WI",
      rate: "5.0000",
      name: "State Tax",
      shipping: true,
      order: 47
    },
    {
      country: "US",
      state: "WY",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 48
    }
  ]
};

WooCommerce.post("taxes/batch", data)
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
            'country' => 'US',
            'state' => 'AL',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 1
        ],
        [
            'country' => 'US',
            'state' => 'AZ',
            'rate' => '5.6000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 2
        ],
        [
            'country' => 'US',
            'state' => 'AR',
            'rate' => '6.5000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 3
        ],
        [
            'country' => 'US',
            'state' => 'CA',
            'rate' => '7.5000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 4
        ],
        [
            'country' => 'US',
            'state' => 'CO',
            'rate' => '2.9000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 5
        ],
        [
            'country' => 'US',
            'state' => 'CT',
            'rate' => '6.3500',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 6
        ],
        [
            'country' => 'US',
            'state' => 'DC',
            'rate' => '5.7500',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 7
        ],
        [
            'country' => 'US',
            'state' => 'FL',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 8
        ],
        [
            'country' => 'US',
            'state' => 'GA',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 9
        ],
        [
            'country' => 'US',
            'state' => 'GU',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 10
        ],
        [
            'country' => 'US',
            'state' => 'HI',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 11
        ],
        [
            'country' => 'US',
            'state' => 'ID',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 12
        ],
        [
            'country' => 'US',
            'state' => 'IL',
            'rate' => '6.2500',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 13
        ],
        [
            'country' => 'US',
            'state' => 'IN',
            'rate' => '7.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 14
        ],
        [
            'country' => 'US',
            'state' => 'IA',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 15
        ],
        [
            'country' => 'US',
            'state' => 'KS',
            'rate' => '6.1500',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 16
        ],
        [
            'country' => 'US',
            'state' => 'KY',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 17
        ],
        [
            'country' => 'US',
            'state' => 'LA',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 18
        ],
        [
            'country' => 'US',
            'state' => 'ME',
            'rate' => '5.5000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 19
        ],
        [
            'country' => 'US',
            'state' => 'MD',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 20
        ],
        [
            'country' => 'US',
            'state' => 'MA',
            'rate' => '6.2500',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 21
        ],
        [
            'country' => 'US',
            'state' => 'MI',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 22
        ],
        [
            'country' => 'US',
            'state' => 'MN',
            'rate' => '6.8750',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 23
        ],
        [
            'country' => 'US',
            'state' => 'MS',
            'rate' => '7.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 24
        ],
        [
            'country' => 'US',
            'state' => 'MO',
            'rate' => '4.2250',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 25
        ],
        [
            'country' => 'US',
            'state' => 'NE',
            'rate' => '5.5000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 26
        ],
        [
            'country' => 'US',
            'state' => 'NV',
            'rate' => '6.8500',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 27
        ],
        [
            'country' => 'US',
            'state' => 'NJ',
            'rate' => '7.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 28
        ],
        [
            'country' => 'US',
            'state' => 'NM',
            'rate' => '5.1250',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 29
        ],
        [
            'country' => 'US',
            'state' => 'NY',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 30
        ],
        [
            'country' => 'US',
            'state' => 'NC',
            'rate' => '4.7500',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 31
        ],
        [
            'country' => 'US',
            'state' => 'ND',
            'rate' => '5.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 32
        ],
        [
            'country' => 'US',
            'state' => 'OH',
            'rate' => '5.7500',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 33
        ],
        [
            'country' => 'US',
            'state' => 'OK',
            'rate' => '4.5000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 34
        ],
        [
            'country' => 'US',
            'state' => 'PA',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 35
        ],
        [
            'country' => 'US',
            'state' => 'PR',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 36
        ],
        [
            'country' => 'US',
            'state' => 'RI',
            'rate' => '7.0000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 37
        ],
        [
            'country' => 'US',
            'state' => 'SC',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 38
        ],
        [
            'country' => 'US',
            'state' => 'SD',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 39
        ],
        [
            'country' => 'US',
            'state' => 'TN',
            'rate' => '7.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 40
        ],
        [
            'country' => 'US',
            'state' => 'TX',
            'rate' => '6.2500',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 41
        ],
        [
            'country' => 'US',
            'state' => 'UT',
            'rate' => '5.9500',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 42
        ],
        [
            'country' => 'US',
            'state' => 'VT',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 43
        ],
        [
            'country' => 'US',
            'state' => 'VA',
            'rate' => '5.3000',
            'name' => 'State Tax',
            'shipping' => false,
            'order' => 44
        ],
        [
            'country' => 'US',
            'state' => 'WA',
            'rate' => '6.5000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 45
        ],
        [
            'country' => 'US',
            'state' => 'WV',
            'rate' => '6.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 46
        ],
        [
            'country' => 'US',
            'state' => 'WI',
            'rate' => '5.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 47
        ],
        [
            'country' => 'US',
            'state' => 'WY',
            'rate' => '4.0000',
            'name' => 'State Tax',
            'shipping' => true,
            'order' => 48
        ]
    ]
];

print_r($woocommerce->post('taxes/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "country": "US",
            "state": "AL",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 1
        },
        {
            "country": "US",
            "state": "AZ",
            "rate": "5.6000",
            "name": "State Tax",
            "shipping": False,
            "order": 2
        },
        {
            "country": "US",
            "state": "AR",
            "rate": "6.5000",
            "name": "State Tax",
            "shipping": True,
            "order": 3
        },
        {
            "country": "US",
            "state": "CA",
            "rate": "7.5000",
            "name": "State Tax",
            "shipping": False,
            "order": 4
        },
        {
            "country": "US",
            "state": "CO",
            "rate": "2.9000",
            "name": "State Tax",
            "shipping": False,
            "order": 5
        },
        {
            "country": "US",
            "state": "CT",
            "rate": "6.3500",
            "name": "State Tax",
            "shipping": True,
            "order": 6
        },
        {
            "country": "US",
            "state": "DC",
            "rate": "5.7500",
            "name": "State Tax",
            "shipping": True,
            "order": 7
        },
        {
            "country": "US",
            "state": "FL",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 8
        },
        {
            "country": "US",
            "state": "GA",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 9
        },
        {
            "country": "US",
            "state": "GU",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 10
        },
        {
            "country": "US",
            "state": "HI",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 11
        },
        {
            "country": "US",
            "state": "ID",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 12
        },
        {
            "country": "US",
            "state": "IL",
            "rate": "6.2500",
            "name": "State Tax",
            "shipping": False,
            "order": 13
        },
        {
            "country": "US",
            "state": "IN",
            "rate": "7.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 14
        },
        {
            "country": "US",
            "state": "IA",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 15
        },
        {
            "country": "US",
            "state": "KS",
            "rate": "6.1500",
            "name": "State Tax",
            "shipping": True,
            "order": 16
        },
        {
            "country": "US",
            "state": "KY",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 17
        },
        {
            "country": "US",
            "state": "LA",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 18
        },
        {
            "country": "US",
            "state": "ME",
            "rate": "5.5000",
            "name": "State Tax",
            "shipping": False,
            "order": 19
        },
        {
            "country": "US",
            "state": "MD",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 20
        },
        {
            "country": "US",
            "state": "MA",
            "rate": "6.2500",
            "name": "State Tax",
            "shipping": False,
            "order": 21
        },
        {
            "country": "US",
            "state": "MI",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 22
        },
        {
            "country": "US",
            "state": "MN",
            "rate": "6.8750",
            "name": "State Tax",
            "shipping": True,
            "order": 23
        },
        {
            "country": "US",
            "state": "MS",
            "rate": "7.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 24
        },
        {
            "country": "US",
            "state": "MO",
            "rate": "4.2250",
            "name": "State Tax",
            "shipping": False,
            "order": 25
        },
        {
            "country": "US",
            "state": "NE",
            "rate": "5.5000",
            "name": "State Tax",
            "shipping": True,
            "order": 26
        },
        {
            "country": "US",
            "state": "NV",
            "rate": "6.8500",
            "name": "State Tax",
            "shipping": False,
            "order": 27
        },
        {
            "country": "US",
            "state": "NJ",
            "rate": "7.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 28
        },
        {
            "country": "US",
            "state": "NM",
            "rate": "5.1250",
            "name": "State Tax",
            "shipping": True,
            "order": 29
        },
        {
            "country": "US",
            "state": "NY",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 30
        },
        {
            "country": "US",
            "state": "NC",
            "rate": "4.7500",
            "name": "State Tax",
            "shipping": True,
            "order": 31
        },
        {
            "country": "US",
            "state": "ND",
            "rate": "5.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 32
        },
        {
            "country": "US",
            "state": "OH",
            "rate": "5.7500",
            "name": "State Tax",
            "shipping": True,
            "order": 33
        },
        {
            "country": "US",
            "state": "OK",
            "rate": "4.5000",
            "name": "State Tax",
            "shipping": False,
            "order": 34
        },
        {
            "country": "US",
            "state": "PA",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 35
        },
        {
            "country": "US",
            "state": "PR",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 36
        },
        {
            "country": "US",
            "state": "RI",
            "rate": "7.0000",
            "name": "State Tax",
            "shipping": False,
            "order": 37
        },
        {
            "country": "US",
            "state": "SC",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 38
        },
        {
            "country": "US",
            "state": "SD",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 39
        },
        {
            "country": "US",
            "state": "TN",
            "rate": "7.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 40
        },
        {
            "country": "US",
            "state": "TX",
            "rate": "6.2500",
            "name": "State Tax",
            "shipping": True,
            "order": 41
        },
        {
            "country": "US",
            "state": "UT",
            "rate": "5.9500",
            "name": "State Tax",
            "shipping": False,
            "order": 42
        },
        {
            "country": "US",
            "state": "VT",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 43
        },
        {
            "country": "US",
            "state": "VA",
            "rate": "5.3000",
            "name": "State Tax",
            "shipping": False,
            "order": 44
        },
        {
            "country": "US",
            "state": "WA",
            "rate": "6.5000",
            "name": "State Tax",
            "shipping": True,
            "order": 45
        },
        {
            "country": "US",
            "state": "WV",
            "rate": "6.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 46
        },
        {
            "country": "US",
            "state": "WI",
            "rate": "5.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 47
        },
        {
            "country": "US",
            "state": "WY",
            "rate": "4.0000",
            "name": "State Tax",
            "shipping": True,
            "order": 48
        }
    ]
}

print(wcapi.post("taxes/batch", data).json())
```

```ruby
data = {
  create: [
    {
      country: "US",
      state: "AL",
      rate: "4.0000",
      name: "State Tax",
      shipping: false,
      order: 1
    },
    {
      country: "US",
      state: "AZ",
      rate: "5.6000",
      name: "State Tax",
      shipping: false,
      order: 2
    },
    {
      country: "US",
      state: "AR",
      rate: "6.5000",
      name: "State Tax",
      shipping: true,
      order: 3
    },
    {
      country: "US",
      state: "CA",
      rate: "7.5000",
      name: "State Tax",
      shipping: false,
      order: 4
    },
    {
      country: "US",
      state: "CO",
      rate: "2.9000",
      name: "State Tax",
      shipping: false,
      order: 5
    },
    {
      country: "US",
      state: "CT",
      rate: "6.3500",
      name: "State Tax",
      shipping: true,
      order: 6
    },
    {
      country: "US",
      state: "DC",
      rate: "5.7500",
      name: "State Tax",
      shipping: true,
      order: 7
    },
    {
      country: "US",
      state: "FL",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 8
    },
    {
      country: "US",
      state: "GA",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 9
    },
    {
      country: "US",
      state: "GU",
      rate: "4.0000",
      name: "State Tax",
      shipping: false,
      order: 10
    },
    {
      country: "US",
      state: "HI",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 11
    },
    {
      country: "US",
      state: "ID",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 12
    },
    {
      country: "US",
      state: "IL",
      rate: "6.2500",
      name: "State Tax",
      shipping: false,
      order: 13
    },
    {
      country: "US",
      state: "IN",
      rate: "7.0000",
      name: "State Tax",
      shipping: false,
      order: 14
    },
    {
      country: "US",
      state: "IA",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 15
    },
    {
      country: "US",
      state: "KS",
      rate: "6.1500",
      name: "State Tax",
      shipping: true,
      order: 16
    },
    {
      country: "US",
      state: "KY",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 17
    },
    {
      country: "US",
      state: "LA",
      rate: "4.0000",
      name: "State Tax",
      shipping: false,
      order: 18
    },
    {
      country: "US",
      state: "ME",
      rate: "5.5000",
      name: "State Tax",
      shipping: false,
      order: 19
    },
    {
      country: "US",
      state: "MD",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 20
    },
    {
      country: "US",
      state: "MA",
      rate: "6.2500",
      name: "State Tax",
      shipping: false,
      order: 21
    },
    {
      country: "US",
      state: "MI",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 22
    },
    {
      country: "US",
      state: "MN",
      rate: "6.8750",
      name: "State Tax",
      shipping: true,
      order: 23
    },
    {
      country: "US",
      state: "MS",
      rate: "7.0000",
      name: "State Tax",
      shipping: true,
      order: 24
    },
    {
      country: "US",
      state: "MO",
      rate: "4.2250",
      name: "State Tax",
      shipping: false,
      order: 25
    },
    {
      country: "US",
      state: "NE",
      rate: "5.5000",
      name: "State Tax",
      shipping: true,
      order: 26
    },
    {
      country: "US",
      state: "NV",
      rate: "6.8500",
      name: "State Tax",
      shipping: false,
      order: 27
    },
    {
      country: "US",
      state: "NJ",
      rate: "7.0000",
      name: "State Tax",
      shipping: true,
      order: 28
    },
    {
      country: "US",
      state: "NM",
      rate: "5.1250",
      name: "State Tax",
      shipping: true,
      order: 29
    },
    {
      country: "US",
      state: "NY",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 30
    },
    {
      country: "US",
      state: "NC",
      rate: "4.7500",
      name: "State Tax",
      shipping: true,
      order: 31
    },
    {
      country: "US",
      state: "ND",
      rate: "5.0000",
      name: "State Tax",
      shipping: true,
      order: 32
    },
    {
      country: "US",
      state: "OH",
      rate: "5.7500",
      name: "State Tax",
      shipping: true,
      order: 33
    },
    {
      country: "US",
      state: "OK",
      rate: "4.5000",
      name: "State Tax",
      shipping: false,
      order: 34
    },
    {
      country: "US",
      state: "PA",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 35
    },
    {
      country: "US",
      state: "PR",
      rate: "6.0000",
      name: "State Tax",
      shipping: false,
      order: 36
    },
    {
      country: "US",
      state: "RI",
      rate: "7.0000",
      name: "State Tax",
      shipping: false,
      order: 37
    },
    {
      country: "US",
      state: "SC",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 38
    },
    {
      country: "US",
      state: "SD",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 39
    },
    {
      country: "US",
      state: "TN",
      rate: "7.0000",
      name: "State Tax",
      shipping: true,
      order: 40
    },
    {
      country: "US",
      state: "TX",
      rate: "6.2500",
      name: "State Tax",
      shipping: true,
      order: 41
    },
    {
      country: "US",
      state: "UT",
      rate: "5.9500",
      name: "State Tax",
      shipping: false,
      order: 42
    },
    {
      country: "US",
      state: "VT",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 43
    },
    {
      country: "US",
      state: "VA",
      rate: "5.3000",
      name: "State Tax",
      shipping: false,
      order: 44
    },
    {
      country: "US",
      state: "WA",
      rate: "6.5000",
      name: "State Tax",
      shipping: true,
      order: 45
    },
    {
      country: "US",
      state: "WV",
      rate: "6.0000",
      name: "State Tax",
      shipping: true,
      order: 46
    },
    {
      country: "US",
      state: "WI",
      rate: "5.0000",
      name: "State Tax",
      shipping: true,
      order: 47
    },
    {
      country: "US",
      state: "WY",
      rate: "4.0000",
      name: "State Tax",
      shipping: true,
      order: 48
    }
  ]
}

woocommerce.post("taxes/batch", data).parsed_response
```

> JSON response example:

```json
{
  "create": [
    {
      "id": 72,
      "country": "US",
      "state": "AL",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 1,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/72"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 73,
      "country": "US",
      "state": "AZ",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.6000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 2,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/73"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 74,
      "country": "US",
      "state": "AR",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.5000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 3,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/74"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 75,
      "country": "US",
      "state": "CA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "7.5000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 4,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/75"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 76,
      "country": "US",
      "state": "CO",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "2.9000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 5,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/76"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 77,
      "country": "US",
      "state": "CT",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.3500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 6,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/77"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 78,
      "country": "US",
      "state": "DC",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.7500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 7,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/78"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 79,
      "country": "US",
      "state": "FL",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 8,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/79"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 80,
      "country": "US",
      "state": "GA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 9,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/80"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 81,
      "country": "US",
      "state": "GU",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 10,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/81"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 82,
      "country": "US",
      "state": "HI",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 11,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/82"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 83,
      "country": "US",
      "state": "ID",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 12,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/83"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 84,
      "country": "US",
      "state": "IL",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.2500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 13,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/84"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 85,
      "country": "US",
      "state": "IN",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "7.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 14,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/85"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 86,
      "country": "US",
      "state": "IA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 15,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/86"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 87,
      "country": "US",
      "state": "KS",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.1500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 16,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/87"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 88,
      "country": "US",
      "state": "KY",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 17,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/88"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 89,
      "country": "US",
      "state": "LA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 18,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/89"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 90,
      "country": "US",
      "state": "ME",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.5000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 19,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/90"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 91,
      "country": "US",
      "state": "MD",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 20,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/91"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 92,
      "country": "US",
      "state": "MA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.2500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 21,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/92"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 93,
      "country": "US",
      "state": "MI",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 22,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/93"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 94,
      "country": "US",
      "state": "MN",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.8750",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 23,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/94"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 95,
      "country": "US",
      "state": "MS",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "7.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 24,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/95"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 96,
      "country": "US",
      "state": "MO",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.2250",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 25,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/96"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 97,
      "country": "US",
      "state": "NE",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.5000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 26,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/97"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 98,
      "country": "US",
      "state": "NV",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.8500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 27,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/98"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 99,
      "country": "US",
      "state": "NJ",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "7.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 28,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/99"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 100,
      "country": "US",
      "state": "NM",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.1250",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 29,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/100"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 101,
      "country": "US",
      "state": "NY",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 30,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/101"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 102,
      "country": "US",
      "state": "NC",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.7500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 31,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/102"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 103,
      "country": "US",
      "state": "ND",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 32,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/103"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 104,
      "country": "US",
      "state": "OH",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.7500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 33,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/104"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 105,
      "country": "US",
      "state": "OK",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.5000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 34,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/105"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 106,
      "country": "US",
      "state": "PA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 35,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/106"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 107,
      "country": "US",
      "state": "PR",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 36,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/107"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 108,
      "country": "US",
      "state": "RI",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "7.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 37,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/108"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 109,
      "country": "US",
      "state": "SC",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 38,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/109"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 110,
      "country": "US",
      "state": "SD",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 39,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/110"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 111,
      "country": "US",
      "state": "TN",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "7.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 40,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/111"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 112,
      "country": "US",
      "state": "TX",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.2500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 41,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/112"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 113,
      "country": "US",
      "state": "UT",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.9500",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 42,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/113"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 114,
      "country": "US",
      "state": "VT",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 43,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/114"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 115,
      "country": "US",
      "state": "VA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.3000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": false,
      "order": 44,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/115"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 116,
      "country": "US",
      "state": "WA",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.5000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 45,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/116"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 117,
      "country": "US",
      "state": "WV",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "6.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 46,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/117"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 118,
      "country": "US",
      "state": "WI",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "5.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 47,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/118"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    },
    {
      "id": 119,
      "country": "US",
      "state": "WY",
      "postcode": "",
      "city": "",
      "postcodes": [],
      "cities": [],
      "rate": "4.0000",
      "name": "State Tax",
      "priority": 0,
      "compound": false,
      "shipping": true,
      "order": 48,
      "class": "standard",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes/119"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/taxes"
          }
        ]
      }
    }
  ]
}
```
