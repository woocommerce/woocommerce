# WooCommerce Order Fulfillments REST API

This document describes the REST API endpoints for managing order fulfillments in WooCommerce.

## Base URL

All endpoints use the WooCommerce REST API v3 namespace:

```http
/wp-json/wc/v3/orders/{order_id}/fulfillments
```

## Authentication

All endpoints require authentication with `manage_woocommerce` capability for full access, or order ownership for read-only access.

## Endpoints

### 1. Get Order Fulfillments

Retrieve all fulfillments for a specific order.

**Endpoint:** `GET /wp-json/wc/v3/orders/{order_id}/fulfillments`

#### Parameters

| Parameter  | Type    | Required | Description                     |
| ---------- | ------- | -------- | ------------------------------- |
| `order_id` | integer | Yes      | Unique identifier for the order |

#### Example Request

```http
GET /wp-json/wc/v3/orders/123/fulfillments
Authorization: Basic <base64_encoded_credentials>
```

#### Example Response

```json
[
    {
        "id": 1,
        "entity_type": "WC_Order",
        "entity_id": "123",
        "status": "fulfilled",
        "is_fulfilled": true,
        "date_updated": "2024-01-15T10:30:00",
        "date_deleted": null,
        "meta_data": [
            {
                "id": 1,
                "key": "_items",
                "value": [
                    {
                        "item_id": 456,
                        "qty": 2
                    },
                    {
                        "item_id": 789,
                        "qty": 1
                    }
                ]
            },
            {
                "id": 2,
                "key": "_tracking_number",
                "value": "1Z999AA1234567890"
            },
            {
                "id": 3,
                "key": "_shipping_provider",
                "value": "ups"
            }
        ]
    }
]
```

---

### 2. Create Order Fulfillment

Create a new fulfillment for a specific order.

**Endpoint:** `POST /wp-json/wc/v3/orders/{order_id}/fulfillments`

#### Parameters

