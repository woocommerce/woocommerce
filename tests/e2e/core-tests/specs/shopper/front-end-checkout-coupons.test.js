/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect, jest/no-standalone-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	createCoupon,
	createSimpleProduct,
	uiUnblocked,
	applyCoupon,
	removeCoupon,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runCheckoutApplyCouponsTest = () => {
	describe('Checkout coupons', () => {
		let couponFixedCart;
		let couponPercentage;
		let couponFixedProduct;

		beforeAll(async () => {
			await createSimpleProduct();
			couponFixedCart = await createCoupon();
			couponPercentage = await createCoupon('50', 'Percentage discount');
			couponFixedProduct = await createCoupon('5', 'Fixed product discount');
			await shopper.emptyCart();
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');
			await uiUnblocked();
			await shopper.goToCheckout();
		});

		it('allows checkout to apply fixed cart coupon', async () => {
			await applyCoupon(couponFixedCart);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Wait for page to expand total calculations to avoid flakyness
			await page.waitForSelector('.order-total');

			// Verify discount applied and order total
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$4.99'});
			await removeCoupon(couponFixedCart);
		});

		it('allows checkout to apply percentage coupon', async () => {
			await applyCoupon(couponPercentage);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Verify discount applied and order total
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$4.99'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$5.00'});
			await removeCoupon(couponPercentage);
		});

		it('allows checkout to apply fixed product coupon', async () => {
			await applyCoupon(couponFixedProduct);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Verify discount applied and order total
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$4.99'});
			await removeCoupon(couponFixedProduct);
		});

		it('prevents checkout applying same coupon twice', async () => {
			await applyCoupon(couponFixedCart);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await applyCoupon(couponFixedCart);
			// Verify only one discount applied
			// This is a work around for Puppeteer inconsistently finding 'Coupon code already applied'
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$4.99'});
		});

		it('allows checkout to apply multiple coupons', async () => {
			await applyCoupon(couponFixedProduct);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Verify discount applied and order total
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.order-total .amount', {text: '$0.00'});
		});

		it('restores checkout total when coupons are removed', async () => {
			await removeCoupon(couponFixedCart);
			await removeCoupon(couponFixedProduct);
			await expect(page).toMatchElement('.order-total .amount', {text: '$9.99'});
		});
	});
};

module.exports = runCheckoutApplyCouponsTest;
