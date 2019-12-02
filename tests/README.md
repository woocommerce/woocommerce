# WooCommerce Unit Tests

## Initial Setup

From the WooCommerce root directory (if you are using VVV you might need to `vagrant ssh` first), run the following:

1. Install [PHPUnit](http://phpunit.de/) via Composer by running:
    ```
    $ composer install
    ```

2. Install WordPress and the WP Unit Test lib using the `install.sh` script:
    ```
    $ tests/bin/install.sh <db-name> <db-user> <db-password> [db-host]
    ```

You may need to quote strings with backslashes to prevent them from being processed by the shell or other programs.

Example:

    $ tests/bin/install.sh woocommerce_tests root root

    #  woocommerce_tests is the database name and root is both the MySQL user and its password.

**Important**: The `<db-name>` database will be created if it doesn't exist and all data will be removed during testing.

## Running Tests

Change to the plugin root directory and type:

    $ vendor/bin/phpunit

The tests will execute and you'll be presented with a summary.

You can run specific tests by providing the path and filename to the test class:

    $ vendor/bin/phpunit tests/unit-tests/importer/product.php

A text code coverage summary can be displayed using the `--coverage-text` option:

    $ vendor/bin/phpunit --coverage-text

## Writing Tests

* Each test file should roughly correspond to an associated source file, e.g. the `formatting/functions.php` test file covers code in the `wc-formatting-functions.php` file
* Each test method should cover a single method or function with one or more assertions
* A single method or function can have multiple associated test methods if it's a large or complex method
* Use the test coverage HTML report (under `tmp/coverage/index.html`) to examine which lines your tests are covering and aim for 100% coverage
* For code that cannot be tested (e.g. they require a certain PHP version), you can exclude them from coverage using a comment: `// @codeCoverageIgnoreStart` and `// @codeCoverageIgnoreEnd`. For example, see [`wc_round_tax_total()`](https://github.com/woocommerce/woocommerce/blob/35f83867736713955fa2c4f463a024578bb88795/includes/wc-formatting-functions.php#L208-L219)
* In addition to covering each line of a method/function, make sure to test common input and edge cases.
* Prefer `assertSame()` where possible as it tests both type and value
* Remember that only methods prefixed with `test` will be run so use helper methods liberally to keep test methods small and reduce code duplication. If there is a common helper method used in multiple test files, consider adding it to the `WC_Unit_Test_Case` class so it can be shared by all test cases
* Filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.
* Use data providers where possible. Be sure that their name is like `data_provider_function_to_test` (i.e. the data provider for `test_is_postcode` would be `data_provider_test_is_postcode`). Read more about data providers [here](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers).

## Automated Tests

Tests are automatically run with [Travis-CI](https://travis-ci.org/woocommerce/woocommerce) for each commit and pull request.

## Code Coverage

Code coverage is available on [Scrutinizer](https://scrutinizer-ci.com/g/woocommerce/woocommerce/) and [Codecov](https://codecov.io/gh/woocommerce/woocommerce/) which receives updated data after each Travis build.
