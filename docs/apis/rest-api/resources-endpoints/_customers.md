# Customers #

The customer API allows you to create, view, update, and delete individual, or a batch, of customers.

## Customer properties ##

| Attribute            | Type      | Description                                                                                                |
|----------------------|-----------|------------------------------------------------------------------------------------------------------------|
| `id`                 | integer   | Unique identifier for the resource. <i class="label label-info">read-only</i>                              |
| `date_created`       | date-time | The date the customer was created, in the site's timezone. <i class="label label-info">read-only</i>       |
| `date_created_gmt`   | date-time | The date the customer was created, as GMT. <i class="label label-info">read-only</i>                          |
| `date_modified`      | date-time | The date the customer was last modified, in the site's timezone. <i class="label label-info">read-only</i> |
| `date_modified_gmt`  | date-time | The date the customer was last modified, as GMT. <i class="label label-info">read-only</i>                 |
| `email`              | string    | The email address for the customer. <i class="label label-info">mandatory</i>                              |
| `first_name`         | string    | Customer first name.                                                                                       |
| `last_name`          | string    | Customer last name.                                                                                        |
| `role`               | string    | Customer role. <i class="label label-info">read-only</i>                                                   |
| `username`           | string    | Customer login name.                                                                                       |
| `password`           | string    | Customer password. <i class="label label-info">write-only</i>                                              |
| `billing`            | object    | List of billing address data. See [Customer - Billing properties](#customer-billing-properties)            |
| `shipping`           | object    | List of shipping address data. See [Customer - Shipping properties](#customer-shipping-properties)         |
| `is_paying_customer` | bool      | Is the customer a paying customer? <i class="label label-info">read-only</i>                               |
| `avatar_url`         | string    | Avatar URL. <i class="label label-info">read-only</i>                                                      |
| `meta_data`          | array     | Meta data. See [Customer - Meta data properties](#customer-meta-data-properties)                           |

### Customer - Billing properties ###

| Attribute    | Type   | Description                                          |
|--------------|--------|------------------------------------------------------|
| `first_name` | string | First name.                                          |
| `last_name`  | string | Last name.                                           |
| `company`    | string | Company name.                                        |
| `address_1`  | string | Address line 1                                       |
| `address_2`  | string | Address line 2                                       |
| `city`       | string | City name.                                           |
| `state`      | string | ISO code or name of the state, province or district. |
| `postcode`   | string | Postal code.                                         |
| `country`    | string | ISO code of the country.                             |
| `email`      | string | Email address.                                       |
| `phone`      | string | Phone number.                                        |

### Customer - Shipping properties ###

| Attribute    | Type   | Description                                          |
|--------------|--------|------------------------------------------------------|
| `first_name` | string | First name.                                          |
| `last_name`  | string | Last name.                                           |
| `company`    | string | Company name.                                        |
| `address_1`  | string | Address line 1                                       |
| `address_2`  | string | Address line 2                                       |
| `city`       | string | City name.                                           |
| `state`      | string | ISO code or name of the state, province or district. |
| `postcode`   | string | Postal code.                                         |
| `country`    | string | ISO code of the country.                             |

### Customer - Meta data properties ###

| Attribute | Type    | Description                                        |
|-----------|---------|----------------------------------------------------|
| `id`      | integer | Meta ID. <i class="label label-info">read-only</i> |
| `key`     | string  | Meta key.                                          |
| `value`   | string  | Meta value.                                        |

## Create a customer ##

This API helps you to create a new customer.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/customers</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/customers \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "email": "john.doe@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "username": "john.doe",
  "billing": {
    "first_name": "John",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US",
    "email": "john.doe@example.com",
    "phone": "(555) 555-5555"
  },
  "shipping": {
    "first_name": "John",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US"
  }
}'
```

```javascript
const data = {
  email: "john.doe@example.com",
  first_name: "John",
  last_name: "Doe",
  username: "john.doe",
  billing: {
    first_name: "John",
    last_name: "Doe",
    company: "",
    address_1: "969 Market",
    address_2: "",
    city: "San Francisco",
    state: "CA",
    postcode: "94103",
    country: "US",
    email: "john.doe@example.com",
    phone: "(555) 555-5555"
  },
  shipping: {
    first_name: "John",
    last_name: "Doe",
    company: "",
    address_1: "969 Market",
    address_2: "",
    city: "San Francisco",
    state: "CA",
    postcode: "94103",
    country: "US"
  }
};

WooCommerce.post("customers", data)
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
    'email' => 'john.doe@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'username' => 'john.doe',
    'billing' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'company' => '',
        'address_1' => '969 Market',
        'address_2' => '',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postcode' => '94103',
        'country' => 'US',
        'email' => 'john.doe@example.com',
        'phone' => '(555) 555-5555'
    ],
    'shipping' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'company' => '',
        'address_1' => '969 Market',
        'address_2' => '',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postcode' => '94103',
        'country' => 'US'
    ]
];

