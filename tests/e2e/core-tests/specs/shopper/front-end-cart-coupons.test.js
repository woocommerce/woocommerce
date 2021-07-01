/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
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

const couponsTable = [
	['fixed cart', { text: '$5.00' }, { text: '$4.99' }],
	['percentage', { text: '$4.99' }, { text: '$5.00' }],
	['fixed product', { text: '$5.00' }, { text: '$4.99' }]
];

let coupons;

const getCoupon = (couponType) => {
	const coupon = coupons.find((pair) => pair.includes(couponType))[1];
	return coupon;
};

const runCartApplyCouponsTest = () => {
	describe('Cart applying coupons', () => {
		let couponFixedCart;
		let couponPercentage;
		let couponFixedProduct;

		beforeAll(async () => {
			await createSimpleProduct();
			couponFixedCart = await createCoupon();
			couponPercentage = await createCoupon('50', 'Percentage discount');
			couponFixedProduct = await createCoupon('5', 'Fixed product discount');
			coupons = [
				['fixed cart', couponFixedCart],
				['percentage', couponPercentage],
				['fixed product', couponFixedProduct]
			];
			await shopper.emptyCart();
			await shopper.goToShop();
			await shopper.addToCartFromShopPage('Simple product');
			await uiUnblocked();
			await shopper.goToCart();
		});

		it.each(couponsTable)('allows cart to apply %s coupon', async (couponType, cartDiscount, orderTotal) => {
			const coupon = getCoupon(couponType);
			await applyCoupon(coupon);
			await expect(page).toMatchElement('.woocommerce-message', { text: 'Coupon code applied successfully.' });

			// Verify discount applied and order total
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.cart-discount .amount', cartDiscount);
			await expect(page).toMatchElement('.order-total .amount', orderTotal);
			await removeCoupon(coupon);
		});

		it('prevents cart applying same coupon twice', async () => {
			await applyCoupon(couponFixedCart);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});
			await applyCoupon(couponFixedCart);
			// Verify only one discount applied
			// This is a work around for Puppeteer inconsistently finding 'Coupon code already applied'
			await expect(page).toMatchElement('.cart-discount .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$4.99'});
		});

		it('allows cart to apply multiple coupons', async () => {
			await applyCoupon(couponFixedProduct);
			await expect(page).toMatchElement('.woocommerce-message', {text: 'Coupon code applied successfully.'});

			// Verify discount applied and order total
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.order-total .amount', {text: '$0.00'});
		});

		it('restores cart total when coupons are removed', async () => {
			await removeCoupon(couponFixedCart);
			await removeCoupon(couponFixedProduct);
			await expect(page).toMatchElement('.order-total .amount', {text: '$9.99'});
		});
	});
};

module.exports = runCartApplyCouponsTest;
