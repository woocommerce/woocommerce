# WooCommerce Core End to End Test Suite

This package contains the automated end-to-end tests for WooCommerce.

## Table of contents

- [Pre-requisites](#pre-requisites)
- [Setting up core tests](#setting-up-core-tests)
- [Test functions](#test-functions)
    - [Activation and setup](#activation-and-setup)
    - [Merchant](#merchant)
    - [Shopper](#shopper)
- [Contributing a new test](#contributing-a-new-test)

## Pre-requisites

### Setting up the test environment

Follow [E2E setup instructions](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/README.md).

### Setting up core tests

#### Version 0.2.0 or newer

Version 0.2.0 added a test installer that will populate the `tests/e2e/specs` folder with test scripts for all the current core test suite. It also creates sample configuration files including all the configuration data needed to run the core tests.

- Install the e2e-environment `npm install @woocommerce/e2e-environment --save-dev`
- Run the installer `npx wc-e2e install @woocommerce/e2e-core-tests`
- Merge the sample configuration files:
    - `tests/e2e/docker/woocommerce.e2e-core-tests.sh` => `initialize.sh`
    - `tests/e2e/config/default-woocommerce.e2e-core-tests.json` => `default.json`

#### Version 0.1.X or other test runner

- Create the folder `tests/e2e/specs` in your repository if it does not exist.
- To add a core test to your test suite, create a new `.test.js` file within `tests/e2e/specs` . Example code to run all the shopper tests:

```js

const { runShopperTests } = require( '@woocommerce/e2e-core-tests' );

runShopperTests();

```

## Retrying/Re-running tests

On a new site, the setup and activation tests prepare the site for the remainder of the tests. To retry/rerun the test suite on a site where setup/onboarding test have already run use the environment variable `E2E_RETEST=1`.


## Test functions

The functions to access the core tests are:

### Activation and setup

- `runSetupOnboardingTests` - Run all setup and onboarding tests
    - `runActivationTest` - Merchant can activate WooCommerce
    - `runOnboardingFlowTest` - Merchant can complete onboarding flow
    - `runTaskListTest` - Merchant can complete onboarding task list
    - `runInitialStoreSettingsTest` - Merchant can complete initial settings

### Merchant

- `runMerchantTests` - Run all merchant tests
    - `runAddShippingClassesTest` - Merchant can create shipping classes and let shopper test them
    - `runAddNewShippingZoneTest` - Merchant can create shipping zones and let shopper test them
    - `runAddSimpleProductTest` - Merchant can create a simple product
    - `runAddVariableProductTest` - Merchant can create a variable product
    - `runCreateCouponTest` - Merchant can create coupon
    - `runCreateOrderTest` - Merchant can create order
    - `runMerchantOrdersCustomerPaymentPage` - Merchant can visit the customer payment page
    - `runMerchantOrderEmailsTest` - Merchant can receive order emails and resend emails by Order Actions
    - `runEditOrderTest` - Merchant can edit an order in the dashboard
    - `runOrderStatusFilterTest` - Merchant can filter orders by order status
    - `runOrderRefundTest` - Merchant can refund an order
    - `runOrderApplyCouponTest` - Merchant can apply a coupon to an order
    - `runOrderSearchingTest` - Merchant can search for order via different terms
    - `runProductEditDetailsTest` - Merchant can edit an existing product
    - `runProductSearchTest` - Merchant can search for a product and view it
    - `runProductSettingsTest` - Merchant can update product settings
    - `runTaxSettingsTest` - Merchant can update tax settings
    - `runUpdateGeneralSettingsTest` - Merchant can update general settings
    - `runMerchantOrderEmailsTest` - Merchant can receive order emails and resend emails by Order Actions
    - `runAnalyticsPageLoadsTest` - Merchant can load and see all pages in Analytics
    - `runImportProductsTest` - Merchant can import products via CSV file
    - `runInitiateWccomConnectionTest` - Merchant can initiate connection to Woo.com
    - `runAdminPageLoadTests` - Merchant can load pages from the WP Admin sidebar

### Shopper

- `runShopperTests` - Run all shopper tests
    - `runCartApplyCouponsTest` - Shopper can use coupons on cart
    - `runCartPageTest` - Shopper can view and update cart
    - `runCheckoutApplyCouponsTest` - Shopper can use coupons on checkout
    - `runCheckoutPageTest` - Shopper can complete checkout
    - `runMyAccountPageTest` - Shopper can access my account page
    - `runMyAccountPayOrderTest` - Shopper can pay for their order in My Account
    - `runProductBrowseSearchSortTest` - Shopper can browse, search & sort products
    - `runSingleProductPageTest` - Shopper can view single product page in many variations (simple, variable, grouped)
    - `runVariableProductUpdateTest` - Shopper can view and update variations on a variable product
    - `runCheckoutCreateAccountTest` - Shopper can create an account during checkout
    - `runCheckoutLoginAccountTest` - Shopper can login to an account during checkout
    - `runMyAccountCreateAccountTest` - Shopper can create an account via my account page
    - `runCartCalculateShippingTest` - Shopper can calculate shipping in the cart
    - `runCartRedirectionTest` - Shopper is redirected to the cart page after adding to cart
    - `runOrderEmailReceivingTest` - Shopper can receive an email for his order
    - `runCartAndCheckoutConsistentShippingTest` - Shopper gets consistent shipping information on cart and checkout pages

### REST API

- `runApiTests` - Run all API tests
    - `runExternalProductAPITest` - Can create, read, and delete an external product
    - `runGroupedProductAPITest` - Can create, read, and delete a grouped product
    - `runVariableProductAPITest` - Can create, read, and delete a variable product and its variations
    - `runCouponApiTest` - Can create, read, and delete a coupon
    - `runOrderApiTest` - Can create, read, and delete an order

## Contributing a new test

- In your branch create a new `example-test-name.test.js` under the appropriate folder in the [`specs`](specs) directory.
- Jest does not allow its global functions to be accessed outside the jest environment. To allow the test code to be published in a package import any jest global functions used in your test.

```js
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );
```

- Wrap your test in a function and export it

```js
const runExampleTestName = () => {
	describe('Example test', () => {
		beforeAll(async () => {
			// ...
		});

		it('do some example action', async () => {
            // ...
		});
        // ...
    });
});

module.exports = runExampleTestName;
```

- Add your test to [`specs/index.js`](specs/index.js)

```js
const runExampleTestName = require( './grouping/example-test-name.test' );
// ...
module.exports = {
// ...
    runExampleTestName,
}
```
