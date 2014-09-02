# WooCommerce Unit Tests

## Initial Setup

1) Install [PHPUnit](http://phpunit.de/) by following their [installation guide](https://phpunit.de/getting-started.html). If you've installed it correctly, this should display the version:

    $ phpunit --version

2) Install WordPress and the WP Unit Test lib using the `install-wp-tests.sh` script. Change to the plugin root directory and type:

    $ tests/install-wp-tests.sh <db-name> <db-user> <db-password> [db-host]

Sample usage:

    $ tests/install-wp-tests.sh woocommerce_tests root root

**Important**: The `<db-name>` database will be created if it doesn't exist and all data will be removed during testing.

## Running Tests

Simply change to the plugin root directory and type:

    $ phpunit

The tests will execute and you'll be presented with a summary. Code coverage documentation is automatically generated as HTML in the `tmp/coverage` directory.

You can run specific tests by providing the path and filename to the test class:

    $ phpunit tests/unit-tests/api/webhooks

A text code coverage summary can be displayed using the `--coverage-text` option:

    $ phpunit --coverage-text

## Writing Tests

TODO

## Automated Tests

Tests are automatically run with Travis-CI for each commit and pull request.
