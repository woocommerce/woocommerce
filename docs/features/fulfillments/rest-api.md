---
post_title: Order fulfillments REST API
sidebar_label: REST API
---

# Order fulfillments REST API

WooCommerce exposes order fulfillments through the REST API v3 controller, which uses order-scoped routes. This surface depends on the fulfillments feature flag being enabled.

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
| `DELETE` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/{fulfillment_id}/metadata?meta_key={meta_key}` | Delete a metadata key identified by the `meta_key` query parameter. |
| `GET` | `/wp-json/wc/v3/orders/{order_id}/fulfillments/lookup?tracking_number={tracking_number}` | Parse a tracking number and return provider-specific details when available. |

### Request fields

The v3 create and update routes accept the fulfillment state in the request body.

| Field | Type | Notes |
| --- | --- | --- |
| `status` | string | A registered fulfillment status slug. |
| `meta_data` | array | Array of metadata objects with `key`, `value`, and optional `id`. |
| `notify_customer` | boolean | Query parameter used on create, update, and delete requests. |

The `meta_data` array usually carries `_items`, `_tracking_number`, `_shipment_provider`, and `_tracking_url`.

> **Note**: `is_fulfilled` is a **read-only** response field. It is derived automatically from `status` by WooCommerce and cannot be set directly in requests.

### Create a fulfillment

```http
POST /wp-json/wc/v3/orders/123/fulfillments?notify_customer=true
Content-Type: application/json
Authorization: Basic base64(consumer_key:consumer_secret)

{
  "status": "fulfilled",
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
      "key": "_shipment_provider",
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

## Response shape

The REST API returns a fulfillment resource with the following core fields.

```json
{
  "id": 12,
  "entity_type": "WC_Order",
  "entity_id": "123",
  "status": "fulfilled",
  "is_fulfilled": true,
  "date_updated": "2026-03-28T15:00:00",
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