print_r($woocommerce->post('customers', $data));
?>
```

```python
data = {
    "email": "john.doe@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "username": "john.doe",
    "billing": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US",
        "email": "john.doe@example.com",
        "phone": "(555) 555-5555"
    },
    "shipping": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US"
    }
}

print(wcapi.post("customers", data).json())
```

```ruby
data = {
  email: "john.doe@example.com",
  first_name: "John",
  last_name: "Doe",
  username: "john.doe",
  billing: {
    first_name: "John",
    last_name: "Doe",
    company: "",
    address_1: "969 Market",
    address_2: "",
    city: "San Francisco",
    state: "CA",
    postcode: "94103",
    country: "US",
    email: "john.doe@example.com",
    phone: "(555) 555-5555"
  },
  shipping: {
    first_name: "John",
    last_name: "Doe",
    company: "",
    address_1: "969 Market",
    address_2: "",
    city: "San Francisco",
    state: "CA",
    postcode: "94103",
    country: "US"
  }
}

woocommerce.post("customers", data).parsed_response
```

> JSON response example:

```json
{
  "id": 25,
  "date_created": "2017-03-21T16:09:28",
  "date_created_gmt": "2017-03-21T19:09:28",
  "date_modified": "2017-03-21T16:09:30",
  "date_modified_gmt": "2017-03-21T19:09:30",
  "email": "john.doe@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "role": "customer",
  "username": "john.doe",
  "billing": {
    "first_name": "John",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US",
    "email": "john.doe@example.com",
    "phone": "(555) 555-5555"
  },
  "shipping": {
    "first_name": "John",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US"
  },
  "is_paying_customer": false,
  "avatar_url": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96",
  "meta_data": [],
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers/25"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers"
      }
    ]
  }
}
```

## Retrieve a customer ##

This API lets you retrieve and view a specific customer by ID.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/customers/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/customers/25 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("customers/25")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('customers/25')); ?>
```

```python
print(wcapi.get("customers/25").json())
```

```ruby
woocommerce.get("customers/25").parsed_response
```

> JSON response example:

```json
{
  "id": 25,
  "date_created": "2017-03-21T16:09:28",
  "date_created_gmt": "2017-03-21T19:09:28",
  "date_modified": "2017-03-21T16:09:30",
  "date_modified_gmt": "2017-03-21T19:09:30",
  "email": "john.doe@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "role": "customer",
  "username": "john.doe",
  "billing": {
    "first_name": "John",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US",
    "email": "john.doe@example.com",
    "phone": "(555) 555-5555"
  },
  "shipping": {
    "first_name": "John",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US"
  },
  "is_paying_customer": false,
  "avatar_url": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96",
  "meta_data": [],
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers/25"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers"
      }
    ]
  }
}
```

## List all customers ##

