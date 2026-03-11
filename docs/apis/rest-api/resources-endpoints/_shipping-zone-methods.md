# Shipping zone methods #

The shipping zone methods API allows you to create, view, update, and delete individual methods of a shipping zone.

## Shipping method properties ##

| Attribute            | Type    | Description                                                                                                 |
| -------------------- | ------- | ----------------------------------------------------------------------------------------------------------- |
| `instance_id`        | integer | Shipping method instance ID. <i class="label label-info">read-only</i>                                      |
| `title`              | string  | Shipping method customer facing title. <i class="label label-info">read-only</i>                            |
| `order`              | integer | Shipping method sort order.                                                                                 |
| `enabled`            | boolean | Shipping method enabled status.                                                                             |
| `method_id`          | string  | Shipping method ID. <i class="label label-info">read-only</i> <i class="label label-info">mandatory</i>     |
| `method_title`       | string  | Shipping method title. <i class="label label-info">read-only</i>                                            |
| `method_description` | string  | Shipping method description. <i class="label label-info">read-only</i>                                      |
| `settings`           | object  | Shipping method settings. See [Shipping method - Settings properties](#shipping-method-settings-properties) |

### Shipping method - Settings properties ###

| Attribute     | Type   | Description                                                                                                                                                                                     |
| ------------- | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`          | string | A unique identifier for the setting. <i class="label label-info">read-only</i>                                                                                                                  |
| `label`       | string | A human readable label for the setting used in interfaces. <i class="label label-info">read-only</i>                                                                                            |
| `description` | string | A human readable description for the setting used in interfaces. <i class="label label-info">read-only</i>                                                                                      |
| `type`        | string | Type of setting. Options: `text`, `email`, `number`, `color`, `password`, `textarea`, `select`, `multiselect`, `radio`, `image_width` and `checkbox`. <i class="label label-info">read-only</i> |
| `value`       | string | Setting value.                                                                                                                                                                                  |
| `default`     | string | Default value for the setting. <i class="label label-info">read-only</i>                                                                                                                        |
| `tip`         | string | Additional help text shown to the user about the setting. <i class="label label-info">read-only</i>                                                                                             |
| `placeholder` | string | Placeholder text to be displayed in text inputs. <i class="label label-info">read-only</i>                                                                                                      |

## Include a shipping method to a shipping zone ##

This API helps you to create a new shipping method to a shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;id&gt;/methods</h6>
	</div>
</div>

> JSON response example:

```shell
curl -X POST https://example.com/wp-json/wc/v3/shipping/zones/5/methods \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "method_id": "flat_rate"
}'
```

```javascript
const data = {
  method_id: "flat_rate"
};

WooCommerce.post("shipping/zones/5/methods", data)
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
    'method_id' => 'flat_rate'
];

print_r($woocommerce->post('shipping/zones/5/methods', $data));
?>
```

```python
data = {
    "method_id": "flat_rate"
}

print(wcapi.post("shipping/zones/5/methods", data).json())
```

```ruby
data = {
  method_id: "flat_rate"
}

woocommerce.post("shipping/zones/5/methods", data).parsed_response
```

> JSON response example:

```json
{
  "instance_id": 26,
  "title": "Flat rate",
  "order": 1,
  "enabled": true,
  "method_id": "flat_rate",
  "method_title": "Flat rate",
  "method_description": "<p>Lets you charge a fixed rate for shipping.</p>\n",
  "settings": {
    "title": {
      "id": "title",
      "label": "Method title",
      "description": "This controls the title which the user sees during checkout.",
      "type": "text",
      "value": "Flat rate",
      "default": "Flat rate",
      "tip": "This controls the title which the user sees during checkout.",
      "placeholder": ""
    },
    "tax_status": {
      "id": "tax_status",
      "label": "Tax status",
      "description": "",
      "type": "select",
      "value": "taxable",
      "default": "taxable",
      "tip": "",
      "placeholder": "",
      "options": {
        "taxable": "Taxable",
        "none": "None"
      }
    },
    "cost": {
      "id": "cost",
      "label": "Cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "0",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": ""
    },
    "class_costs": {
      "id": "class_costs",
      "label": "Shipping class costs",
      "description": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "type": "title",
      "value": "",
      "default": "",
      "tip": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "placeholder": ""
    },
    "class_cost_92": {
      "id": "class_cost_92",
      "label": "\"Express\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "class_cost_91": {
      "id": "class_cost_91",
      "label": "\"Priority\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "no_class_cost": {
      "id": "no_class_cost",
      "label": "No shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "type": {
      "id": "type",
      "label": "Calculation type",
      "description": "",
      "type": "select",
      "value": "class",
      "default": "class",
      "tip": "",
      "placeholder": "",
      "options": {
        "class": "Per class: Charge shipping for each shipping class individually",
        "order": "Per order: Charge shipping for the most expensive shipping class"
      }
    }
  },
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods"
      }
    ],
    "describes": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ]
  }
}
```

## Retrieve a shipping method from a shipping zone ##

This API lets you retrieve and view a specific shipping method from a shipping zone by ID.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;zone_id&gt;/methods/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26 \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("shipping/zones/5/methods/26")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('shipping/zones/5/methods/26')); ?>
```

