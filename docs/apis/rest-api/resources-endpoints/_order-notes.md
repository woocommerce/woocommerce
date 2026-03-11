# Order notes #

The order notes API allows you to create, view, and delete individual order notes.  
Order notes are added by administrators and programmatically to store data about an order, or order events.

## Order note properties ##

| Attribute          | Type      | Description                                                                                                                                      |
|--------------------|-----------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`               | integer   | Unique identifier for the resource. <i class="label label-info">read-only</i>                                                                    |
| `author`           | string    | Order note author. <i class="label label-info">read-only</i>                                                                                     |
| `date_created`     | date-time | The date the order note was created, in the site's timezone. <i class="label label-info">read-only</i>                                           |
| `date_created_gmt` | date-time | The date the order note was created, as GMT. <i class="label label-info">read-only</i>                                                           |
| `note`             | string    | Order note content. <i class="label label-info">mandatory</i>                                                                                    |
| `customer_note`    | boolean   | If true, the note will be shown to customers and they will be notified. If false, the note will be for admin reference only. Default is `false`. |
| `added_by_user`    | boolean   | If true, this note will be attributed to the current user. If false, the note will be attributed to the system. Default is `false`.              |

## Create an order note ##

This API helps you to create a new note for an order.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/notes</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/orders/723/notes \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "note": "Order ok!!!"
}'
```

```javascript
const data = {
  note: "Order ok!!!"
};

WooCommerce.post("orders/723/notes", data)
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
    'note' => 'Order ok!!!'
];

print_r($woocommerce->post('orders/723/notes', $data));
?>
```

```python
data = {
    "note": "Order ok!!!"
}

print(wcapi.post("orders/723/notes", data).json())
```

```ruby
data = {
  note: "Order ok!!!"
}

woocommerce.post("orders/723/notes", data).parsed_response
```

> JSON response example:

```json
{
  "id": 281,
  "author": "system",
  "date_created": "2017-03-21T16:46:41",
  "date_created_gmt": "2017-03-21T19:46:41",
  "note": "Order ok!!!",
  "customer_note": false,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723/notes/281"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723/notes"
      }
    ],
    "up": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723"
      }
    ]
  }
}
```

## Retrieve an order note ##

This API lets you retrieve and view a specific note from an order.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/notes/&lt;note_id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/orders/723/notes/281 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("orders/723/notes/281")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('orders/723/notes/281')); ?>
```

```python
print(wcapi.get("orders/723/notes/281").json())
```

```ruby
woocommerce.get("orders/723/notes/281").parsed_response
```

> JSON response example:

```json
{
  "id": 281,
  "author": "system",
  "date_created": "2017-03-21T16:46:41",
  "date_created_gmt": "2017-03-21T19:46:41",
  "note": "Order ok!!!",
  "customer_note": false,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723/notes/281"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723/notes"
      }
    ],
    "up": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723"
      }
    ]
  }
}
```

## List all order notes ##

This API helps you to view all the notes from an order.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/notes</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/orders/723/notes \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("orders/723/notes")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('orders/723/notes')); ?>
```

```python
print(wcapi.get("orders/723/notes").json())
```

```ruby
woocommerce.get("orders/723/notes").parsed_response
```

> JSON response example:

```json
[
  {
    "id": 281,
    "author": "system",
    "date_created": "2017-03-21T16:46:41",
    "date_created_gmt": "2017-03-21T19:46:41",
    "note": "Order ok!!!",
    "customer_note": false,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723/notes/281"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723/notes"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723"
        }
      ]
    }
  },
  {
    "id": 280,
    "author": "system",
    "date_created": "2017-03-21T16:16:58",
    "date_created_gmt": "2017-03-21T19:16:58",
    "note": "Order status changed from On hold to Completed.",
    "customer_note": false,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723/notes/280"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723/notes"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723"
        }
      ]
    }
  },
  {
    "id": 279,
    "author": "system",
    "date_created": "2017-03-21T16:16:46",
    "date_created_gmt": "2017-03-21T19:16:46",
    "note": "Awaiting BACS payment Order status changed from Pending payment to On hold.",
    "customer_note": false,
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723/notes/279"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723/notes"
        }
      ],
      "up": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/723"
        }
      ]
    }
  }
]
```

#### Available parameters ####

| Parameter | Type   | Description                                                                                                                  |
|-----------|--------|------------------------------------------------------------------------------------------------------------------------------|
| `context` | string | Scope under which the request is made; determines fields present in response. Options: `view` and `edit`. Default is `view`. |
| `type`    | string | Limit result to customers or internal notes. Options: `any`, `customer` and `internal`. Default is `any`.                    |

## Delete an order note ##

This API helps you delete an order note.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/orders/&lt;id&gt;/notes/&lt;note_id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/orders/723/notes/281?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("orders/723/notes/281", {
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
<?php print_r($woocommerce->delete('orders/723/notes/281', ['force' => true])); ?>
```

```python
print(wcapi.delete("orders/723/notes/281", params={"force": True}).json())
```

```ruby
woocommerce.delete("orders/723/notes/281", force: true).parsed_response
```

> JSON response example:

```json
{
  "id": 281,
  "author": "system",
  "date_created": "2017-03-21T16:46:41",
  "date_created_gmt": "2017-03-21T19:46:41",
  "note": "Order ok!!!",
  "customer_note": false,
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723/notes/281"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723/notes"
      }
    ],
    "up": [
      {
        "href": "https://example.com/wp-json/wc/v3/orders/723"
      }
    ]
  }
}
```
#### Available parameters ####

| Parameter | Type   | Description                                                   |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |
