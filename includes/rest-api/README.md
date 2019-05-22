---
title: 'WooCommerce REST API'
---

WooCommerce REST API
===

This repository houses the WooCommerce REST API found in the core WooCommerce plugin. 

It can also be used as a standalone plugin (it still requires WooCommerce!), or can be pulled into other projects and feature plugins that need new API features that are not yet in the core release.

Once stable, changes in woocommerce-rest-api are brought over to [WooCommerce core](https://github.com/woocommerce/woocommerce) when they are ready for release.

## Endpoint namespaces

* Current stable WC REST API version: **wc/v3** - **[Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/)**
* WC Blocks REST API version (internal use only): **wc-blocks/v1**
* Older versions:
    * **wc/v1** - **[Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/wp-api-v1.html)**
    * **wc/v2** - **[Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/wp-api-v2.html)**

## Contributing

Please read the [WooCommerce contributor guidelines](https://github.com/woocommerce/woocommerce/blob/master/.github/CONTRIBUTING.md) for more information how you can contribute.

Endpoints are located in the `includes/` directory. Endpoints currently inherit from the stable version of the endpoint. If you need to change the behavior of an endpoint, you can do so in these classes.

phpunit tests for the API are located in the `tests/unit-tests/` folder and are also merged and shipped with WooCommerce core. You can use the same helpers/framework files that core uses, or introduce new ones.

Run tests using `phpunit` in the root of this folder. Code coverage reports can be ran with `phpunit --coverage-html /tmp/coverage`.

## Translation

For strings located in API endpoints, use `woocommerce` as your text-domain. These endpoints will at some point be merged back into WooCommerce Core.
