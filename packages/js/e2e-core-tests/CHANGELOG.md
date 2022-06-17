# Unreleased

## Fixed

- Updated assertion in the block `can update order details` from the e2e test `order-edit.test.js` that wasn't checking properly the date value when editing an order, allowing the test to return a false positive.
- Moved `merchant.login()` out of `beforeAll()` block and into test body for retried runs.

## Added

- Additional Merchant Product Edit tests to increase the variable products coverage.
- Additional Merchant Order Edit tests to increase the downloadable products coverage.
- A `specs/data` folder to store page element data.
- Tests to verify that different top-level menus and their associated sub-menus load successfully.
- Test scaffolding via `npx wc-e2e install @woocommerce/e2e-core-tests`
- Tests to check shipping cost calculation consistency between cart and checkout pages.

## Changed

- The e2e test `update-product-settings.test.js` now covers setting and unsetting the `X-Accel-Redirect/X-Sendfile` download method and `Append a unique string to filename for security` flag.
- The e2e test `order-edit.test.js` now uses the API to create orders.
- New coupon test deletes the coupon instead of trashing it.
- A copy of sample_data.csv is included in the package.
- Removed `faker` dependency

# 0.1.6

## Fixed

- Fatal error in order filter test

# 0.1.5

## Added

- Support for re-running setup and shopper tests
- Shopper Order Email Receiving
- New tests - See [README.md](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/e2e-core-tests/README.md) for list of available tests

## Fixed

- Checkout create account test would fail if configuration value `addresses.customer.billing.email` was not `john.doe@example.com` 

## Changed
- The e2e test `order-status-filters.test.js` now uses the API to create orders
- Implemented data-driven design using `describe.each` and `it.each`


# 0.1.3

## Added

- Shopper Checkout Login Account
- Shopper My Account Create Account
- Shopper Cart Calculate Shipping
- Shopper Cart Redirection

## Fixed

- removed use of ES6 `import`

# 0.1.2

## Added

- api package test for variable products and product variations
- api package test for grouped products
- api package test for external products
- api package test for coupons
- Registered Shopper Checkout tests
- Merchant Product Edit tests
- Merchant Product Search tests
- Shopper Single Product tests
- Shopper My Account Pay Order
- Shopper Shop Browse Search Sort
- Merchant Orders Customer Checkout Page
- Shopper Cart Apply Coupon
- Merchant Order Searching
- Merchant Settings Shipping Zones
- Shopper Variable product info updates on different variations
- Merchant order emails flow
- Merchant analytics page load tests
- Shopper Checkout Create Account
- Merchant import products via CSV

# 0.1.1

## Added

- Merchant Order Status Filter tests
- Merchant Order Refund tests
- Merchant Apply Coupon tests
- Added new config variable for Simple Product price to `tests/e2e/env/config/default.json`. Defaults to 9.99
- Shopper Checkout Apply Coupon

## Fixed

- Flaky Create Product, Coupon, and Order tests
- Missing `config` package dependency

# 0.1.0

- Initial/beta release
