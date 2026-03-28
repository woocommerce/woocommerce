---
post_title: Order fulfillments REST API
sidebar_label: REST API
---

# Order fulfillments REST API

WooCommerce exposes order fulfillments through two related REST surfaces. The v3 controller uses order-scoped routes, while the v4 controller adds top-level fulfillment resources and a provider registry endpoint. Both surfaces depend on the fulfillments feature flag being enabled.

## Permissions

WooCommerce applies the same permission model across the fulfillment routes:

- Users with the `manage_woocommerce` capability can read and write all fulfillment resources.
- The customer who owns an order can only perform read requests for that order's fulfillments.
- Write requests require an administrator or another user with the required WooCommerce capability.

## WooCommerce REST API v3

The v3 controller exposes order-scoped routes under the standard WooCommerce REST namespace.

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/wp-json/wc/v3/orders/{order_id}/fulfillments` | List all fulfillments for an order. |
| `POST` | `/wp-json/wc/v3/orders/{order_id}/fulfillments` | Create a fulfillment for an order. |
| `GET` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}` | Retrieve one fulfillment. |
| `PUT` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}` | Update one fulfillment. |
| `DELETE` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}` | Soft-delete one fulfillment. |
| `GET` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata` | Read raw fulfillment metadata. |
| `PUT` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata` | Replace fulfillment metadata values. |
| `DELETE` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata` | Delete a metadata key. |
| `GET` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/lookup?tracking_number={tracking_number}` | Parse a tracking number and return provider-specific details when available. |

### Request fields

The v3 create and update routes accept the fulfillment state in the request body.

| Field | Type | Notes |
| --- | --- | --- |
| `status` | string | A registered fulfillment status slug. |
| `is_fulfilled` | boolean | Whether the fulfillment is fulfilled. The status helper will keep these values in sync. |
| `meta_data` | array | Array of metadata objects with `key`, `value`, and optional `id`. |
| `notify_customer` | boolean | Query parameter used on create, update, and delete requests. |

The `meta_data` array usually carries `_items`, `_tracking_number`, `_shipping_provider`, and `_tracking_url`.

### Create a fulfillment

```http
POST /wp-json/wc/v3/orders/123/fulfillments?notify_customer=true
Content-Type: application/json
Authorization: Basic base64(consumer_key:consumer_secret)

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

### Update behavior for metadata

When a v3 update request includes a `meta_data` array, WooCommerce treats the supplied keys as the desired final state for the fulfillment metadata. Existing metadata keys that are not present in the request payload are removed during the update.

The same replacement behavior applies to the dedicated metadata update endpoint.

## WooCommerce REST API v4

The v4 controller adds top-level fulfillment routes and a provider registry endpoint.

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/wp-json/wc/v4/fulfillments?order_id={order_id}` | List all fulfillments for an order. |
| `POST` | `/wp-json/wc/v4/fulfillments` | Create a fulfillment using `entity_type` and `entity_id` in the request body. |
| `GET` | `/wp-json/wc/v4/fulfillments/{fulfillment_id}` | Retrieve one fulfillment. |
| `PUT` | `/wp-json/wc/v4/fulfillments/{fulfillment_id}` | Update one fulfillment. |
| `DELETE` | `/wp-json/wc/v4/fulfillments/{fulfillment_id}` | Soft-delete one fulfillment (marks it as deleted via `date_deleted`). |
| `GET` | `/wp-json/wc/v4/fulfillments/providers` | Retrieve the shipping provider registry used by the feature. |

For order-backed fulfillments, the v4 controller delegates the CRUD behavior to the v3 order controller. That means the response shape, validation rules, customer notification behavior, and hook execution stay aligned across both namespaces. The v4 `DELETE` route uses the same soft-delete behavior as v3, updating `date_deleted` instead of permanently removing the fulfillment.

### Create a v4 fulfillment

```http
POST /wp-json/wc/v4/fulfillments
Content-Type: application/json
Authorization: Basic base64(consumer_key:consumer_secret)

{
  "entity_type": "WC_Order",
  "entity_id": 123,
  "status": "fulfilled",
  "is_fulfilled": true,
  "meta_data": [
    {
      "key": "_items",
      "value": [
        {
          "item_id": 456,
          "qty": 2
        }
      ]
    },
    {
      "key": "_tracking_number",
      "value": "1Z999AA1234567890"
    }
  ]
}
```

### Provider registry response

The v4 providers endpoint returns an object keyed by provider slug. Each provider object includes the fields below.

| Field | Description |
| --- | --- |
| `label` | Human-readable provider name. |
| `icon` | Icon URL used by the admin UI. |
| `value` | Provider slug. |
| `url` | Tracking URL template. |

Extensions can filter that response with `woocommerce_rest_prepare_fulfillments_providers`.

## Response shape

Both REST surfaces return a fulfillment resource with the same core fields.

```json
{
  "id": 12,
  "entity_type": "WC_Order",
  "entity_id": "123",
  "status": "fulfilled",
  "is_fulfilled": true,
  "date_updated": "2026-03-28 15:00:00",
  "date_deleted": null,
  "meta_data": [
    {
      "id": 44,
      "key": "_tracking_number",
      "value": "1Z999AA1234567890"
    }
  ]
}
```

## Related hooks

REST requests trigger the same lifecycle hooks as direct PHP usage of the `Fulfillment` object. Use the [hooks reference](./hooks.md) when an extension needs to validate fulfillment data, send external notifications, customize order notes, or alter the provider registry.