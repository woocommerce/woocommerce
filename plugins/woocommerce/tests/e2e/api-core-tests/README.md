# WooCommerce Core API Test Suite

This package contains automated API tests for WooCommerce.

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

For local setup, create a `.env` file in this folder with the three required values described above.

Alternatively, these values can be passed in via the command line. For example:

```shell
BASE_URL=http://localhost:8084 USER_KEY=admin USER_SECRET=password npm run test:api
```

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
npm run test:hello
```

This tests connectivity to the API by validating connection to the following:

* A non-authenticated endpoint: [Index](https://woocommerce.github.io/woocommerce-rest-api-docs/?javascript#index)
* An endpoint requiring authentication: [System status properties](https://woocommerce.github.io/woocommerce-rest-api-docs/?javascript#system-status-properties)

### Run all tests

To run all of the API tests, you can use the following command:

```shell
npm run test:api
```

### Running groups of tests

To run a specific group of tests, you can use the `npm test -- --group=` command and pass in the group's name you want to run.

For example, if you wanted to only run the orders API tests, you can use the following:

```shell
npm test -- --group=orders
```

Alternatively, you can use `jest` to run test groups:

```shell
jest --group=api
```

## Writing tests

### Test groups

This package makes use of the `jest-runner-groups` package, which allows grouping tests together around semantic groups (such as `orders` API calls, or `coupons` API calls) to make running test suites more granular.

Before the `describe()` statement, add in a doc block containing the desired groups:

```javascript
/**
 * Tests for the WooCommerce API.
 *
 * @group api
 * @group endpoint
 *
 */
describe('', () => {
	it('', async () => {});
});
```

The `api` group should be included on all tests that should be run with the rest of the test suite. Groups can also contain a path, such as `orders/delete`.

For more information on how groups work, please refer to the [`jest-runner-groups` documentation](https://www.npmjs.com/package/jest-runner-groups).

## Using query strings

For tests that use query strings, these can be passed into the `getRequest()` method using an object of one or more key value pairs:

```javascript
const { getRequest } = require('./utils/request');

const queryString = {
  dates_are_gmt: true,
  after: '2021-05-13T19:00:00',
  before: '2021-05-13T22:00:00'
};

const response = await getRequest('/orders', queryString);
```

## Debugging tests

You can make use of the REST API log plugin to see how requests are being made, and check the request payload, response, and more.

[REST API Log](https://wordpress.org/plugins/wp-rest-api-log/)

## Generate a Postman Collection

This package also allows generating a `collection.json` file using the test data in this package. This file can be imported into Postman and other REST clients that support the Postman v2 collection. To generate this file, run:

```
npm run make:collection
```

This will output a `collection.json` file in this directory.

## Resources

This package makes use of the [SuperTest HTTP assertion package](https://www.npmjs.com/package/supertest). For more information on the `response` properties that are available can be found in the [SuperAgent documentation](https://visionmedia.github.io/superagent/#response-properties).

For the list of WooCommerce API endpoints, expected responses, and more, please see the [WooCommerce REST API Documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/).
