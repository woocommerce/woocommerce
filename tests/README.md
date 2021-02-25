# WooCommerce Tests

This document discusses unit tests. See [the e2e README](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e) to learn how to setup testing environment for running e2e tests and run them.


## Table of contents

- [WooCommerce Tests](#woocommerce-tests)
  - [Table of contents](#table-of-contents)
  - [Initial Setup](#initial-setup)
    - [MySQL database](#mysql-database)
    - [Setup instructions](#setup-instructions)
  - [Running Tests](#running-tests)
    - [Running tests in PHP 8](#running-tests-in-php-8)
  - [Writing Tests](#writing-tests)
  - [Automated Tests](#automated-tests)
  - [Code Coverage](#code-coverage)


## Initial Setup

### MySQL database

To run the tests, you need to create a test database. You can:
- Access a database on a server
- Connect to your local database on your machine
- Use a solution like VVV - if you are using VVV you might need to `vagrant ssh` first
- Run a throwaway database in docker with this one-liner: `docker run --rm --name woocommerce_test_db -p 3306:3306 -e MYSQL_ROOT_PASSWORD=woocommerce_test_password -d mysql:5.7.33`. ( Use `tests/bin/install.sh woocommerce_tests root woocommerce_test_password 0.0.0.0` in next step) 

### Setup instructions

Once you have database, from the WooCommerce root directory run the following:

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

    $ vendor/bin/phpunit tests/legacy/unit-tests/importer/product.php

A text code coverage summary can be displayed using the `--coverage-text` option:

    $ vendor/bin/phpunit --coverage-text

### Running tests in PHP 8

WooCommerce currently supports PHP versions from 7.0 up to 8.0, and this poses an issue with PHPUnit:

* The latest PHPUnit version that supports PHP 7.0 is 6.5.14
* The latest PHPUnit version that WordPress (and thus WooCommerce) supports is 7.5.20, but that version doesn't work on PHP 8

To workaround this, the testing strategy used by WooCommerce is as follows:

* We normally use PHPUnit 6.5.14
* For PHP 8 we use [a custom fork of PHPUnit 7.5.20 with support for PHP 8](https://github.com/woocommerce/phpunit/pull/1). The Travis build is configured to use this fork instead of the old version 6 when running in PHP 8.

If you want to run the tests locally under PHP 8 you'll need to temporarily modify `composer.json` to use the custom PHPUnit fork in the same way that the Travis setup script does. These are the commands that you'll need (run them after a regular `composer install`):

```shell
curl -L https://github.com/woocommerce/phpunit/archive/add-compatibility-with-php8-to-phpunit-7.zip -o /tmp/phpunit-7.5-fork.zip
unzip -d /tmp/phpunit-7.5-fork /tmp/phpunit-7.5-fork.zip
composer bin phpunit config --unset platform
composer bin phpunit config repositories.0 '{"type": "path", "url": "/tmp/phpunit-7.5-fork/phpunit-add-compatibility-with-php8-to-phpunit-7", "options": {"symlink": false}}'
composer bin phpunit require --dev -W phpunit/phpunit:@dev --ignore-platform-reqs    
```

Just remember that you can't include the modified `composer.json` in any commit!


## Writing Tests

There are three different unit test directories:

- `tests/legacy/unit-tests` contains tests for code in the `includes` directory. No new tests should be added here, ever; existing test classes shouldn't get new tests either. Fixing faulty existing tests is allowed.
- `tests/php/includes` is where all the new tests for code in the `includes` directory should be written.
- `tests/php/src` is where all the tests for code in the `src` directory should be written.

Each test file should correspond to an associated source file and be named accordingly:
    * For `src` code: The base namespace for tests is `Automattic\WooCommerce\Tests`. A class named `Automattic\WooCommerce\TheNamespace\TheClass` should have a test named `Automattic\WooCommerce\Tests\TheNamespace\TheClassTest`.
    * For `includes` code:
        * When testing classes: use the same approach as for `src` except that namespaces are not used. So a `WC_Something` class in `includes/somefolder/class-wc-something.php` should have its tests in `tests/src/internal/somefolder/class-wc-something-test.php`.
        * When testing functions: use one test file per functions group file, for example `wc-formatting-functions-test.php` for code in the `wc-formatting-functions.php` file.


See also [the guidelines for writing unit tests for `src` code](https://github.com/woocommerce/woocommerce/tree/trunk/src/README.md#writing-unit-tests) and [the guidelines for `includes` code](https://github.com/woocommerce/woocommerce/tree/trunk/includes/README.md#writing-unit-tests). 

General guidelines for all the unit tests:

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

Code coverage is available on [Codecov](https://codecov.io/gh/woocommerce/woocommerce/) which receives updated data after each Travis build.
