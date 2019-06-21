WooCommerce REST API
===

<a href="https://packagist.org/packages/woocommerce/woocommerce-rest-api"><img src="https://poser.pugx.org/woocommerce/woocommerce-rest-api/license" alt="license"></a> 
<a href="https://packagist.org/packages/woocommerce/woocommerce-rest-api"><img src="https://poser.pugx.org/woocommerce/woocommerce-rest-api/v/stable" alt="Latest Stable Version"></a>
<a href="https://travis-ci.org/woocommerce/woocommerce-rest-api/"><img src="https://travis-ci.org/woocommerce/woocommerce-rest-api.svg?branch=master" alt="Build Status"></a>
<a href="https://scrutinizer-ci.com/g/woocommerce/woocommerce-rest-api/?branch=master"><img src="https://scrutinizer-ci.com/g/woocommerce/woocommerce-rest-api/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"></a>

This repository is home to the WooCommerce REST API package. 

The stable version of this package is bundled with [WooCommerce core](https://github.com/woocommerce/woocommerce)  releases, but it can also be used as a standalone plugin so bleeding-edge API features can be tested or used by other feature plugins.

## Using this package as a plugin

After checking out the code to your `wp-content/plugins` directory, you'll need to run `composer install` in the plugin directory (`wp-content/plugins/woocommerce-rest-api`) to install dependencies and to enable the autoloader. Without performing this step, if you activate the plugin it will simply show an admin notice.

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

## Using this package in other projects

This package is [hosted on Packagist](https://packagist.org/packages/woocommerce/woocommerce-rest-api) and can be included using composer.json:

```json
"require": {
    "woocommerce/woocommerce-rest-api": "1.0.0"
},
```

Since multiple versions of this package may be included at the same time, it includes a special package-version autoloader. This dependency is also on Packagist:

```json
  "automattic/jetpack-autoloader": "^1"
```

And using this autoloader requires the following include in your codebase:

```
$autoloader = __DIR__ . '/vendor/autoload_packages.php';
```

If you choose to use your own autoloader, please note you won't be able to determine which version of the package is running since it could use the version in WooCommerce core or your version. The namespaces would conflict. All of our feature plugins and packages use the package autoloader.

## Contributing

Please read the [WooCommerce contributor guidelines](https://github.com/woocommerce/woocommerce/blob/master/.github/CONTRIBUTING.md) for more information how you can contribute to WooCommerce, and [the REST API contribution documentation here](https://github.com/woocommerce/woocommerce/wiki/Contributing-to-the-WooCommerce-REST-API).

Within this package, namespaces and endpoint classes are located within the `src/RestAPI/` directory. If you need to change the behavior of an endpoint, you can do so in these classes.

Run tests using `phpunit` in the root of the package. All pull-requests must pass unit tests in order to be accepted.

## Translation

For strings located in API endpoints, use `woocommerce` as your text-domain. These endpoints will be translated in the WooCommerce Core PO/MO files.
