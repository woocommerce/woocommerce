# Test Options

The table below shows what options are available for each test type. For example, for Activation tests you can supply a WordPress version but for the security tests it isn't supported.

|                              | Activation | E2E | API | Security | PHPStan |
| ---------------------------- | ---------- | --- | --- | -------- | ------- |
| WordPress Versions           | ✅         | ✅  | ✅  | ❌       | ❌      |
| WooCommerce Versions         | ✅         | ✅  | ✅  | ❌       | ❌      |
| WooCommerce Features         | ✅         | ✅  | ✅  | ❌       | ❌      |
| PHP Version                  | ✅         | ✅  | ✅  | ❌       | ❌      |
| Additional Extensions        | ✅         | ✅  | ✅  | ❌       | ❌      |
| Additional WordPress Plugins | ✅         | ✅  | ✅  | ❌       | ❌      |

## WordPress and WooCommerce versions

You can specify the last 5 stable releases along with the most recent beta/release candidate to be used in the test environment.

?> Please note that for API and end-to-end tests, the WooCommerce version is currently restricted to specific versions as we work to make the tests backwards and forwards compatible. For the most up to date versions that we currently support, you can select the test type in the Viewer under `Run a Test` or, using the QIT CLI, check the help for each test type: `./qit run:api --help` and `./qit run:e2e --help`.

## WooCommerce features

QIT currently supports the following option WooCommerce features:

- [High Performance Order Storage (HPOS)](https://developer.woocommerce.com/roadmap/high-performance-order-storage/)

For more information on using optional features in your tests, check out [Testing WooCommerce Optional Features](cli/running-tests?id=using-optional-features).

## PHP versions

You can specify a PHP version from 7.4 to 8.2 to be used in the test environment.