```python
print(wcapi.get("shipping/zones/5/methods/26").json())
```

```ruby
woocommerce.get("shipping/zones/5/methods/26").parsed_response
```

> JSON response example:

```json
{
  "instance_id": 26,
  "title": "Flat rate",
  "order": 1,
  "enabled": true,
  "method_id": "flat_rate",
  "method_title": "Flat rate",
  "method_description": "<p>Lets you charge a fixed rate for shipping.</p>\n",
  "settings": {
    "title": {
      "id": "title",
      "label": "Method title",
      "description": "This controls the title which the user sees during checkout.",
      "type": "text",
      "value": "Flat rate",
      "default": "Flat rate",
      "tip": "This controls the title which the user sees during checkout.",
      "placeholder": ""
    },
    "tax_status": {
      "id": "tax_status",
      "label": "Tax status",
      "description": "",
      "type": "select",
      "value": "taxable",
      "default": "taxable",
      "tip": "",
      "placeholder": "",
      "options": {
        "taxable": "Taxable",
        "none": "None"
      }
    },
    "cost": {
      "id": "cost",
      "label": "Cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "0",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": ""
    },
    "class_costs": {
      "id": "class_costs",
      "label": "Shipping class costs",
      "description": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "type": "title",
      "value": "",
      "default": "",
      "tip": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "placeholder": ""
    },
    "class_cost_92": {
      "id": "class_cost_92",
      "label": "\"Express\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "class_cost_91": {
      "id": "class_cost_91",
      "label": "\"Priority\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "no_class_cost": {
      "id": "no_class_cost",
      "label": "No shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "type": {
      "id": "type",
      "label": "Calculation type",
      "description": "",
      "type": "select",
      "value": "class",
      "default": "class",
      "tip": "",
      "placeholder": "",
      "options": {
        "class": "Per class: Charge shipping for each shipping class individually",
        "order": "Per order: Charge shipping for the most expensive shipping class"
      }
    }
  },
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods"
      }
    ],
    "describes": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ]
  }
}
```

## List all shipping methods from a shipping zone ##

