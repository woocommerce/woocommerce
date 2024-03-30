# Webhook

Represents a full webhook, including static definition and current status.

## Implemented interfaces

* [WebhookDefinition](../Interfaces/WebhookDefinition.md)

## Fields for `Webhook`
| Name | Type | Description |
|-|-|-|
id | `int!` | 
failure_count | `int!` | 
pending_delivery | `bool!` | 
date_created | `string!` | 
status | [`WebhookStatus!`](../Enums/WebhookStatus.md) | 
user | [`User`](../ObjectTypes/User.md) | 
name | `string!` | Display name of the webhook.
topic | `string!` | The topic of the webhook.
delivery_url | `string!` | The URL where the webhook payload will be delivered.
secret | `string!` | Optional secret to be delivered as part of the webhook payload.
api_version | [`WebhookApiType!`](../Enums/WebhookApiType.md) | WooCommerce API version that will be used to compose the webhook payload.
