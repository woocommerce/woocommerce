# WooCommerce Tests

This document discusses unit tests. See [the e2e README](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/README.md) to learn how to setup testing environment for running e2e tests and run them.

## Table of contents

- [WooCommerce Tests](#woocommerce-tests)
    - [Table of contents](#table-of-contents)
    - [Set up Test Environment](#set-up-test-environment)
        - [wp-env (recommended)](#wp-env-recommended)
        - [Manual setup](#manual-setup)
    - [Running Unit Tests](#running-unit-tests)
        - [Troubleshooting](#troubleshooting)
    - [Guide for Writing Unit Tests](#guide-for-writing-unit-tests)
    - [Automated Tests](#automated-tests)
    - [Code Coverage](#code-coverage)

## Set up Test Environment

Before using the tests a test environment is needed to run against.

You can set up the local testing environment by either using `wp-env` or by installing the required software on your machine.

### wp-env (recommended)

Run the following command to set up the environment:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce env:dev
```

### Manual setup

#### MySQL database

To run the tests, you need to create a test database. You can:

- Access a database on a server
- Connect to your local database on your machine
- Use a solution like VVV - if you are using VVV you might need to `vagrant ssh` first
- Run a throwaway database in docker with this one-liner: `docker run --rm --name woocommerce_test_db -p 3306:3306 -e MYSQL_ROOT_PASSWORD=woocommerce_test_password -d mysql:8.0.32`. ( Use `tests/bin/install.sh woocommerce_tests root woocommerce_test_password 0.0.0.0` in next step)

#### Setup instructions

Once you have database, from the WooCommerce root directory "cd" into `plugins/woocommerce` directory and run the following:

1. Install [PHPUnit](http://phpunit.de/) via Composer by running: `composer install`
2. Install WordPress and the WP Unit Test lib using the `install.sh` script:

```sh
tests/bin/install.sh <db-name> <db-user> <db-password> [db-host]
```

You may need to quote strings with backslashes to prevent them from being processed by the shell or other programs.

Example:

```sh
tests/bin/install.sh woocommerce_tests root root

# woocommerce_tests is the database name and root is both the MySQL user and its password.
```

**Important**: The `<db-name>` database will be created if it doesn't exist and all data will be removed during testing.

## Running Unit Tests

To run the tests, you can use the following command:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env

# or 

pnpm --filter=@woocommerce/plugin-woocommerce test:unit  # if you are not using wp-env
```

You can run specific tests by providing the `--filter` option. For example, to run only the tests in a specific class:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env -- --filter=TestClassName
```

For example, to test `WC_Admin_Tests_RemoteInboxNotifications_PluginVersionRuleProcessor` class:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env -- --filter=WC_Admin_Tests_RemoteInboxNotifications_PluginVersionRuleProcessor
```

A text code coverage summary can be displayed using the `--coverage-text` option:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env -- --coverage-text
```

You can also watch for changes and re-run tests automatically by using the following command:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env:watch
```

By default, all tests will be run. You can also run specific tests by providing the `--filter` option. For example, to run only the tests in a specific class:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env:watch -- --filter=TestClassName
```

or you can pass `--list-groups` to list all the available test groups:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env:watch -- --list-groups
```

### Troubleshooting

In case you're unable to run the unit tests, you might see an error message similar to:

```sh
Fatal error: require_once(): Failed opening required '/var/folders/qr/3cnz_5_j3j1cljph_246ty1h0000gn/T/wordpress-tests-lib/includes/functions.php' (include_path='.:/usr/local/Cellar/php@7.4/7.4.23/share/php@7.4/pear') in /Users/nielslange/Plugins/woocommerce/tests/legacy/bootstrap.php on line 59
```

If you run into this problem, simply delete the WordPress test directory and run the installer again. In this particular case, you'd run the following commands:

```sh
rm -rf /var/folders/qr/3cnz_5_j3j1cljph_246ty1h0000gn/T/wordpress-tests-lib
```

```sh
tests/bin/install.sh woocommerce_tests_1 root root
```

Or if you run into this error:

```sh
PHP Fatal error:  require_once(): Failed opening required '/var/folders/n_/ksp7kpt9475byx0vs665j6gc0000gn/T/wordpress//wp-includes/PHPMailer/PHPMailer.php' (include_path='.:/usr/local/Cellar/php@7.4/7.4.26_1/share/php@7.4/pear') in /private/var/folders/n_/ksp7kpt9475byx0vs665j6gc0000gn/T/wordpress-tests-lib/includes/mock-mailer.php on line 2]
```

You will want to delete the wordpress folder

```sh
rm -rf /var/folders/qr/3cnz_5_j3j1cljph_246ty1h0000gn/T/wordpress
```

```sh
tests/bin/install.sh woocommerce_tests_1 root root
```

Note that `woocommerce_tests` changed to `woocommerce_tests_1` as the `woocommerce_tests` database already exists due to the prior command.

## Guide for Writing Unit Tests

There are three different unit test directories:

- `tests/legacy/unit-tests` contains tests for code in the `includes` directory. No new tests should be added here, ever; existing test classes shouldn't get new tests either. Fixing faulty existing tests is allowed.
- `tests/php/includes` is where all the new tests for code in the `includes` directory should be written.
- `tests/php/src` is where all the tests for code in the `src` directory should be written.

Each test file should correspond to an associated source file and be named accordingly:

- For `src` code: The base namespace for tests is `Automattic\WooCommerce\Tests`. A class named `Automattic\WooCommerce\TheNamespace\TheClass` should have a test named `Automattic\WooCommerce\Tests\TheNamespace\TheClassTest`.
- For `includes` code:
    - When testing classes: use the same approach as for `src` except that namespaces are not used. So a `WC_Something` class in `includes/somefolder/class-wc-something.php` should have its tests in `tests/src/internal/somefolder/class-wc-something-test.php`.
    - When testing functions: use one test file per functions group file, for example `wc-formatting-functions-test.php` for code in the `wc-formatting-functions.php` file.


See also [the guidelines for writing unit tests for `src` code](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/README.md#writing-unit-tests) and [the guidelines for `includes` code](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/includes/README.md#writing-unit-tests).

General guidelines for all the unit tests:

- Each test method should cover a single method or function with one or more assertions
- A single method or function can have multiple associated test methods if it's a large or complex method
- Use the test coverage HTML report (under `tmp/coverage/index.html`) to examine which lines your tests are covering and aim for 100% coverage
- For code that cannot be tested (e.g. they require a certain PHP version), you can exclude them from coverage using a comment: `// @codeCoverageIgnoreStart` and `// @codeCoverageIgnoreEnd`. For example, see [`wc_round_tax_total()`](https://github.com/woocommerce/woocommerce/blob/35f83867736713955fa2c4f463a024578bb88795/includes/wc-formatting-functions.php#L208-L219)
- In addition to covering each line of a method/function, make sure to test common input and edge cases.
- Prefer `assertSame()` where possible as it tests both type and value
- Remember that only methods prefixed with `test` will be run so use helper methods liberally to keep test methods small and reduce code duplication. If there is a common helper method used in multiple test files, consider adding it to the `WC_Unit_Test_Case` class so it can be shared by all test cases
- Filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.
- Use data providers where possible. Be sure that their name is like `data_provider_function_to_test` (i.e. the data provider for `test_is_postcode` would be `data_provider_test_is_postcode`). Read more about data providers [here](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers).

## Automated Tests

Tests are automatically run with [GitHub Actions](https://github.com/woocommerce/woocommerce/actions/workflows/ci.yml) for each commit and pull request.

## Code Coverage

Code coverage is available on [Codecov](https://codecov.io/gh/woocommerce/woocommerce/) which receives updated data after each build.