This API helps you to view all the shipping methods from a shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;id&gt;/methods</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/shipping/zones/5/methods \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("shipping/zones/5/methods")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('shipping/zones/5/methods')); ?>
```

```python
print(wcapi.get("shipping/zones/5/methods").json())
```

```ruby
woocommerce.get("shipping/zones/5/methods").parsed_response
```

> JSON response example:

```json
[
  {
    "instance_id": 26,
    "title": "Flat rate",
    "order": 1,
    "enabled": true,
    "method_id": "flat_rate",
    "method_title": "Flat rate",
    "method_description": "<p>Lets you charge a fixed rate for shipping.</p>\n",
    "settings": {
      "title": {
        "id": "title",
        "label": "Method title",
        "description": "This controls the title which the user sees during checkout.",
        "type": "text",
        "value": "Flat rate",
        "default": "Flat rate",
        "tip": "This controls the title which the user sees during checkout.",
        "placeholder": ""
      },
      "tax_status": {
        "id": "tax_status",
        "label": "Tax status",
        "description": "",
        "type": "select",
        "value": "taxable",
        "default": "taxable",
        "tip": "",
        "placeholder": "",
        "options": {
          "taxable": "Taxable",
          "none": "None"
        }
      },
      "cost": {
        "id": "cost",
        "label": "Cost",
        "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "type": "text",
        "value": "0",
        "default": "",
        "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "placeholder": ""
      },
      "class_costs": {
        "id": "class_costs",
        "label": "Shipping class costs",
        "description": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
        "type": "title",
        "value": "",
        "default": "",
        "tip": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
        "placeholder": ""
      },
      "class_cost_92": {
        "id": "class_cost_92",
        "label": "\"Express\" shipping class cost",
        "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "type": "text",
        "value": "",
        "default": "",
        "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "placeholder": "N/A"
      },
      "class_cost_91": {
        "id": "class_cost_91",
        "label": "\"Priority\" shipping class cost",
        "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "type": "text",
        "value": "",
        "default": "",
        "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "placeholder": "N/A"
      },
      "no_class_cost": {
        "id": "no_class_cost",
        "label": "No shipping class cost",
        "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "type": "text",
        "value": "",
        "default": "",
        "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
        "placeholder": "N/A"
      },
      "type": {
        "id": "type",
        "label": "Calculation type",
        "description": "",
        "type": "select",
        "value": "class",
        "default": "class",
        "tip": "",
        "placeholder": "",
        "options": {
          "class": "Per class: Charge shipping for each shipping class individually",
          "order": "Per order: Charge shipping for the most expensive shipping class"
        }
      }
    },
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods"
        }
      ],
      "describes": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
        }
      ]
    }
  },
  {
    "instance_id": 27,
    "title": "Free shipping",
    "order": 2,
    "enabled": true,
    "method_id": "free_shipping",
    "method_title": "Free shipping",
    "method_description": "<p>Free shipping is a special method which can be triggered with coupons and minimum spends.</p>\n",
    "settings": {
      "title": {
        "id": "title",
        "label": "Title",
        "description": "This controls the title which the user sees during checkout.",
        "type": "text",
        "value": "Free shipping",
        "default": "Free shipping",
        "tip": "This controls the title which the user sees during checkout.",
        "placeholder": ""
      },
      "requires": {
        "id": "requires",
        "label": "Free shipping requires...",
        "description": "",
        "type": "select",
        "value": "",
        "default": "",
        "tip": "",
        "placeholder": "",
        "options": {
          "": "N/A",
          "coupon": "A valid free shipping coupon",
          "min_amount": "A minimum order amount",
          "either": "A minimum order amount OR a coupon",
          "both": "A minimum order amount AND a coupon"
        }
      },
      "min_amount": {
        "id": "min_amount",
        "label": "Minimum order amount",
        "description": "Users will need to spend this amount to get free shipping (if enabled above).",
        "type": "price",
        "value": "0",
        "default": "",
        "tip": "Users will need to spend this amount to get free shipping (if enabled above).",
        "placeholder": ""
      }
    },
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods/27"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods"
        }
      ],
      "describes": [
        {
          "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
        }
      ]
    }
  }
]
```

## Update a shipping method of a shipping zone ##

This API lets you make changes to a shipping method of a shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;zone_id&gt;/methods/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26 \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "settings": {
    "cost": "20.00"
  }
}'
```

```javascript
const data = {
  settings: {
    cost: "20.00"
  }
};

WooCommerce.put("shipping/zones/5/methods/26", data)
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
    'settings' => [
        'cost' => '20.00'
    ]
];

print_r($woocommerce->put('shipping/zones/5/methods/26', $data));
?>
```

```python
data = {
    "settings": {
        "cost": "20.00"
    }
}

print(wcapi.put("shipping/zones/5/methods/26", data).json())
```

```ruby
data = {
  settings: {
    "cost": "20.00"
  }
}

woocommerce.put("shipping/zones/5/methods/26", data).parsed_response
```

> JSON response example:

