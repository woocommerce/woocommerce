# Docker PHP Test Suite

A docker app to run the PHP Test Suite.

## Getting Started

For convenience, the PHP Test Suite can be run from an npm script.

```shell
npm run test:php
```

This runs the the `phpunit` container with `docker-compose -f run --rm phpunit`. On first use, the container will install the PHP Test Suite and perform the tests. Subsequent use will only perform the tests.

## Re-install Test Suite

Re-installation is useful to update WordPress to the latest version. To do this, remove the existing `test-suite` volume using Docker. For example:

```shell
docker volume rm -f wc-admin-php-test-suite_test-suite
```

Then run the test suite normally using the npm script. Installation will be automatically performed.

## Testing a single test case

PHPUnit flags can be passed to the npm script. To limit testing to a single test case, use the `--filter` flag.

```shell
npm run test:php -- --filter=<name of test>
```

## Selecting the WordPress and WooCommerce Versions

By default, the minimum supported versions of WordPress and WooCommerce are used to build the test suite. This can be overridden with environment variables.

```shell
WP_VERSION=5.6 WC_VERSION=4.9.0 npm run test:php
```

## Selecting PHP versions

The PHP test suite supports running tests in PHP 7 and 8. We do use a custom PHPUnit fork of version ~7.5 when running tests in PHP 8, for the reasoning why see [this WooCommerce Readme](https://github.com/woocommerce/woocommerce/blob/trunk/tests/README.md#running-tests-in-php-8).

As mentioned in the readme, when running tests in PHP 8 it will modify the composer files in `/bin/composer/phpunit`, please remember to not include these changes in any of your commits.

To run tests using PHP 8:

```shell
PHP_VERSION=8 npm run test:php
```

## Development

When comitting changes to the `Dockerfile` or `entrypoint.sh` files, bump the `wc-admin-php-test-suite-phpunit` image tag version in `docker-composer.xml`. This will result in an image rebuild automatically upon next use, enabling the changes to be applied for all users.