This API helps you to view all the customers.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/customers</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/customers \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("customers")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('customers')); ?>
```

```python
print(wcapi.get("customers").json())
```

```ruby
woocommerce.get("customers").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 26,
    "date_created": "2017-03-21T16:11:14",
    "date_created_gmt": "2017-03-21T19:11:14",
    "date_modified": "2017-03-21T16:11:16",
    "date_modified_gmt": "2017-03-21T19:11:16",
    "email": "joao.silva@example.com",
    "first_name": "João",
    "last_name": "Silva",
    "role": "customer",
    "username": "joao.silva",
    "billing": {
      "first_name": "João",
      "last_name": "Silva",
      "company": "",
      "address_1": "Av. Brasil, 432",
      "address_2": "",
      "city": "Rio de Janeiro",
      "state": "RJ",
      "postcode": "12345-000",
      "country": "BR",
      "email": "joao.silva@example.com",
      "phone": "(55) 5555-5555"
    },
    "shipping": {
      "first_name": "João",
      "last_name": "Silva",
      "company": "",
      "address_1": "Av. Brasil, 432",
      "address_2": "",
      "city": "Rio de Janeiro",
      "state": "RJ",
      "postcode": "12345-000",
      "country": "BR"
    },
    "is_paying_customer": false,
    "avatar_url": "https://secure.gravatar.com/avatar/be7b5febff88a2d947c3289e90cdf017?s=96",
    "meta_data": [],
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/customers/26"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/customers"
        }
      ]
    }
  },
  {
    "id": 25,
    "date_created": "2017-03-21T16:09:28",
    "date_created_gmt": "2017-03-21T19:09:28",
    "date_modified": "2017-03-21T16:09:30",
    "date_modified_gmt": "2017-03-21T19:09:30",
    "email": "john.doe@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": "customer",
    "username": "john.doe",
    "billing": {
      "first_name": "John",
      "last_name": "Doe",
      "company": "",
      "address_1": "969 Market",
      "address_2": "",
      "city": "San Francisco",
      "state": "CA",
      "postcode": "94103",
      "country": "US",
      "email": "john.doe@example.com",
      "phone": "(555) 555-5555"
    },
    "shipping": {
      "first_name": "John",
      "last_name": "Doe",
      "company": "",
      "address_1": "969 Market",
      "address_2": "",
      "city": "San Francisco",
      "state": "CA",
      "postcode": "94103",
      "country": "US"
    },
    "is_paying_customer": false,
    "avatar_url": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96",
    "meta_data": [],
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/customers/25"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/customers"
        }
      ]
    }
  }
]
```

#### Available parameters ####

| Parameter  | Type    | Description                                                                                                                                                                                 |
| ---------- | ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `context`  | string  | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`.                                                                |
| `page`     | integer | Current page of the collection. Default is `1`.                                                                                                                                             |
| `per_page` | integer | Maximum number of items to be returned in result set. Default is `10`.                                                                                                                      |
| `search`   | string  | Limit results to those matching a string.                                                                                                                                                   |
| `exclude`  | array   | Ensure result set excludes specific IDs.                                                                                                                                                    |
| `include`  | array   | Limit result set to specific IDs.                                                                                                                                                           |
| `offset`   | integer | Offset the result set by a specific number of items.                                                                                                                                        |
| `order`    | string  | Order sort attribute ascending or descending. Options: `asc` and `desc`. Default is `asc`.                                                                                                  |
| `orderby`  | string  | Sort collection by object attribute. Options: `id`, `include`, `name` and `registered_date`. Default is `name`.                                                                             |
| `email`    | string  | Limit result set to resources with a specific email.                                                                                                                                        |
| `role`     | string  | Limit result set to resources with a specific role. Options: `all`, `administrator`, `editor`, `author`, `contributor`, `subscriber`, `customer` and `shop_manager`. Default is `customer`. |

## Update a customer ##

This API lets you make changes to a customer.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/customers/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/customers/25 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "first_name": "James",
  "billing": {
    "first_name": "James"
  },
  "shipping": {
    "first_name": "James"
  }
}'
```

```javascript
const data = {
  first_name: "James",
  billing: {
    first_name: "James"
  },
  shipping: {
    first_name: "James"
  }
};

WooCommerce.put("customers/25", data)
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
    'first_name' => 'James',
    'billing' => [
        'first_name' => 'James'
    ],
    'shipping' => [
        'first_name' => 'James'
    ]
];

print_r($woocommerce->put('customers/25', $data));
?>
```

```python
data = {
    "first_name": "James",
    "billing": {
        "first_name": "James"
    },
    "shipping": {
        "first_name": "James"
    }
}

print(wcapi.put("customers/25", data).json())
```

```ruby
data = {
  first_name: "James",
  billing: {
    first_name: "James"
  },
  shipping: {
    first_name: "James"
  }
}

