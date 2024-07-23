# Development

## Running Unit Tests

We'll assume this package is still within WooCommerce repository.

1. Run `wp-env start` from `plugins/woocommerce`
2. If there have been any changes to this package, run `composer reinstall woocommerce/blueprint`
3. Run `wp-env run tests-cli --env-cwd=wp-content/plugins/woocommerce/vendor/woocommerce/blueprint ./vendor/bin/phpunit`