# WebhookDefinition

Represents the static webhook details.

## Fields for `WebhookDefinition`
| Name | Type | Description |
|-|-|-|
name | `string!` | Display name of the webhook.
topic | `string!` | The topic of the webhook.
delivery_url | `string!` | The URL where the webhook payload will be delivered.
secret | `string!` | Optional secret to be delivered as part of the webhook payload.
api_version | [`WebhookApiType!`](../Enums/WebhookApiType.md) | WooCommerce API version that will be used to compose the webhook payload.
