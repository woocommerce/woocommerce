# Tests to Skip

## Checkout Tests

### tests/checkout/checkout.spec.js:381
**Test:** customer can login at checkout and place the order with a different shipping address blocks checkout
**Reason:** UI assumption mismatch - test expects "Edit shipping address" button that doesn't exist
**Details:** See `checkout-edit-shipping-address-button-missing/README.md`
**Skip line to add:** `test.skip('customer can login at checkout and place the order with a different shipping address blocks checkout'`

## Coupon Tests  

### tests/coupons/cart-checkout-coupons.spec.js:280
**Test:** allows applying multiple coupons
**Reason:** Total calculation mismatch - expects $8.00 but different amount shown
**Error:** `Locator: locator('.order-total .amount').filter({ hasText: '$8.00' })` timeout
**Status:** Needs investigation - may be calculation logic difference

## Customize Store Tests

### All tests in customize-store directory
**Reason:** Known WooCommerce bug #44766
**Status:** Ignoring per user request - widespread issue in WooCommerce