```json
{
  "instance_id": 26,
  "title": "Flat rate",
  "order": 1,
  "enabled": true,
  "method_id": "flat_rate",
  "method_title": "Flat rate",
  "method_description": "<p>Lets you charge a fixed rate for shipping.</p>\n",
  "settings": {
    "title": {
      "id": "title",
      "label": "Method title",
      "description": "This controls the title which the user sees during checkout.",
      "type": "text",
      "value": "Flat rate",
      "default": "Flat rate",
      "tip": "This controls the title which the user sees during checkout.",
      "placeholder": ""
    },
    "tax_status": {
      "id": "tax_status",
      "label": "Tax status",
      "description": "",
      "type": "select",
      "value": "taxable",
      "default": "taxable",
      "tip": "",
      "placeholder": "",
      "options": {
        "taxable": "Taxable",
        "none": "None"
      }
    },
    "cost": {
      "id": "cost",
      "label": "Cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "20.00",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": ""
    },
    "class_costs": {
      "id": "class_costs",
      "label": "Shipping class costs",
      "description": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "type": "title",
      "value": "",
      "default": "",
      "tip": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "placeholder": ""
    },
    "class_cost_92": {
      "id": "class_cost_92",
      "label": "\"Express\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "class_cost_91": {
      "id": "class_cost_91",
      "label": "\"Priority\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "no_class_cost": {
      "id": "no_class_cost",
      "label": "No shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "type": {
      "id": "type",
      "label": "Calculation type",
      "description": "",
      "type": "select",
      "value": "class",
      "default": "class",
      "tip": "",
      "placeholder": "",
      "options": {
        "class": "Per class: Charge shipping for each shipping class individually",
        "order": "Per order: Charge shipping for the most expensive shipping class"
      }
    }
  },
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods"
      }
    ],
    "describes": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ]
  }
}
```

## Delete a shipping method from a shipping zone ##

This API helps you delete a shipping method from a shipping zone.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-delete">DELETE</i>
		<h6>/wp-json/wc/v3/shipping/zones/&lt;zone_id&gt;/methods/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X DELETE https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26?force=true \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.delete("shipping/zones/5/methods/26", {
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
<?php print_r($woocommerce->delete('shipping/zones/5/methods/26', ['force' => true])); ?>
```

```python
print(wcapi.delete("shipping/zones/5/methods/26", params={"force": True}).json())
```

```ruby
woocommerce.delete("shipping/zones/5/methods/26", force: true).parsed_response
```

> JSON response example:

```json
{
  "instance_id": 26,
  "title": "Flat rate",
  "order": 1,
  "enabled": true,
  "method_id": "flat_rate",
  "method_title": "Flat rate",
  "method_description": "<p>Lets you charge a fixed rate for shipping.</p>\n",
  "settings": {
    "title": {
      "id": "title",
      "label": "Method title",
      "description": "This controls the title which the user sees during checkout.",
      "type": "text",
      "value": "Flat rate",
      "default": "Flat rate",
      "tip": "This controls the title which the user sees during checkout.",
      "placeholder": ""
    },
    "tax_status": {
      "id": "tax_status",
      "label": "Tax status",
      "description": "",
      "type": "select",
      "value": "taxable",
      "default": "taxable",
      "tip": "",
      "placeholder": "",
      "options": {
        "taxable": "Taxable",
        "none": "None"
      }
    },
    "cost": {
      "id": "cost",
      "label": "Cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "20.00",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": ""
    },
    "class_costs": {
      "id": "class_costs",
      "label": "Shipping class costs",
      "description": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "type": "title",
      "value": "",
      "default": "",
      "tip": "These costs can optionally be added based on the <a href=\"https://example.com/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes\">product shipping class</a>.",
      "placeholder": ""
    },
    "class_cost_92": {
      "id": "class_cost_92",
      "label": "\"Express\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "class_cost_91": {
      "id": "class_cost_91",
      "label": "\"Priority\" shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "no_class_cost": {
      "id": "no_class_cost",
      "label": "No shipping class cost",
      "description": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "type": "text",
      "value": "",
      "default": "",
      "tip": "Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.<br/><br/>Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent=\"10\" min_fee=\"20\" max_fee=\"\"]</code> for percentage based fees.",
      "placeholder": "N/A"
    },
    "type": {
      "id": "type",
      "label": "Calculation type",
      "description": "",
      "type": "select",
      "value": "class",
      "default": "class",
      "tip": "",
      "placeholder": "",
      "options": {
        "class": "Per class: Charge shipping for each shipping class individually",
        "order": "Per order: Charge shipping for the most expensive shipping class"
      }
    }
  },
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods/26"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5/methods"
      }
    ],
    "describes": [
      {
        "href": "https://example.com/wp-json/wc/v3/shipping/zones/5"
      }
    ]
  }
}
```

#### Available parameters ####

| Parameter |  Type  |                          Description                          |
|-----------|--------|---------------------------------------------------------------|
| `force`   | string | Required to be `true`, as resource does not support trashing. |
