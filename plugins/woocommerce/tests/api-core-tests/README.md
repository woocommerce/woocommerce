# WooCommerce Core API Test Suite

This package contains automated API tests for WooCommerce, based on Playwright and `wp-env`. It supersedes the SuperTest based [api-core-tests package](https://www.npmjs.com/package/@woocommerce/api-core-tests) and e2e-environment [setup](../tests/e2e), which we will gradually deprecate.

## Table of contents

- [Pre-requisites](#pre-requisites)
- [Introduction](#introduction)
- [About the Environment](#about-the-environment)
- [Test Variables](#test-variables)
- [Guide for writing tests](#guide-for-writing-tests)
  - [What aspects of the API should we test?](#what-aspects-of-the-api-should-we-test)
  - [Creating test structure](#creating-test-structure)
  - [Test Data Setup/Teardown](#test-data-setupteardown)
  - [Writing the test - A Quick Start Guide](#writing-the-test---a-quick-start-guide)
  - [Examples](#examples)
  - [Debugging tests](#debugging-tests)
- [Guide for using test reports](#guide-for-using-test-reports)
  - [Viewing the Playwright HTML report](#viewing-the-playwright-html-report)
  - [Viewing the Allure report](#viewing-the-allure-report)

## Pre-requisites

- Node.js ([Installation instructions](https://nodejs.org/en/download/))
- NVM ([Installation instructions](https://github.com/nvm-sh/nvm))
- PNPM ([Installation instructions](https://pnpm.io/installation))
- Docker and Docker Compose ([Installation instructions](https://docs.docker.com/engine/install/))

Note, that if you are on Mac and you install Docker through other methods such as homebrew, for example, your steps to set it up might be different. The commands listed in steps below may also vary.

If you are using Windows, we recommend using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for running tests. Follow the [WSL Setup Instructions](../tests/e2e/WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

### Introduction

WooCommerce's `api-core-tests` are powered by Playwright. The test site is spun up using `wp-env` (recommended), but we will continue to support `e2e-environment` in the meantime.

**Running tests for the first time:**

- `nvm use`
- `pnpm install`
- `pnpm run build --filter=woocommerce`
- `cd plugins/woocommerce`
- `pnpm env:test`
- `pnpm test:api-pw`

To run the test again, re-create the environment to start with a fresh state 

- `pnpm env:destroy`
- `pnpm env:test`
- `pnpm test:api-pw`

Other ways of running tests:

- `pnpm test:api-pw ./tests/api-core-tests/tests/hello/hello.test.js` (running a single test file)
- `pnpm test:api-pw ./tests/api-core-tests/tests/hello` (running all tests in a single folder)

To see all options, run `cd plugins/woocommerce && pnpm playwright test --help`

## Environment variables

The following environment variables can be configured as shown in `.env.example`:

```
# Your site's base URL, not including a trailing slash
BASE_URL="https://mysite.com"

# The admin user's username or generated consumer key
USER_KEY=""

# The admin user's password or generated consumer secret
USER_SECRET=""
```

For local setup, create a `.env` file in the `woocommerce/plugins/woocommerce/tests/api-core-tests` folder with the three required values described above. If any of these variables are configured they will override the values automatically set in the `playwright.config.js`

When using a username and password combination instead of a consumer secret and consumer key, make sure to have the [JSON Basic Authentication plugin](https://github.com/WP-API/Basic-Auth) installed and activated on the test site.

For more information about authentication with the WooCommerce API, please see the [Authentication](https://woocommerce.github.io/woocommerce-rest-api-docs/?javascript#authentication) section in the WooCommerce REST API documentation.

### About the environment

The default values are:

- Latest stable WordPress version
- PHP 7.4
- MariaDB
- URL: `http://localhost:8086/`
- Admin credentials: `admin/password`

If you want to customize these, check the [Test Variables](#test-variables) section.


For more information how to configure the test environment for `wp-env`, please checkout the [documentation](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) documentation.

### Test Variables

The test environment uses the following test variables:

```json
{ 
  "url": "http://localhost:8086/",
  "users": {
    "admin": {
      "username": "admin",
      "password": "password"
    },
    "customer": {
      "username": "customer",
      "password": "password"
    }
  }
}
```

If you need to modify the port for your local test environment (eg. port is already in use) or use, edit [playwright.config.js](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/playwright.config.js). Depending on what environment tool you are using, you will need to also edit the respective `.json` file.

**Modify the port wp-env**

Edit [.wp-env.json](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/.wp-env.json) and [playwright.config.js](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/playwright.config.js).

**Modify port for e2e-environment**

Edit [tests/e2e/config/default.json](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/config/default.json).****

### Starting/stopping the environment

After you run a test, it's best to restart the environment to start from a fresh state. We are currently working to reset the state more efficiently to avoid the restart being needed, but this is a work-in-progress.

- `pnpm env:down` to stop the environment
- `pnpm env:destroy` when you make changes to `.wp-env.json`
- `pnpm env:test` to spin up the test environment

## Guide for writing tests

When writing new tests, a good source on how to get started is to reference the [existing tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/api-core-tests/tests). Data that is required for the tests should be located in an equivalent file in the [data](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/api-core-tests/data) folder.

Good examples to reference are the `coupons` and `customers` tests. The [Quick Start Guide](#writing-the-test---a-quick-start-guide) below has the key steps to put together a test and examples of how those steps were implemented for `coupons` and `customers`.

The [Playwright documentation](https://playwright.dev/docs/intro) is a good source for finding out more details on the various methods used in the tests, including what is available to you when you write new tests. The [API testing](https://playwright.dev/docs/test-api-testing) section has a good example on how the [playwright.config.js](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/api-core-tests/playwright.config.js) is used (see [Configuration](https://playwright.dev/docs/test-api-testing#configuration) section) and also how [Setup and Teardown](https://playwright.dev/docs/test-api-testing#setup-and-teardown) works. 

Note: Playwright uses the [expect](https://jestjs.io/docs/expect) library for test assertions. This library provides a lot of matchers like `toEqual`, `toContain`, `toHaveLength` and many more. Examples of these are throughout the test files.

## What aspects of the API should we test?

Assuming that we have validated the API contract (inspected the spec/contract, made sure the endpoints are correctly named, resources and types reflect the object model and there is no missing/duplicate functionality) we are ready to test. 

A test contains 3 different stages:
1. `precondition`
2. `action`  
3. `validation`

and in the case of API testing, this equates to:
1. `data creation` - see [Test Data Setup Examples](#test-data-setup-examples) below
2. `send API request` - see [Request Examples](#request-examples) below
3. `response validation` - see [Validation Examples](#validation-examples) below

For each API request method (`GET`, `POST`, `PUT`, `DELETE`, `PATCH`), the test would need to take the following actions: 
1. Verify correct HTTP status code. For example, creating a resource should return 201 CREATED and un-permitted requests should return 403 FORBIDDEN, etc.
2. Verify response payload. Check the JSON body is valid and field names, types, and values are correct (i.e. check the response payload schema is implemented according to the specification) — including error responses.

3. Verify response headers where appropriate e.g. Some headers hold information related to search totals, pagination values etc. (see [Examples](#examples) below).

At a minimum, we want to ensure each possible CRUD operation can be applied and sufficient assertions have been validated based on the above. 

A reasonable process would be:
1. Create an object using a `POST` request
2. Retrieve the created object using the `GET` request
3. Update the object using a `POST` request
4. Delete the object using a `DELETE`request
5. Test any batch create/update/delete operations (if applicable)

Additional tests can also be added to test the following general test scenarios:

- Basic positive tests (happy paths) - check basic functionality and the acceptance criteria of the API
- Extended positive testing with optional parameters - more thorough testing (can be used for testing a bug/updates/new functionality)
- Negative testing with valid input - expect to gracefully handle problem scenarios with valid user input e.g. trying to add an existing username
- Negative testing with invalid input - expect to gracefully handle problem scenarios with invalid user input e.g. trying to add a username which is null

The intention here is to validate that we get error responses when expected as per specification and the error status code and message are correct as per documentation.

## Creating test structure

The structure of the test serves as a skeleton for the test itself. 

Each test file requires the `@playwright/test` module to be imported as follows:
```js
const { test, expect } = require( '@playwright/test' );
```
You can create a test by using the `test.describe()` and `test()` methods of Playwright:

- [`test.describe()`](https://playwright.dev/docs/api/class-test#test-describe-1) - creates a block that groups together several related tests;
- [`test()`](https://playwright.dev/docs/api/class-test#test-call) - actual method that runs the test.

Based on our example, the test skeleton would look as follows:

```js
test.describe( 'Coupons API tests', () => {
	test( 'can create a coupon', async ( {request} ) => {
    // test to create a coupon here
  } );

	test( 'can retrieve a coupon', async ( {request} ) => {
    // test to retrieve a coupon here
  } );

	test( 'can update a coupon', async ( { request } )  => {
    // test to update a coupon here
  } );
} );
```

Note: you can also nest a `test.describe()` inside a `test.describe()`. Example:

```js
test.describe('Orders API tests: CRUD', () => {
	let orderId; //test variable

	test.describe('Create an order', () => {
		test('can create a pending order by default', async ({request}) => {
      //test code here
    }
```
This allows you to further subgroup tests. When viewing the tests results locally, each test describe 'level' will be separated by `>` in the console as below: 

`Orders API tests: CRUD › Create an order › can create a pending order by default`

## Test Data Setup/Teardown

You may need test data setup prior to the execution of your tests. If so, make sure it is removed after the execution of your tests. This can be achieved with any of the following methods, depending on the needs of the test:

- [`test.beforeAll()`](https://playwright.dev/docs/api/class-test#test-before-all) - runs before all the tests in file/group
- [`test.beforeEach()`](https://playwright.dev/docs/api/class-test#test-before-each) - runs before each test in file/group
- [`test.afterEach()`](https://playwright.dev/docs/api/class-test#test-after-each) - runs after each test in file/group
- [`test.afterAll()`](https://playwright.dev/docs/api/class-test#test-after-all) - runs after all the tests in file/group


## Writing the test - a Quick Start Guide

1. Ensure you have your authentication setup as mentioned in the [Environment Variables](#environment-variables) section above. i.e. 
	> For local setup, create a `.env` file
2. Create `test.js` file inside the tests directory
    - Example for [`coupons`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js) and [`customers`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/api-core-tests/tests/customers/customers-crud.test.js)
3. Import `@playwright/test` module
    - Example for [`coupons`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L1) and [`customers`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/customers/customers-crud.test.js#L1)
4. Group tests with `test.describe()` methods
    - Example for [`coupons`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L10) and [`customers`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/customers/customers-crud.test.js#L20)
5. Add tests with `test()` methods
    - Example for [`coupons`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L14) and [`customers`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/customers/customers-crud.test.js#L93)
6. Separate data where required into files in the `data` directory 
    - Example for [`coupons`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/api-core-tests/data/coupon.js) and [`customers`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/api-core-tests/data/customer.js)
7. Import data required by your tests
    - Example for [`coupons`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L2) and [`customers`](https://github.com/woocommerce/woocommerce/blob/b904fd428db1252f39cb64005f4b627f2a9ac08e/plugins/woocommerce/tests/api-core-tests/tests/customers/customers-crud.test.js#L5)
8. After writing your tests, ensure all tests pass successfully with `pnpm test:api-pw`

If you have made updates to functionality that breaks the tests then the tests should be updated accordingly. Similarly, if there is new functionality added then new tests should be added.

## Examples

Below are examples in our `api-core-tests` including references and typical API test operations.

Playwright [configuration file](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/api-core-tests/playwright.config.js)

Test files [location](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/api-core-tests/tests)

Data files [location](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/api-core-tests/data) 

### Test Data Setup Examples

Setup data with [test.beforeAll()](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L347)
```js
	test.beforeAll( async ( { request } ) => {
		// Create a coupon
		const createCouponResponse = await request.post(
			'/wp-json/wc/v3/coupons/',
			{
				data: testCoupon,
			}
		);
		const createCouponResponseJSON = await createCouponResponse.json();
		testCoupon.id = createCouponResponseJSON.id;
	} );
  ```

Teardown data with [test.afterAll()](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L360)
```js
	// Clean up created coupon and order
	test.afterAll( async ( { request } ) => {
		await request.delete( `/wp-json/wc/v3/coupons/${ testCoupon.id }`, {
			data: { force: true },
		} );
		await request.delete( `/wp-json/wc/v3/orders/${ orderId }`, {
			data: { force: true },
		} );
	} );
  ```
### Request Examples

`GET` [request](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L44)
```js
    //call API to get previously created coupon
		const response = await request.get(
			`/wp-json/wc/v3/coupons/${ couponId }`
		);
```

`POST` [request](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L63)
```js
    //call API to update previously created coupon
		const response = await request.post(
			`/wp-json/wc/v3/coupons/${ couponId }`,
			{
				data: updatedCouponDetails,
			}
		);
```

`PUT` [request](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/orders/order-complex.test.js#L220)
```js
		//ensure tax calculations are enabled
		await request.put(
			'/wp-json/wc/v3/settings/general/woocommerce_calc_taxes',
			{
				data: {
					value: 'yes',
				},
			}
		);
```

`DELETE` [request](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L80)
```js
//call API to delete previously created coupon
		const response = await request.delete(
			`/wp-json/wc/v3/coupons/${ couponId }`,
			{
				data: { force: true },
			}
		);
```

`BATCH` [request](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L119)
```js
    // Batch create 2 new coupons.
		const batchCreatePayload = {
			create: expectedCoupons,
		};

		// call API to batch create coupons
		const batchCreateResponse = await request.post(
			'wp-json/wc/v3/coupons/batch',
			{
				data: batchCreatePayload,
			}
		);
```

### Validation Examples

Verify [Status code](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L30)
```js
		expect( response.status() ).toEqual( 201 );
```

Verify [Response payload](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L33)
```js
//validate the response data
		expect( await response.json() ).toEqual(
			expect.objectContaining( {
				code: testCoupon.code,
				amount: Number( coupon.amount ).toFixed( 2 ),
				discount_type: coupon.discount_type,
			} )
		);
```

Verify [field names](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L142) 
```js
			expect( id ).toBeDefined();
			expect( code ).toEqual( expectedCouponCode );
```

Verify [field types](https://github.com/woocommerce/woocommerce/blob/d19c20491e5a7ade64c8fd530f01e0f3f3f7e29c/plugins/woocommerce/tests/api-core-tests/tests/shipping/shipping-method.test.js#L85)
```js
expect(typeof responseJSON.id).toEqual('string');
```

Verify [field values](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L186) 
```js
expect( updatedCoupons[ 1 ].amount ).toEqual( '25.00' );
```

Verify [field length](https://github.com/woocommerce/woocommerce/blob/4ac1d822ac9082102536b0f7aa9cb0553965adaa/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L136)
```js
expect( actualCoupons ).toHaveLength( expectedCoupons.length );
```

Verify search [headers](https://github.com/woocommerce/woocommerce/blob/d19c20491e5a7ade64c8fd530f01e0f3f3f7e29c/plugins/woocommerce/tests/api-core-tests/tests/orders/orders.test.js#L2703)
```js
// Verify total page count.
			expect( page1.headers()[ 'x-wp-total' ] ).toEqual(
				ORDERS_COUNT.toString()
			);
			expect( page1.headers()[ 'x-wp-totalpages' ] ).toEqual( '3' );
  ```

Verify variable [not undefined](https://github.com/woocommerce/woocommerce/blob/778cb130f27d0dd0dc7da1acb0e89762f81c0f18/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L31)
```js
expect( couponId ).toBeDefined();
```

Verify [response contains an object](https://github.com/woocommerce/woocommerce/blob/778cb130f27d0dd0dc7da1acb0e89762f81c0f18/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L73)
```js
expect( await response.json() ).toEqual(
			expect.objectContaining( updatedCouponDetails )
		);
```

Verify [response contains an array containing an object](https://github.com/woocommerce/woocommerce/blob/778cb130f27d0dd0dc7da1acb0e89762f81c0f18/plugins/woocommerce/tests/api-core-tests/tests/coupons/coupons.test.js#L388)
```js
		expect( responseJSON.coupon_lines[ 0 ].meta_data ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					key: 'coupon_data',
					value: expect.objectContaining( {
						code: testCoupon.code,
					} ),
				} ),
			] )
		);
```

## Debugging tests

The Playwright debugger won't work for the API tests as it is based around GUI interactions.

For now it is simple enough to add `console.log()` statements to output the values of your response/JSON/variables/status etc. Be sure to remove them when done ;) 

You can also use the handy [REST API Log](https://wordpress.org/plugins/wp-rest-api-log/) plugin to see the API request information within WordPress. It displays the details, request headers, query params, body params, body content, response headers and response body information.

For the list of WooCommerce API endpoints, expected responses, and more, please see the [WooCommerce REST API Documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/).

## Guide for using test reports

The tests would generate three kinds of reports after the run:
1. A Playwright HTML report.
1. A Playwright JSON report.
1. Allure results.

By default, they are saved inside the `test-results` folder. If you want to save them in a custom location, just assign the absolute path to the environment variables mentioned in the [Playwright](https://playwright.dev/docs/test-reporters) and [Allure-Playwright](https://www.npmjs.com/package/allure-playwright) documentation.

| Report | Default location | Environment variable for custom location |
| ----------- | ---------------- | ---------------------------------------- |
| Playwright HTML report | `test-results/playwright-report` | `PLAYWRIGHT_HTML_REPORT` |
| Playwright JSON report | `test-results/test-results.json` | `PLAYWRIGHT_JSON_OUTPUT_NAME` |
| Allure results | `test-results/allure-results` | `ALLURE_RESULTS_DIR` |

### Viewing the Playwright HTML report

Use the `playwright show-report $PATH_TO_PLAYWRIGHT_HTML_REPORT` command to open the report. For example, assuming that you're at the root of the WooCommerce monorepo, and that you did not specify a custom location for the report, you would use the following commands:

```bash
cd plugins/woocommerce
pnpm exec playwright show-report tests/api-core-tests/test-results/playwright-report
```

For more details about the Playwright HTML report, see their [HTML Reporter](https://playwright.dev/docs/test-reporters#html-reporter) documentation.

### Viewing the Allure report

This assumes that you're already familiar with reports generated by the [Allure Framework](https://github.com/allure-framework), particularly:
- What the `allure-results` and `allure-report` folders are, and how they're different from each other.
- Allure commands like `allure generate` and `allure open`.

Use the `allure generate` command to generate an HTML report from the `allure-results` directory created at the end of the test run. Then, use the `allure open` command to open it on your browser. For example, assuming that:
- You're at the root of the WooCommerce monorepo
- You did not specify a custom location for `allure-results` (you did not assign a value to `ALLURE_RESULTS_DIR`)
- You want to generate the `allure-report` folder in `plugins/woocommerce/tests/api-core-tests/test-results`

Then you would need to use the following commands:

```bash
cd plugins/woocommerce
pnpm exec allure generate --clean tests/api-core-tests/test-results/allure-results --output tests/api-core-tests/test-results/allure-report
pnpm exec allure open tests/api-core-tests/test-results/allure-report
```

A browser window should open the Allure report.

If you're using [WSL](https://learn.microsoft.com/en-us/windows/wsl/about) however, you might get this message right after running the `allure open` command:
```
Starting web server...
2022-12-09 18:52:01.323:INFO::main: Logging initialized @286ms to org.eclipse.jetty.util.log.StdErrLog
Can not open browser because this capability is not supported on your platform. You can use the link below to open the report manually.
Server started at <http://127.0.1.1:38917/>. Press <Ctrl+C> to exit
```
In this case, take note of the port number (38917 in the example above) and then use it to navigate to `http://localhost`. Taking the example above, you should be able to view the Allure report on http://localhost:38917.

To know more about the allure-playwright integration, see their [GitHub documentation](https://github.com/allure-framework/allure-js/tree/master/packages/allure-playwright).
