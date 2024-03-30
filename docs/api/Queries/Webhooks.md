# Webhooks query

Get all the existing webhooks, optionally only those with the given status.

### Code method
`\Automattic\WooCommerce\Api\Webhooks::get_webhooks`

### REST route
`GET /wp-json/wc/v4/rest/webhooks`

## Return type
[`[Webhook!]!`](../ObjectTypes/Webhook.md)

## Arguments

| Name | Type | Description |
|-|-|-|
status | [`WebhookStatus`](../Enums/WebhookStatus.md) | 
