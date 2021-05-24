# Unreleased

## Added

- Support for re-running setup and shopper tests
- Shopper Order Email Receiving
- New tests - See [README.md](https://github.com/woocommerce/woocommerce/blob/trunk/tests/e2e/core-tests/README.md) for list of available tests

## Fixed

- Checkout create account test would fail if configuration value `addresses.customer.billing.email` was not `john.doe@example.com` 

# 0.1.3

## Added

- Shopper My Account Create Account
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
- Shopper Checkout Apply Coupon
- Merchant Orders Customer Checkout Page
- Shopper Cart Apply Coupon
- Shopper Variable product info updates on different variations
- Merchant order emails flow
- Shopper Checkout Create Account

- Shopper My Account Pay Order

## Fixed

- Flaky Create Product, Coupon, and Order tests
- Missing `config` package dependency

# 0.1.0

- Initial/beta release
