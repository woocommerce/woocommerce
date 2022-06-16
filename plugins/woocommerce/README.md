<p align="center"><a href="https://woocommerce.com/"><img src="https://woocommerce.com/wp-content/themes/woo/images/logo-woocommerce@2x.png" alt="WooCommerce"></a></p>

<p align="center">
<a href="https://packagist.org/packages/woocommerce/woocommerce"><img src="https://poser.pugx.org/woocommerce/woocommerce/license" alt="license"></a> 
<a href="https://packagist.org/packages/woocommerce/woocommerce"><img src="https://poser.pugx.org/woocommerce/woocommerce/v/stable" alt="Latest Stable Version"></a>
<img src="https://img.shields.io/wordpress/plugin/dt/woocommerce.svg" alt="WordPress.org downloads">
<img src="https://img.shields.io/wordpress/plugin/r/woocommerce.svg" alt="WordPress.org rating">
<a href="https://github.com/woocommerce/woocommerce/actions/workflows/ci.yml"><img src="https://github.com/woocommerce/woocommerce/actions/workflows/ci.yml/badge.svg?branch=trunk" alt="Build Status"></a>
<a href="https://codecov.io/gh/woocommerce/woocommerce"><img src="https://codecov.io/gh/woocommerce/woocommerce/branch/trunk/graph/badge.svg" alt="codecov"></a>
</p>

This is the WooCommerce Core plugin. Here you can browse the source and keep track of development. We recommend all developers to follow the [WooCommerce development blog](https://woocommerce.wordpress.com/) to stay up to date about everything happening in the project. You can also [follow @DevelopWC](https://twitter.com/DevelopWC) on Twitter for the latest development updates.

If you are not a developer, please use the [WooCommerce plugin page](https://wordpress.org/plugins/woocommerce/) on WordPress.org.

## Getting Started

Please make sure you follow the [repository's getting started guide](../../README.md#getting-started) first!

```bash
# Make sure that WooCommerce Core and all of its dependencies are built
pnpm -- turbo run build --filter=woocommerce
# Make sure you're in the WooCommerce Core directory
cd plugins/woocommerce
# Start the development environment
pnpm -- wp-env start
```

You should now be able to visit http://docker.local:8888/ and access WooCommerce environment.

## Documentation
* [WooCommerce Documentation](https://docs.woocommerce.com/)
* [WooCommerce Developer Documentation](https://github.com/woocommerce/woocommerce/wiki)
* [WooCommerce Code Reference](https://docs.woocommerce.com/wc-apidocs/)
* [WooCommerce REST API Docs](https://woocommerce.github.io/woocommerce-rest-api-docs/)

## Reporting Security Issues
To disclose a security issue to our team, [please submit a report via HackerOne here](https://hackerone.com/automattic/).