| Parameter         | Type    | Required | Description                                                                                                                                                                                                      |
| ----------------- | ------- | -------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `order_id`        | integer | Yes      | Unique identifier for the order                                                                                                                                                                                  |
| `status`          | string  | No       | Status of the fulfillment (`fulfilled` or `unfulfilled`, or any other custom status that has been added, doesn't indicate that the fulfillment is fulfilled, but that's what's going to be shown on the screen.) |
| `is_fulfilled`    | boolean | No       | Whether the fulfillment is fulfilled                                                                                                                                                                             |
| `meta_data`       | array   | Yes      | Array of metadata objects                                                                                                                                                                                        |
| `notify_customer` | boolean | No       | Whether to notify customer (query parameter)                                                                                                                                                                     |

#### Example Request

```http
POST /wp-json/wc/v3/orders/123/fulfillments?notify_customer=true
Content-Type: application/json
Authorization: Basic <base64_encoded_credentials>

{
  "status": "fulfilled",
  "is_fulfilled": true,
  "meta_data": [
    {
      "key": "_items",
      "value": [
        {
          "item_id": 456,
          "qty": 2
        },
        {
          "item_id": 789,
          "qty": 1
        }
      ]
    },
    {
      "key": "_tracking_number",
      "value": "1Z999AA1234567890"
    },
    {
      "key": "_shipping_provider",
      "value": "ups"
    },
    {
      "key": "_tracking_url",
      "value": "https://www.ups.com/track?tracknum=1Z999AA1234567890"
    }
  ]
}
```

#### Example Response

```json
{
    "id": 2,
    "entity_type": "WC_Order",
    "entity_id": "123",
    "status": "fulfilled",
    "is_fulfilled": true,
    "date_updated": "2024-01-15T11:45:00",
    "date_deleted": null,
    "meta_data": [
        {
            "id": 3,
            "key": "_items",
            "value": [
                {
                    "item_id": 456,
                    "qty": 2
                },
                {
                    "item_id": 789,
                    "qty": 1
                }
            ]
        },
        {
            "id": 4,
            "key": "_tracking_number",
            "value": "1Z999AA1234567890"
        },
        {
            "id": 5,
            "key": "_shipping_provider",
            "value": "ups"
        },
        {
            "id": 6,
            "key": "_tracking_url",
            "value": "https://www.ups.com/track?tracknum=1Z999AA1234567890"
        }
    ]
}
```

---

### 3. Get Specific Fulfillment

Retrieve a specific fulfillment by ID.

**Endpoint:** `GET /wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}`

#### Parameters

| Parameter        | Type    | Required | Description                           |
| ---------------- | ------- | -------- | ------------------------------------- |
| `order_id`       | integer | Yes      | Unique identifier for the order       |
| `fulfillment_id` | integer | Yes      | Unique identifier for the fulfillment |

#### Example Request

```http
GET /wp-json/wc/v3/orders/123/fulfillments/2
Authorization: Basic <base64_encoded_credentials>
```

#### Example Response

```json
{
    "id": 2,
    "entity_type": "WC_Order",
    "entity_id": "123",
    "status": "fulfilled",
    "is_fulfilled": true,
    "date_updated": "2024-01-15T11:45:00",
    "date_deleted": null,
    "meta_data": [
        {
            "id": 3,
            "key": "_tracking_number",
            "value": "1Z999AA1234567890"
        },
        {
            "id": 4,
            "key": "_shipping_provider",
            "value": "ups"
        }
    ]
}
```

---

### 4. Update Fulfillment

Update an existing fulfillment.

**Endpoint:** `PUT /wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}`

#### Parameters

| Parameter         | Type    | Required | Description                                              |
| ----------------- | ------- | -------- | -------------------------------------------------------- |
| `order_id`        | integer | Yes      | Unique identifier for the order                          |
| `fulfillment_id`  | integer | Yes      | Unique identifier for the fulfillment                    |
| `status`          | string  | No       | Status of the fulfillment (`fulfilled` or `unfulfilled`) |
| `is_fulfilled`    | boolean | No       | Whether the fulfillment is fulfilled                     |
| `meta_data`       | array   | No       | Array of metadata objects                                |
| `notify_customer` | boolean | No       | Whether to notify customer (query parameter)             |

#### Example Request

```http
PUT /wp-json/wc/v3/orders/123/fulfillments/2?notify_customer=true
Content-Type: application/json
Authorization: Basic <base64_encoded_credentials>

{
  "id": 2,
  "status": "fulfilled",
  "is_fulfilled": true,
  "meta_data": [
    {
      "key": "_items",
      "value": [
        {
          "item_id": 456,
          "qty": 2
        },
        {
          "item_id": 789,
          "qty": 1
        }
      ]
    },
    {
      "key": "_tracking_number",
      "value": "1Z999AA1234567890"
    },
    {
      "key": "_shipping_provider",
      "value": "ups"
    },
    {
      "key": "_delivery_date",
      "value": "2024-01-16"
    }
  ]
}
```

#### Example Response

```json
{
    "id": 2,
    "entity_type": "WC_Order",
    "entity_id": "123",
    "status": "fulfilled",
    "is_fulfilled": true,
    "date_updated": "2024-01-16T09:30:00",
    "date_deleted": null,
    "meta_data": [
        {
            "id": 3,
            "key": "_items",
            "value": [
                {
                    "item_id": 456,
                    "qty": 2
                },
                {
                    "item_id": 789,
                    "qty": 1
                }
            ]
        },
        {
            "id": 4,
            "key": "_tracking_number",
            "value": "1Z999AA1234567890"
        },
        {
            "id": 5,
            "key": "_shipping_provider",
            "value": "ups"
        },
        {
            "id": 6,
            "key": "_delivery_date",
            "value": "2024-01-16"
        }
    ]
}
```

---

### 5. Delete Fulfillment

Delete a specific fulfillment.

**Endpoint:** `DELETE /wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}`

#### Parameters

| Parameter         | Type    | Required | Description                                 |
| ----------------- | ------- | -------- | ------------------------------------------- |
| `order_id`        | integer | Yes      | Unique identifier for the order             |
| `fulfillment_id`  | integer | Yes      | Unique identifier for the fulfillment       |
| `notify_customer` | boolean | No       | Whether to notify customer (default: false) |

#### Example Request

```http
DELETE /wp-json/wc/v3/orders/123/fulfillments/2?notify_customer=true
Authorization: Basic <base64_encoded_credentials>
```

#### Example Response

```json
{
	"message": "Fulfillment deleted successfully."
}
```

---

### 6. Get Fulfillment Metadata

Retrieve metadata for a specific fulfillment.

**Endpoint:** `GET /wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata`

#### Parameters

| Parameter        | Type    | Required | Description                           |
| ---------------- | ------- | -------- | ------------------------------------- |
| `order_id`       | integer | Yes      | Unique identifier for the order       |
| `fulfillment_id` | integer | Yes      | Unique identifier for the fulfillment |

#### Example Request

```http
GET /wp-json/wc/v3/orders/123/fulfillments/2/metadata
Authorization: Basic <base64_encoded_credentials>
```

#### Example Response

```json
[
    {
        "id": 3,
        "key": "_items",
        "value": [
            {
                "item_id": 456,
                "qty": 2
            },
            {
                "item_id": 789,
                "qty": 1
            }
        ]
    },
    {
        "id": 4,
        "key": "_tracking_number",
        "value": "1Z999AA1234567890"
    },
    {
        "id": 5,
        "key": "_shipping_provider",
        "value": "ups"
    },
    {
        "id": 6,
        "key": "_tracking_url",
        "value": "https://www.ups.com/track?tracknum=1Z999AA1234567890"
    }
]
```

---

### 7. Update Fulfillment Metadata

Update metadata for a specific fulfillment.

**Endpoint:** `PUT /wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata`

#### Parameters

| Parameter        | Type    | Required | Description                           |
| ---------------- | ------- | -------- | ------------------------------------- |
| `order_id`       | integer | Yes      | Unique identifier for the order       |
| `fulfillment_id` | integer | Yes      | Unique identifier for the fulfillment |
| `meta_data`      | array   | Yes      | Array of metadata objects to update   |

#### Example Request

```http
PUT /wp-json/wc/v3/orders/123/fulfillments/2/metadata
Content-Type: application/json
Authorization: Basic <base64_encoded_credentials>

[
    {
        "key": "_items",
        "value": [
            {
                "item_id": 456,
                "qty": 2
            },
            {
                "item_id": 789,
                "qty": 1
            }
        ]
    },
    {
        "key": "_tracking_number",
        "value": "1Z999AA9876543210"
    },
    {
        "key": "_shipping_provider",
        "value": "fedex"
    },
    {
        "key": "_estimated_delivery",
        "value": "2024-01-18"
    }
]
```

#### Example Response

```json
[
    {
        "id": 3,
        "key": "_items",
        "value": [
            {
                "item_id": 456,
                "qty": 2
            },
            {
                "item_id": 789,
                "qty": 1
            }
        ]
    },
    {
        "id": 4,
        "key": "_tracking_number",
        "value": "1Z999AA9876543210"
    },
    {
        "id": 5,
        "key": "_shipping_provider",
        "value": "fedex"
    },
    {
        "id": 7,
        "key": "_estimated_delivery",
        "value": "2024-01-18"
    }
]
```

---

### 8. Delete Fulfillment Metadata

Delete specific metadata from a fulfillment.

**Endpoint:** `DELETE /wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata`

#### Parameters

| Parameter        | Type    | Required | Description                           |
| ---------------- | ------- | -------- | ------------------------------------- |
| `order_id`       | integer | Yes      | Unique identifier for the order       |
| `fulfillment_id` | integer | Yes      | Unique identifier for the fulfillment |
| `meta_key`       | string  | Yes      | The metadata key to delete            |

#### Example Request

```http
DELETE /wp-json/wc/v3/orders/123/fulfillments/2/metadata
Authorization: Basic <base64_encoded_credentials>
{
    "meta_key": "_estimated_delivery"
}
```

#### Example Response

Empty response with 204 HTTP Header if successful, otherwise an error message is delivered.

---

### 9. Lookup Tracking Number Details

Get tracking information for a tracking number.

**Endpoint:** `GET /wp-json/wc/v3/orders/{order_id}/fulfillments/lookup`

#### Parameters

| Parameter         | Type    | Required | Description                     |
| ----------------- | ------- | -------- | ------------------------------- |
| `order_id`        | integer | Yes      | Unique identifier for the order |
| `tracking_number` | string  | Yes      | The tracking number to lookup   |

#### Example Request

```http
GET /wp-json/wc/v3/orders/123/fulfillments/lookup?tracking_number=1Z999AA1234567890
Authorization: Basic <base64_encoded_credentials>
```

#### Example Response (Multiple Possibilities)

```json
{
    "tracking_number": "1234567890123456",
    "shipping_provider": "fedex",
    "tracking_url": "https://www.fedex.com/fedextrack/?tracknum=1234567890123456",
    "possibilities": {
        "fedex": {
            "url": "https://www.fedex.com/fedextrack/?tracknum=1234567890123456",
            "ambiguity_score": 95
        },
        "ups": {
            "url": "https://www.ups.com/track?tracknum=1234567890123456",
            "ambiguity_score": 85
        },
        "dhl": {
            "url": "https://www.dhl.com/track?tracknum=1234567890123456",
            "ambiguity_score": 75
        }
    }
}
```

#### Example Response (Single Match)

```json
{
    "tracking_number": "1Z999AA1234567890",
    "shipping_provider": "ups",
    "tracking_url": "https://www.ups.com/track?tracknum=1Z999AA1234567890"
}
```

---

## Error Responses

All endpoints may return error responses in the following format:

```json
{
	"code": "error_code",
	"message": "Error message description",
	"data": {
		"status": 400
	}
}
```

### Common Error Codes

-   `woocommerce_rest_order_invalid_id` - Invalid order ID
-   `woocommerce_rest_tracking_number_missing` - Tracking number is required
-   `woocommerce_rest_order_id_missing` - Order ID is required
-   Authentication errors for insufficient permissions

### HTTP Status Codes

-   `200` - Success
-   `201` - Created
-   `204` - No Content (for DELETE metadata)
-   `400` - Bad Request
-   `401` - Unauthorized
-   `404` - Not Found

## Metadata Structure

Metadata objects have the following structure:

| Field   | Type    | Description                                            |
| ------- | ------- | ------------------------------------------------------ |
| `id`    | integer | Unique identifier for the metadata (0 for new entries) |
| `key`   | string  | The metadata key                                       |
| `value` | mixed   | The metadata value (string, number, array, or object)  |

**Note:** The metadata keys prefixed with underscore (`_`) are private and for internal use only. You can add as many as your application needs. The unprefixed ones will be shown to the merchant on the fulfillment metadata box, and to the customer on the fulfillment emails.

### Required Metadata

-   `_items` - **REQUIRED** - Array of objects representing items being fulfilled

### Optional Private Metadata Keys

-   `_tracking_number` - Shipment tracking number
-   `_shipping_provider` - Shipping provider key (ups, fedex, dhl, etc.)
-   `_tracking_url` - URL to track the shipment
-   `_is_locked` - Whether the fulfillment is locked for merchant modification
-   `_lock_message` - What to show as the lock message for a locked fulfillment

### Items Structure

The `_items` metadata must be an array of objects with the following structure:

```json
[
	{
		"item_id": 456,
		"qty": 2
	},
	{
		"item_id": 789,
		"qty": 1
	}
]
```

| Field     | Type    | Description                          |
| --------- | ------- | ------------------------------------ |
| `item_id` | integer | The order line item ID               |
| `qty`     | integer | Quantity of the item being fulfilled |

**Note:** The `_items` value is stored as a native array object in the metadata.

## Notifications

When `notify_customer` is set to `true`, WooCommerce will trigger appropriate action hooks:

-   `woocommerce_fulfillment_created_notification` - When a fulfillment is created or marked fulfilled
-   `woocommerce_fulfillment_updated_notification` - When a fulfilled fulfillment is updated
-   `woocommerce_fulfillment_deleted_notification` - When a fulfilled fulfillment is deleted
