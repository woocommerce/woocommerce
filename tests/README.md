# WooCommerce Unit Tests

## Initial Setup

1) Install [PHPUnit](http://phpunit.de/) by following their [installation guide](https://phpunit.de/getting-started.html). If you've installed it correctly, this should display the version:

    $ phpunit --version

2) Install WordPress and the WP Unit Test lib using the `install.sh` script. Change to the plugin root directory and type:

    $ tests/bin/install.sh <db-name> <db-user> <db-password> [db-host]

Sample usage:

    $ tests/bin/install.sh woocommerce_tests root root

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

* Each test file should roughly correspond to an associated source file, e.g. the `formatting/functions.php` test file covers code in the `wc-formatting-functions.php` file
* Each test method should cover a single method or function with one or more assertions
* A single method or function can have multiple associated test methods if it's a large or complex method
* Use the test coverage HTML report (under `tmp/coverage/index.html`) to examine which lines your tests are covering and aim for 100% coverage
* For code that cannot be tested (e.g. they require a certain PHP version), you can exclude them from coverage using a comment: `// @codeCoverageIgnoreStart` and `// @codeCoverageIgnoreEnd`. For example, see [`wc_round_tax_total()`](https://github.com/woothemes/woocommerce/blob/master/includes/wc-formatting-functions.php#L155-155)
* In addition to covering each line of a method/function, make sure to test common input and edge cases.
* Prefer `assertsEquals()` where possible as it tests both type & equality
* Remember that only methods prefixed with `test` will be run so use helper methods liberally to keep test methods small and reduce code duplication. If there is a common helper method used in multiple test files, consider adding it to the `WC_Unit_Test_Case` class so it can be shared by all test cases
* Filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.
* Use data providers where possible. Be sure that their name is like `data_provider_function_to_test` (i.e. the data provider for `test_is_postcode` would be `data_provider_test_is_postcode`). Read more about data providers [here](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers).

## Automated Tests

Tests are automatically run with [Travis-CI](https://travis-ci.org) for each commit and pull request.

## Code Coverage

Code coverage is available on [Coveralls](https://coveralls.io/) which receives updated data after each Travis build.