woocommerce.put("customers/25", data).parsed_response
```

> JSON response example:

```json
{
  "id": 25,
  "date_created": "2017-03-21T16:09:28",
  "date_created_gmt": "2017-03-21T19:09:28",
  "date_modified": "2017-03-21T16:12:28",
  "date_modified_gmt": "2017-03-21T19:12:28",
  "email": "john.doe@example.com",
  "first_name": "James",
  "last_name": "Doe",
  "role": "customer",
  "username": "john.doe",
  "billing": {
    "first_name": "James",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US",
    "email": "john.doe@example.com",
    "phone": "(555) 555-5555"
  },
  "shipping": {
    "first_name": "James",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US"
  },
  "is_paying_customer": false,
  "avatar_url": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96",
  "meta_data": [],
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers/25"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers"
      }
    ]
  }
}
```

## Delete a customer ##

This API helps you delete a customer.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/customers/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/customers/25?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("customers/25", {
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
<?php print_r($woocommerce->delete('customers/25', ['force' => true])); ?>
```

```python
print(wcapi.delete("customers/25", params={"force": True}).json())
```

```ruby
woocommerce.delete("customers/25", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 25,
  "date_created": "2017-03-21T16:09:28",
  "date_created_gmt": "2017-03-21T19:09:28",
  "date_modified": "2017-03-21T16:12:28",
  "date_modified_gmt": "2017-03-21T19:12:28",
  "email": "john.doe@example.com",
  "first_name": "James",
  "last_name": "Doe",
  "role": "customer",
  "username": "john.doe",
  "billing": {
    "first_name": "James",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US",
    "email": "john.doe@example.com",
    "phone": "(555) 555-5555"
  },
  "shipping": {
    "first_name": "James",
    "last_name": "Doe",
    "company": "",
    "address_1": "969 Market",
    "address_2": "",
    "city": "San Francisco",
    "state": "CA",
    "postcode": "94103",
    "country": "US"
  },
  "is_paying_customer": false,
  "avatar_url": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96",
  "meta_data": [],
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers/25"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/customers"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter  |   Type  |                          Description                          |
|------------|---------|---------------------------------------------------------------|
| `force`    | string  | Required to be `true`, as resource does not support trashing. |
| `reassign` | integer | User ID to reassign posts to.                                 |

## Batch update customers ##

This API helps you to batch create, update and delete multiple customers.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/customers/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/customers/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "create": [
    {
      "email": "john.doe2@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "username": "john.doe2",
      "billing": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US",
        "email": "john.doe@example.com",
        "phone": "(555) 555-5555"
      },
      "shipping": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US"
      }
    },
    {
      "email": "joao.silva2@example.com",
      "first_name": "João",
      "last_name": "Silva",
      "username": "joao.silva2",
      "billing": {
        "first_name": "João",
        "last_name": "Silva",
        "company": "",
        "address_1": "Av. Brasil, 432",
        "address_2": "",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "postcode": "12345-000",
        "country": "BR",
        "email": "joao.silva@example.com",
        "phone": "(55) 5555-5555"
      },
      "shipping": {
        "first_name": "João",
        "last_name": "Silva",
        "company": "",
        "address_1": "Av. Brasil, 432",
        "address_2": "",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "postcode": "12345-000",
        "country": "BR"
      }
    }
  ],
  "update": [
    {
      "id": 26,
      "billing": {
        "phone": "(11) 1111-1111"
      }
    }
  ],
  "delete": [
    25
  ]
}'
```

