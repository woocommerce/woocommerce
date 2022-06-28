# WooCommerce Core API Test Suite - Playwright POC

This package contains automated API tests for WooCommerce using Playwright.

This is a POC. The 4 key operations from the coupons tests have been implemented

README.md needs to be further updated as the updating of the tests progresses but time ran out on me :)


## Environment variables

Before running the tests, the following environment variables need to be configured as shown in `.env.example`:

```
# Your site's base URL, not including a trailing slash
BASE_URL="https://mysite.com"

# The admin user's username or generated consumer key
USER_KEY=""

# The admin user's password or generated consumer secret
USER_SECRET=""
```

For local setup, create a `.env` file in the e2e folder with the three required values described above.

When using a username and password combination instead of a consumer secret and consumer key, make sure to have the [JSON Basic Authentication plugin](https://github.com/WP-API/Basic-Auth) installed and activated on the test site.

For more information about authentication with the WooCommerce API, please see the [Authentication](https://woocommerce.github.io/woocommerce-rest-api-docs/?javascript#authentication) section in the WooCommerce REST API documentation.

### Optional variables

The following optional variables can be set in your local `.env` file:

* `VERBOSE`: determine whether each individual test should be reported during the run.
* `USE_INDEX_PERMALINKS`: determine whether to use index permalinks (`?p=123`) for the API route.

## Running tests

### Test API connection

To verify that everything is configured correctly, the following test script is available:

```shell
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js ./e2e/tests/api-core-tests/tests/hello/hello.test.js
```

This tests connectivity to the API by validating connection to the following:

* A non-authenticated endpoint: [Index](https://woocommerce.github.io/woocommerce-rest-api-docs/?javascript#index)
* An endpoint requiring authentication: [System status properties](https://woocommerce.github.io/woocommerce-rest-api-docs/?javascript#system-status-properties)

### Running the coupons API tests
```shell
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js ./e2e/tests/api-core-tests/tests/coupons/coupons.test.js
```

## Creating test data

Most of the time, test data would be in the form of a request payload. Instead of building them from scratch inside the test, create a test data file inside the `data` directory. Create a model of the request payload within that file, and export it as an object or a function that generates this object.

Afterwards, make sure to add the test data file to the `data/index.js` file.

This way, the test data would be decoupled from the test itself, allowing for easier test data management, and more readable tests.

## Debugging tests

You can make use of the REST API log plugin to see how requests are being made, and check the request payload, response, and more.

[REST API Log](https://wordpress.org/plugins/wp-rest-api-log/)