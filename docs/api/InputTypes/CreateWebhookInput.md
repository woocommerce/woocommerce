# CreateWebhookInput

(No description)

## Fields for `CreateWebhookInput`
| Name | Type | Description |
|-|-|-|
user_id | `int!` | 
status | [`WebhookStatus!`](../Enums/WebhookStatus.md) | Initial status the webhook witll be created with
name | `string!` | Display name of the webhook.
topic | `string!` | The topic of the webhook.
delivery_url | `string!` | The URL where the webhook payload will be delivered.
secret | `string!` | Optional secret to be delivered as part of the webhook payload.
api_version | [`WebhookApiType!`](../Enums/WebhookApiType.md) | WooCommerce API version that will be used to compose the webhook payload.
