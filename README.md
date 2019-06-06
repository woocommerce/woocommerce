WooCommerce REST API
===

This repository is home to the WooCommerce REST API package. 

The stable version of this package is bundled with [WooCommerce core](https://github.com/woocommerce/woocommerce)  releases, but it can also be used as a standalone plugin so bleeding-edge API features can be tested or used by other feature plugins.

## Using this package

This package is [hosted on Packagist](https://packagist.org/packages/woocommerce/woocommerce-rest-api) and can be included using composer.json:

```json
"require": {
    "woocommerce/woocommerce-rest-api": "1.0.0"
},
```

Since multiple versions of this package may be included at the same time, WooCommerce handles loading __only the latest version__ by using a special loader. 

After including the package and installing dependencies, include the main `woocommerce-rest-api.php` file in your code:

```php
include 'vendor/woocommerce-rest-api/woocommerce-rest-api.php';
```

This will register the version of the package with WooCommerce and make it available after the `woocommerce_loaded` hook is fired.

## API documentation

- [Usage documentation for the REST API can be found here](https://github.com/woocommerce/woocommerce/wiki/Getting-started-with-the-REST-API).
- [Contribution documentation can be found here.](https://github.com/woocommerce/woocommerce/wiki/Contributing-to-the-WooCommerce-REST-API)

### Versions

| Namespace | Status | Docs |
| -------- | -------- | -------- |
| `wc/v4`     | Development     | [Link](https://woocommerce.github.io/woocommerce-rest-api-docs/)     |
| `wc/v3`     | Stable     | [Link](https://woocommerce.github.io/woocommerce-rest-api-docs/)     |
| `wc/v2`     | Deprecated - October 2020     | [Link](https://woocommerce.github.io/woocommerce-rest-api-docs/wp-api-v2.html)     |
| `wc/v1`     | Deprecated - April 2019     | [Link](https://woocommerce.github.io/woocommerce-rest-api-docs/wp-api-v1.html)     |

Note: API Versions are kept around for 2 years after being replaced, and may be removed in the next major version after that date passes.

## Contributing

Please read the [WooCommerce contributor guidelines](https://github.com/woocommerce/woocommerce/blob/master/.github/CONTRIBUTING.md) for more information how you can contribute to WooCommerce, and [the REST API contribution documentation here](https://github.com/woocommerce/woocommerce/wiki/Contributing-to-the-WooCommerce-REST-API).

Within this package, namespaces and endpoint classes are located within the `src/RestAPI/` directory. If you need to change the behavior of an endpoint, you can do so in these classes.

Run tests using `phpunit` in the root of the package. All pull-requests must pass unit tests in order to be accepted.

## Translation

For strings located in API endpoints, use `woocommerce` as your text-domain. These endpoints will be translated in the WooCommerce Core PO/MO files.
