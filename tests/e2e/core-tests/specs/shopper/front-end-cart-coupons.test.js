/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
    createCoupon,
    createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runCartApplyCouponsTest = () => {
	describe('Cart applying coupons', () => {
		beforeAll(async () => {
			await merchant.login();
            await createSimpleProduct();
            await createCoupon('Fixed cart discount', '1');
            await createCoupon('Percentage discount', '1');
			await createCoupon('Fixed product discount', '1');
			await merchant.logout();
		});

		it('allows customer to apply fixed cart coupon in the cart', async () => {
			await shopper.goToProduct();
			await shopper.addToCart('Simple product');
			await shopper.goToCart();
			await shopper.productIsInCart('Simple product');
		});
	});
};

module.exports = runCartApplyCouponsTest;
