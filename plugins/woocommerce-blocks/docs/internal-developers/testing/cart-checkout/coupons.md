# Coupons

## Setup

To conduct these tests, please set up the following coupons:

- `generalCoupon`: A general-purpose coupon
- `expiredCoupon`: An expired coupon
- `emailRestrictedCoupon`: An email-limited coupon, applicable to `*@automattic.com` emails
- `thresholdCoupon`: A cart condition coupon, applicable to carts above a certain threshold (e.g., $200)
- `individualCoupon`: An individually used coupon
- `freeShippingCoupon`: A free shipping coupon

## Test cases

To execute the following test cases, you will need to toggle the coupon functionality. You can enable or disable coupons via `WP Admin » WooCommerce » Settings » General » Enable coupons`:

<img width="650" alt="Screenshot 2023-09-27 at 21 26 30" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/b79cbc87-0609-4306-90a0-e6666f738433">

### With coupons disabled

- [ ] Verify that the 'Add Coupon' section is not visible in your cart, checkout, and in the editor.

### With coupons enabled

- [ ] Ensure the ability to apply coupons in both Cart and Checkout blocks.
- [ ] A valid coupon, `generalCoupon`, should accurately reduce your totals.
- [ ] An expired coupon, `expiredCoupon`, should generate an error upon application.
- [ ] Attempting to apply an invalid coupon should generate an error message.
- [ ] An email-limited coupon, `emailRestrictedCoupon`, should be applicable to your cart.
    - [ ] If the email is correct, checkout should proceed without errors.
    - [ ] If the email is incorrect, an error message should appear, and the coupon should be removed from your cart.
- [ ] A condition coupon, `thresholdCoupon`, should only be applicable once the stipulated condition is met.
    - [ ] If a condition coupon is added and subsequently the condition is unmet, it should be removed from your cart with a corresponding error message.
- [ ] A valid coupon, `generalCoupon`, when followed by an individually used coupon, `individualCoupon`, should override the first one.
- [ ] An individually used coupon, `individualCoupon`, when followed by an attempt to add another coupon, should generate an error message.
- [ ] A free shipping coupon, `freeShippingCoupon`, should display the free shipping method you previously configured.
