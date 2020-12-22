/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect, jest/no-standalone-expect */
/**
 * Internal dependencies
 */
const {
    CustomerFlow,
    createCoupon,
    createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

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
            await createSimpleProduct();
            await createCoupon('Fixed cart discount', '1');
            await createCoupon('Percentage discount', '1');
            await createCoupon('Fixed product discount', '1');
		});

		it('allows customer to apply fixed cart coupon in the cart', async () => {
            await CustomerFlow.goToShop();
			await CustomerFlow.addToCartFromShopPage(simpleProductName);
			await CustomerFlow.goToCart();
		});
	});
};

module.exports = runCartApplyCouponsTest;