```javascript
const data = {
  create: [
    {
      email: "john.doe2@example.com",
      first_name: "John",
      last_name: "Doe",
      username: "john.doe2",
      billing: {
        first_name: "John",
        last_name: "Doe",
        company: "",
        address_1: "969 Market",
        address_2: "",
        city: "San Francisco",
        state: "CA",
        postcode: "94103",
        country: "US",
        email: "john.doe@example.com",
        phone: "(555) 555-5555"
      },
      shipping: {
        first_name: "John",
        last_name: "Doe",
        company: "",
        address_1: "969 Market",
        address_2: "",
        city: "San Francisco",
        state: "CA",
        postcode: "94103",
        country: "US"
      }
    },
    {
      email: "joao.silva2@example.com",
      first_name: "João",
      last_name: "Silva",
      username: "joao.silva2",
      billing: {
        first_name: "João",
        last_name: "Silva",
        company: "",
        address_1: "Av. Brasil, 432",
        address_2: "",
        city: "Rio de Janeiro",
        state: "RJ",
        postcode: "12345-000",
        country: "BR",
        email: "joao.silva@example.com",
        phone: "(55) 5555-5555"
      },
      shipping: {
        first_name: "João",
        last_name: "Silva",
        company: "",
        address_1: "Av. Brasil, 432",
        address_2: "",
        city: "Rio de Janeiro",
        state: "RJ",
        postcode: "12345-000",
        country: "BR"
      }
    }
  ],
  update: [
    {
      id: 26,
      billing: {
        phone: "(11) 1111-1111"
      }
    }
  ],
  delete: [
    11
  ]
};

WooCommerce.post("customers/batch", data)
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
            'email' => 'john.doe2@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'john.doe2',
            'billing' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => '',
                'address_1' => '969 Market',
                'address_2' => '',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postcode' => '94103',
                'country' => 'US',
                'email' => 'john.doe@example.com',
                'phone' => '(555) 555-5555'
            ],
            'shipping' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => '',
                'address_1' => '969 Market',
                'address_2' => '',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postcode' => '94103',
                'country' => 'US'
            ]
        ],
        [
            'email' => 'joao.silva2@example.com',
            'first_name' => 'João',
            'last_name' => 'Silva',
            'username' => 'joao.silva2',
            'billing' => [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'company' => '',
                'address_1' => 'Av. Brasil, 432',
                'address_2' => '',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'postcode' => '12345-000',
                'country' => 'BR',
                'email' => 'joao.silva@example.com',
                'phone' => '(55) 5555-5555'
            ],
            'shipping' => [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'company' => '',
                'address_1' => 'Av. Brasil, 432',
                'address_2' => '',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'postcode' => '12345-000',
                'country' => 'BR'
            ]
        ]
    ],
    'update' => [
        [
            'id' => 26,
            'billing' => [
                'phone' => '(11) 1111-1111'
            ]
        ]
    ],
    'delete' => [
        25
    ]
];

print_r($woocommerce->post('customers/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "email": "john.doe2@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "username": "john.doe2",
            "billing": {
                "first_name": "John",
                "last_name": "Doe",
                "company": "",
                "address_1": "969 Market",
                "address_2": "",
                "city": "San Francisco",
                "state": "CA",
                "postcode": "94103",
                "country": "US",
                "email": "john.doe@example.com",
                "phone": "(555) 555-5555"
            },
            "shipping": {
                "first_name": "John",
                "last_name": "Doe",
                "company": "",
                "address_1": "969 Market",
                "address_2": "",
                "city": "San Francisco",
                "state": "CA",
                "postcode": "94103",
                "country": "US"
            }
        },
        {
            "email": "joao.silva2@example.com",
            "first_name": "João",
            "last_name": "Silva",
            "username": "joao.silva2",
            "billing": {
                "first_name": "João",
                "last_name": "Silva",
                "company": "",
                "address_1": "Av. Brasil, 432",
                "address_2": "",
                "city": "Rio de Janeiro",
                "state": "RJ",
                "postcode": "12345-000",
                "country": "BR",
                "email": "joao.silva@example.com",
                "phone": "(55) 5555-5555"
            },
            "shipping": {
                "first_name": "João",
                "last_name": "Silva",
                "company": "",
                "address_1": "Av. Brasil, 432",
                "address_2": "",
                "city": "Rio de Janeiro",
                "state": "RJ",
                "postcode": "12345-000",
                "country": "BR"
            }
        }
    ],
    "update": [
        {
            "id": 26,
            "billing": {
                "phone": "(11) 1111-1111"
            }
        }
    ],
    "delete": [
        25
    ]
}

print(wcapi.post("customers/batch", data).json())
```

