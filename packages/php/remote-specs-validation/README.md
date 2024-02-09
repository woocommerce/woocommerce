# Remote Specification Validation

## Available Schemas

| Filename  | Endpoint |
| ------------- | ------------- |
| remote-inbox-notification.json  | <https://woocommerce.com/wp-json/wccom/inbox-notifications/1.0/notifications.json>  |
| payment-gateway-suggestions.json  | <https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/suggestions.json>  |
| obw-free-extensions.json | <https://woocommerce.com/wp-json/wccom/obw-free-extensions/3.0/extensions.json> |
| wc-pay-promotions.json | <https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/payment-method/promotions.json> |
| shipping-partner-suggestions.json | <https://woocommerce.com/wp-json/wccom/shipping-partner-suggestions/1.0/suggestions.json> |

# Working with Schema

If it's your first time working with JSON Schema, we highly recommend reading <https://json-schema.org/learn/getting-started-step-by-step> first. 

1. Open a schema file from `schemas` directory.
2. Make changes.
3. Run `./bin/build schemas/:name-of-schema-file`
4. Bundled schema file will be saved in `bundles` directory.

