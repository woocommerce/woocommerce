/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createCoupon,
	createSimpleProduct,
	uiUnblocked
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
			let couponFixedCart;
			let couponPercentage;
			let couponFixedProduct;
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			couponFixedCart = await createCoupon();
			couponPercentage = await createCoupon('50', 'Percentage discount');
			couponFixedProduct = await createCoupon('5', 'Fixed product discount');
			await merchant.logout();
		});

		it('allows customer to apply coupons in the cart', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');
			await shopper.goToCart();
			await shopper.productIsInCart('Simple product');

			// Apply Fixed cart discount coupon
			await expect(page).toFill('#coupon_code', couponFixedCart);
			await expect(page).toClick('button', {text: 'Apply coupon'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Wait for page to expand total calculations to avoid flakyness
			await page.waitForSelector('.order-total');

			// Verify discount applied and order total
			await page.waitForSelector('.cart-discount .amount', {text: '$5.00'});
			await page.waitForSelector('.order-total .amount', {text: '$4.99'});

			// Remove coupon
			await expect(page).toClick('.woocommerce-remove-coupon', {text: '[Remove]'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon has been removed.'});

			// Apply Percentage discount coupon
			await expect(page).toFill('#coupon_code', couponPercentage);
			await expect(page).toClick('button', {text: 'Apply coupon'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await page.waitForSelector('.cart-discount .amount', {text: '$4.99'});
			await page.waitForSelector('.order-total .amount', {text: '$5.00'});

			// Remove coupon
			await expect(page).toClick('.woocommerce-remove-coupon', {text: '[Remove]'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon has been removed.'});

			// Apply Fixed product discount coupon
			await expect(page).toFill('#coupon_code', couponFixedProduct);
			await expect(page).toClick('button', {text: 'Apply coupon'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await page.waitForSelector('.cart-discount .amount', {text: '$5.00'});
			await page.waitForSelector('.order-total .amount', {text: '$4.99'});

			// Try to apply the same coupon
			await expect(page).toFill('#coupon_code', couponFixedProduct);
			await expect(page).toClick('button', {text: 'Apply coupon'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-error', { text: 'Coupon code already applied!' });

			// Try to apply multiple coupons
			await expect(page).toFill('#coupon_code', couponFixedCart);
			await expect(page).toClick('button', {text: 'Apply coupon'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await page.waitForSelector('.order-total .amount', {text: '$0.00'});

			// Remove coupon
			await expect(page).toClick('.woocommerce-remove-coupon', {text: '[Remove]'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon has been removed.'});
			await expect(page).toClick('.woocommerce-remove-coupon', {text: '[Remove]'});
			await uiUnblocked();
			await page.waitForSelector('.woocommerce-message', {text: 'Coupon has been removed.'});

			// Verify the total amount after all coupons removal
			await page.waitForSelector('.order-total .amount', {text: '$9.99'});
		});
	});
};

module.exports = runCartApplyCouponsTest;
