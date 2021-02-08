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

Follow [E2E setup instructions](https://github.com/woocommerce/woocommerce/blob/master/tests/e2e/README.md).

### Setting up core tests

- Create the folder `tests/e2e/specs` in your repository if it does not exist.
- To add a core test to your test suite, create a new `.test.js` file within `tests/e2e/specs` . Example code to run all the shopper tests:
```js

const { runShopperTests } = require( '@woocommerce/e2e-core-tests' );

runShopperTests();

```

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
  - `runCreateCouponTest` - Merchant can create coupon
  - `runCreateOrderTest` - Merchant can create order
  - `runAddSimpleProductTest` - Merchant can create a simple product
  - `runAddVariableProductTest` - Merchant can create a variable product
  - `runUpdateGeneralSettingsTest` - Merchant can update general settings
  - `runProductSettingsTest` - Merchant can update product settings
  - `runTaxSettingsTest` - Merchant can update tax settings
  - `runOrderStatusFilterTest` - Merchant can filter orders by order status
  - `runOrderRefundTest` - Merchant can refund an order
  - `runOrderApplyCouponTest` - Merchant can apply a coupon to an order

### Shopper

- `runShopperTests` - Run all shopper tests
  - `runCartPageTest` - Shopper can view and update cart
  - `runCheckoutPageTest` - Shopper can complete checkout
  - `runMyAccountPageTest` - Shopper can access my account page
  - `runSingleProductPageTest` - Shopper can view single product page

## Contributing a new test

- In your branch create a new `example-test-name.test.js` under the `tests/e2e/core-tests/specs` folder.
- Jest does not allow its global functions to be accessed outside the jest environment. To allow the test code to be published in a package import any jest global functions used in your test
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
- Add your test to `tests/e2e/core-tests/specs/index.js`
```js
const runExampleTestName = require( './grouping/example-test-name.test' );
// ...
module.exports = {
// ...
    runExampleTestName,
}
```