```ruby
data = {
  create: [
    {
      email: "john.doe2@example.com",
      first_name: "John",
      last_name: "Doe",
      username: "john.doe2",
      billing: {
        first_name: "John",
        last_name: "Doe",
        company: "",
        address_1: "969 Market",
        address_2: "",
        city: "San Francisco",
        state: "CA",
        postcode: "94103",
        country: "US",
        email: "john.doe@example.com",
        phone: "(555) 555-5555"
      },
      shipping: {
        first_name: "John",
        last_name: "Doe",
        company: "",
        address_1: "969 Market",
        address_2: "",
        city: "San Francisco",
        state: "CA",
        postcode: "94103",
        country: "US"
      }
    },
    {
      email: "joao.silva2@example.com",
      first_name: "João",
      last_name: "Silva",
      username: "joao.silva2",
      billing: {
        first_name: "João",
        last_name: "Silva",
        company: "",
        address_1: "Av. Brasil, 432",
        address_2: "",
        city: "Rio de Janeiro",
        state: "RJ",
        postcode: "12345-000",
        country: "BR",
        email: "joao.silva@example.com",
        phone: "(55) 5555-5555"
      },
      shipping: {
        first_name: "João",
        last_name: "Silva",
        company: "",
        address_1: "Av. Brasil, 432",
        address_2: "",
        city: "Rio de Janeiro",
        state: "RJ",
        postcode: "12345-000",
        country: "BR"
      }
    }
  ],
  update: [
    {
      id: 26,
      billing: {
        phone: "(11) 1111-1111"
      }
    }
  ],
  delete: [
    25
  ]
}

woocommerce.post("customers/batch", data).parsed_response
```

> JSON response example:

```json
{
  "create": [
    {
      "id": 27,
      "date_created": "2017-03-21T16:13:58",
      "date_created_gmt": "2017-03-21T19:13:58",
      "date_modified": "2017-03-21T16:13:59",
      "date_modified_gmt": "2017-03-21T19:13:59",
      "email": "john.doe2@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "customer",
      "username": "john.doe2",
      "billing": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US",
        "email": "john.doe@example.com",
        "phone": "(555) 555-5555"
      },
      "shipping": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US"
      },
      "is_paying_customer": false,
      "avatar_url": "https://secure.gravatar.com/avatar/6ad0b094bac53a85bb282ccdb3958279?s=96",
      "meta_data": [],
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers/27"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers"
          }
        ]
      }
    },
    {
      "id": 28,
      "date_created": "2017-03-21T16:14:00",
      "date_created_gmt": "2017-03-21T19:14:00",
      "date_modified": "2017-03-21T16:14:01",
      "date_modified_gmt": "2017-03-21T19:14:01",
      "email": "joao.silva2@example.com",
      "first_name": "João",
      "last_name": "Silva",
      "role": "customer",
      "username": "joao.silva2",
      "billing": {
        "first_name": "João",
        "last_name": "Silva",
        "company": "",
        "address_1": "Av. Brasil, 432",
        "address_2": "",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "postcode": "12345-000",
        "country": "BR",
        "email": "joao.silva@example.com",
        "phone": "(55) 5555-5555"
      },
      "shipping": {
        "first_name": "João",
        "last_name": "Silva",
        "company": "",
        "address_1": "Av. Brasil, 432",
        "address_2": "",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "postcode": "12345-000",
        "country": "BR"
      },
      "is_paying_customer": false,
      "avatar_url": "https://secure.gravatar.com/avatar/ea9ad095f2970f27cbff07e7f5e99453?s=96",
      "meta_data": [],
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers/28"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers"
          }
        ]
      }
    }
  ],
  "update": [
    {
      "id": 26,
      "date_created": "2017-03-21T16:11:14",
      "date_created_gmt": "2017-03-21T19:11:14",
      "date_modified": "2017-03-21T16:14:03",
      "date_modified_gmt": "2017-03-21T19:14:03",
      "email": "joao.silva@example.com",
      "first_name": "João",
      "last_name": "Silva",
      "role": "customer",
      "username": "joao.silva",
      "billing": {
        "first_name": "João",
        "last_name": "Silva",
        "company": "",
        "address_1": "Av. Brasil, 432",
        "address_2": "",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "postcode": "12345-000",
        "country": "BR",
        "email": "joao.silva@example.com",
        "phone": "(11) 1111-1111"
      },
      "shipping": {
        "first_name": "João",
        "last_name": "Silva",
        "company": "",
        "address_1": "Av. Brasil, 432",
        "address_2": "",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "postcode": "12345-000",
        "country": "BR"
      },
      "is_paying_customer": false,
      "avatar_url": "https://secure.gravatar.com/avatar/be7b5febff88a2d947c3289e90cdf017?s=96",
      "meta_data": [],
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers/26"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers"
          }
        ]
      }
    }
  ],
  "delete": [
    {
      "id": 25,
      "date_created": "2017-03-21T16:09:28",
      "date_created_gmt": "2017-03-21T19:09:28",
      "date_modified": "2017-03-21T16:12:28",
      "date_modified_gmt": "2017-03-21T19:12:28",
      "email": "john.doe@example.com",
      "first_name": "James",
      "last_name": "Doe",
      "role": "customer",
      "username": "john.doe",
      "billing": {
        "first_name": "James",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US",
        "email": "john.doe@example.com",
        "phone": "(555) 555-5555"
      },
      "shipping": {
        "first_name": "James",
        "last_name": "Doe",
        "company": "",
        "address_1": "969 Market",
        "address_2": "",
        "city": "San Francisco",
        "state": "CA",
        "postcode": "94103",
        "country": "US"
      },
      "is_paying_customer": false,
      "avatar_url": "https://secure.gravatar.com/avatar/8eb1b522f60d11fa897de1dc6351b7e8?s=96",
      "meta_data": [],
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers/25"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/customers"
          }
        ]
      }
    }
  ]
}
```

