# Running tests

The QIT CLI allows running tests against both extensions that are published and available for sale in the WooCommerce Extension Store as well as against development builds of an extension.

## Choosing the type of test to run

The commands to run tests are formatted as `run:<test-type>`. The CLI supports all of the current [test types](test-types.md) using the following commands:

- End-to-end
- API
- Activation
- Security
- PHPStan

For example, to run end-to-end tests, you'd run the following command: `./vendor/bin/qit run:e2e my-plugin-slug`.

## Testing a published extension

For running any kind of tests, you'll need the slug for the given extension you want to run the tests against. For example, to run end-to-end tests against an extension with the slug `my-extension`, you'd run the following command:

```shell
qit run:e2e my-extension
```

This will run the [WooCommerce Core E2E Test Suite](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e-pw) against the WooCommerce extension with slug `my-extension` using the latest stable versions of WordPress and WooCommerce.

Since the tests are executed in the cloud, you can even close the terminal if you wish. You can see the result of this test after a while running `qit list-tests`, or `qit get 123`, where `123` is the test run ID. When the test finishes, the status will be updated to `Success`, `Warning`, or `Failed`. For more details on these commands, please see [Useful Commands](cli/useful-commands.md).

## Testing development builds

The QIT CLI supports testing development builds of extensions, so you can run any of the [test types](../test-types.md) against an unpublished version of your extension in the same QIT environment before publishing it to the WooCommerce Store.

!> Make sure the zipped version is a valid plugin. As this is installed on a test WordPress site, an invalid plugin will fail to install and cause the tests to fail.

Once you have a zipped up version of your extension you'd like to test with, use the `--zip` argument to pass in a path to the zip file containing your extension.

For example, to run end-to-end tests against your local build, you'd run the following command:

```shell
qit run:e2e my-extension --zip=my-extension.zip
```

## Seeing test runs and their results

Since the tests are executed in the cloud, you can even close the terminal. You can see the result of this test after a while by running one of the two commands below:

- Run `qit list-tests` to see a list of test runs.
- Run `qit get 123` to get more details about a specific test run, where `123` is the test run ID.
- Run `qit open 123` to open the report for test run `123` in the browser.

When the test finishes, the status will be updated to `Success`, `Warning`, or `Failed`. For more details on what these commands show, please see [Useful Commands](useful-commands.md).

## Specifying WooCommerce and WordPress versions

The QIT CLI supports testing against different versions of WooCommerce and WordPress. There are two arguments available to use, depending on your needs:

- `--woocommerce_version`
- `--wordpress_version`

For example, to run activation tests against the RC 2 version of WooCommerce 7.0.0 and WordPress 6.0.1, you can run the following command:

`qit run:activation my-extension --woocommerce_version=7.0.0-rc.2 --wordpress_version=6.0.1`

?> If either these arguments are not supplied, then the tests will just run against the current versions of WooCommerce and WordPress.

For example, if you wanted to run against the latest WordPress but use a different version of WooCommerce, you can do so:

`qit run:e2e my-extension --woocommerce_version=6.0.0`

## Using optional features

You can also enable option features in your test environment by using the `--optional_features` flag. For example, to run an activation test with the High Performance Order Storage feature enabled, you can run the following command:

`qit run:activation my-extension --optional_features=hpos`

Currently, QIT supports enabling the following optional features:

- `hpos`: [High Performance Order Storage (HPOS)](https://developer.woocommerce.com/roadmap/high-performance-order-storage/)
