# Settings #

The settings API allows you to view all groups of settings available.

## Setting group properties ##

| Attribute     | Type   | Description                                                                                                |
|---------------|--------|------------------------------------------------------------------------------------------------------------|
| `id`          | string | A unique identifier that can be used to link settings together. <i class="label label-info">read-only</i>  |
| `label`       | string | A human readable label for the setting used in interfaces. <i class="label label-info">read-only</i>       |
| `description` | string | A human readable description for the setting used in interfaces. <i class="label label-info">read-only</i> |
| `parent_id`   | string | ID of parent grouping. <i class="label label-info">read-only</i>                                           |
| `sub_groups`  | string | IDs for settings sub groups. <i class="label label-info">read-only</i>                                     |

## List all settings groups ##

This API helps you to view all the settings groups.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/settings</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/settings \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("settings")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('settings')); ?>
```

```python
print(wcapi.get("settings").json())
```

```ruby
woocommerce.get("settings").parsed_response
```

> JSON response example:

```json
[
  {
    "id": "general",
    "label": "General",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "products",
    "label": "Products",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/products"
        }
      ]
    }
  },
  {
    "id": "tax",
    "label": "Tax",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/tax"
        }
      ]
    }
  },
  {
    "id": "shipping",
    "label": "Shipping",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/shipping"
        }
      ]
    }
  },
  {
    "id": "checkout",
    "label": "Checkout",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/checkout"
        }
      ]
    }
  },
  {
    "id": "account",
    "label": "Accounts",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/account"
        }
      ]
    }
  },
  {
    "id": "email",
    "label": "Emails",
    "description": "",
    "parent_id": "",
    "sub_groups": [
      "email_new_order",
      "email_cancelled_order",
      "email_failed_order",
      "email_customer_on_hold_order",
      "email_customer_processing_order",
      "email_customer_completed_order",
      "email_customer_refunded_order",
      "email_customer_invoice",
      "email_customer_note",
      "email_customer_reset_password",
      "email_customer_new_account"
    ],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email"
        }
      ]
    }
  },
  {
    "id": "integration",
    "label": "Integration",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/integration"
        }
      ]
    }
  },
  {
    "id": "api",
    "label": "API",
    "description": "",
    "parent_id": "",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/api"
        }
      ]
    }
  },
  {
    "id": "email_new_order",
    "label": "New order",
    "description": "New order emails are sent to chosen recipient(s) when a new order is received.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_new_order"
        }
      ]
    }
  },
  {
    "id": "email_cancelled_order",
    "label": "Cancelled order",
    "description": "Cancelled order emails are sent to chosen recipient(s) when orders have been marked cancelled (if they were previously processing or on-hold).",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_cancelled_order"
        }
      ]
    }
  },
  {
    "id": "email_failed_order",
    "label": "Failed order",
    "description": "Failed order emails are sent to chosen recipient(s) when orders have been marked failed (if they were previously processing or on-hold).",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_failed_order"
        }
      ]
    }
  },
  {
    "id": "email_customer_on_hold_order",
    "label": "Order on-hold",
    "description": "This is an order notification sent to customers containing order details after an order is placed on-hold.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_on_hold_order"
        }
      ]
    }
  },
  {
    "id": "email_customer_processing_order",
    "label": "Processing order",
    "description": "This is an order notification sent to customers containing order details after payment.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_processing_order"
        }
      ]
    }
  },
  {
    "id": "email_customer_completed_order",
    "label": "Completed order",
    "description": "Order complete emails are sent to customers when their orders are marked completed and usually indicate that their orders have been shipped.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_completed_order"
        }
      ]
    }
  },
  {
    "id": "email_customer_refunded_order",
    "label": "Refunded order",
    "description": "Order refunded emails are sent to customers when their orders are marked refunded.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_refunded_order"
        }
      ]
    }
  },
  {
    "id": "email_customer_invoice",
    "label": "Customer invoice",
    "description": "Customer invoice emails can be sent to customers containing their order information and payment links.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_invoice"
        }
      ]
    }
  },
  {
    "id": "email_customer_note",
    "label": "Customer note",
    "description": "Customer note emails are sent when you add a note to an order.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_note"
        }
      ]
    }
  },
  {
    "id": "email_customer_reset_password",
    "label": "Reset password",
    "description": "Customer \"reset password\" emails are sent when customers reset their passwords.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_reset_password"
        }
      ]
    }
  },
  {
    "id": "email_customer_new_account",
    "label": "New account",
    "description": "Customer \"new account\" emails are sent to the customer when a customer signs up via checkout or account pages.",
    "parent_id": "email",
    "sub_groups": [],
    "_links": {
      "options": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/email_customer_new_account"
        }
      ]
    }
  }
]
```
