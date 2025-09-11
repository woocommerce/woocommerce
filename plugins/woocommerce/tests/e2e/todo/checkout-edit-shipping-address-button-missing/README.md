# TODO: Checkout Test Failure - Edit Shipping Address Button Missing

## Test
`tests/checkout/checkout.spec.js:381:2` - customer can login at checkout and place the order with a different shipping address blocks checkout

## Issue
The test fails with timeout trying to click "Edit shipping address" button that doesn't exist.

## Error
```
TimeoutError: locator.click: Timeout 20000ms exceeded.
waiting for getByRole('button', { name: 'Edit shipping address' })
at tests/checkout/checkout.spec.js:449:7
```

## Analysis
After customer logs in at checkout, the test expects to see an "Edit shipping address" button to click before filling in the shipping form. However, the checkout block shows the shipping address form already in editable state, so there's no "Edit" button to click.

This appears to be a difference in UI behavior between the expected state (collapsed address that needs editing) and actual state (address form already shown and editable).

## Screenshot
See `test-failed-1.png` - Shows the checkout page with shipping address form already visible and editable, no "Edit shipping address" button present.

## Possible Solutions
1. Update test to check if shipping form is already visible/editable before trying to click edit button
2. Investigate if this is a regression in WooCommerce checkout blocks behavior
3. Check if there's a setting or condition that affects whether addresses are collapsed vs expanded

## Status
- Test works fine for classic checkout (test #11 passes)
- Only fails for blocks checkout variant
- Other checkout tests pass successfully (11/12 passing)