## Retrieve customer downloads ##

This API lets you retrieve customer downloads permissions.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/customers/&lt;id&gt;/downloads</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/customers/26/downloads \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("customers/26/downloads")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('customers/26/downloads')); ?>
```

```python
print(wcapi.get("customers/26/downloads").json())
```

```ruby
woocommerce.get("customers/26/downloads").parsed_response
```

> JSON response example:

```json
[
  {
    "download_id": "91447fd1849316bbc89dfb7e986a6006",
    "download_url": "https://example.com/?download_file=87&order=wc_order_58d17c18352&email=joao.silva%40example.com&key=91447fd1849316bbc89dfb7e986a6006",
    "product_id": 87,
    "product_name": "Woo Album #2",
    "download_name": "Woo Album #2 &ndash; Song 2",
    "order_id": 723,
    "order_key": "wc_order_58d17c18352",
    "downloads_remaining": "3",
    "access_expires": "never",
    "access_expires_gmt": "never",
    "file": {
      "name": "Song 2",
      "file": "http://example.com/wp-content/uploads/woocommerce_uploads/2013/06/Song.mp3"
    },
    "_links": {
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/customers/26/downloads"
        }
      ],
      "product": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/87"
        }
      ],
      "order": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723"
        }
      ]
    }
  }
]
```

### Customer downloads properties ###

| Attribute             | Type    | Description                                                                                                                               |
| --------------------- | ------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `download_id`         | string  | Download ID (MD5). <i class="label label-info">read-only</i>                                                                              |
| `download_url`        | string  | Download file URL. <i class="label label-info">read-only</i>                                                                              |
| `product_id`          | integer | Downloadable product ID. <i class="label label-info">read-only</i>                                                                        |
| `product_name`        | string  | Product name. <i class="label label-info">read-only</i>                                                                                   |
| `download_name`       | string  | Downloadable file name. <i class="label label-info">read-only</i>                                                                         |
| `order_id`            | integer | Order ID. <i class="label label-info">read-only</i>                                                                                       |
| `order_key`           | string  | Order key. <i class="label label-info">read-only</i>                                                                                      |
| `downloads_remaining` | string  | Number of downloads remaining. <i class="label label-info">read-only</i>                                                                  |
| `access_expires`      | string  | The date when download access expires, in the site's timezone. <i class="label label-info">read-only</i>                                  |
| `access_expires_gmt`  | string  | The date when download access expires, as GMT. <i class="label label-info">read-only</i>                                                  |
| `file`                | object  | File details. <i class="label label-info">read-only</i> See [Customers downloads - File properties](#customers-downloads-file-properties) |

#### Customer downloads - File properties ####

| Attribute | Type   | Description                                          |
| --------- | ------ | ---------------------------------------------------- |
| `name`    | string | File name. <i class="label label-info">read-only</i> |
| `file`    | string | File URL. <i class="label label-info">read-only</i>  |
