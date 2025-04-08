# Remote Specification Validation

## Installation

```php
composer require woocommerce/remote-specs-validation
```

## Available Schemas

| Filename                          | Endpoint                                                                                               | Bundle                       |
| --------------------------------- | ------------------------------------------------------------------------------------------------------ | ---------------------------- |
| remote-inbox-notification.json    | <https://woocommerce.com/wp-json/wccom/inbox-notifications/2.0/notifications.json>                     | remote-inbox-notification    |
| payment-gateway-suggestions.json  | <https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/2.0/suggestions.json>               | payment-gateway-suggestions  |
| obw-free-extensions.json          | <https://woocommerce.com/wp-json/wccom/obw-free-extensions/4.0/extensions.json>                        | obw-free-extensions          |
| wc-pay-promotions.json            | <https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/2.0/payment-method/promotions.json> | wc-pay-promotions            |
| shipping-partner-suggestions.json | <https://woocommerce.com/wp-json/wccom/shipping-partner-suggestions/2.0/suggestions.json>              | shipping-partner-suggestions |

## Working with Schema

If it's your first time working with JSON Schema, we highly recommend reading <https://json-schema.org/learn/getting-started-step-by-step> first.

1. Open a schema file from `schemas` directory.
2. Make changes.
3. Run `./bin/build schemas/:name-of-schema-file`
4. Bundled schema file will be saved in `bundles` directory.

## Validation Examples

```php
use Automattic\WooCommerce\Tests\RemoteSpecsValidation\RemoteSpecValidator;
$validator = RemoteSpecValidator::create_from_bundle( 'remote-inbox-notification' );

$spec = json_decode( file_get_contents(":your-remote-inbox-noficiation-json") );

$result = $validator->validate( $spec );

if ( !$result->is_valid() ) {
	var_dump( $result->get_errors() );
} else {
	var_dump('everything looks good!');
}
```
