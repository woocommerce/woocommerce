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

let couponFixedCart;
let couponPercentage;
let couponFixedProduct;

const getCoupon = (couponType) => {
	switch (couponType) {
		case 'fixed cart':
			return couponFixedCart;
		case 'percentage':
			return couponPercentage;
		case 'fixed product':
			return couponFixedProduct;
	}
};

const runCartApplyCouponsTest = () => {
	describe('Cart applying coupons', () => {
		let productId;

		beforeAll(async () => {
			productId = await createSimpleProduct();
			couponFixedCart = await createCoupon();
			couponPercentage = await createCoupon('50', 'Percentage discount');
			couponFixedProduct = await createCoupon('5', 'Fixed product discount');
			await shopper.emptyCart();
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
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
