/**
 * Internal dependencies
 */
const {
	shopper,
	createSimpleProduct,
	uiUnblocked,
	applyCoupon,
	removeCoupon,
} = require( '@woocommerce/e2e-utils' );
const { getCouponId, getCouponsTable } = require( '../utils/coupons' );

/**
 * External dependencies
 */
const { it, describe, beforeAll } = require( '@jest/globals' );

const runCartApplyCouponsTest = () => {
	describe( 'Cart applying coupons', () => {
		let productId;

		beforeAll( async () => {
			productId = await createSimpleProduct();
			await shopper.emptyCart();
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await uiUnblocked();
			await shopper.goToCart();
		} );

		it.each( getCouponsTable() )(
			'allows cart to apply %s coupon',
			async ( couponType, cartDiscount, orderTotal ) => {
				const coupon = await getCouponId( couponType );
				await applyCoupon( coupon );
				await expect( page ).toMatchElement( '.woocommerce-message', {
					text: 'Coupon code applied successfully.',
				} );

				// Verify discount applied and order total
				await page.waitForSelector( '.order-total' );
				await expect( page ).toMatchElement(
					'.cart-discount .amount',
					cartDiscount
				);
				await expect( page ).toMatchElement(
					'.order-total .amount',
					orderTotal
				);
				await removeCoupon( coupon );
			}
		);

		it( 'prevents cart applying same coupon twice', async () => {
			const couponId = await getCouponId( 'fixed cart' );
			await applyCoupon( couponId );
			await expect( page ).toMatchElement( '.woocommerce-message', {
				text: 'Coupon code applied successfully.',
			} );
			await applyCoupon( couponId );
			// Verify only one discount applied
			// This is a work around for Puppeteer inconsistently finding 'Coupon code already applied'
			await expect( page ).toMatchElement( '.cart-discount .amount', {
				text: '$5.00',
			} );
			await expect( page ).toMatchElement( '.order-total .amount', {
				text: '$4.99',
			} );
		} );

		it( 'allows cart to apply multiple coupons', async () => {
			await applyCoupon( await getCouponId( 'fixed product' ) );
			await expect( page ).toMatchElement( '.woocommerce-message', {
				text: 'Coupon code applied successfully.',
			} );

			// Verify discount applied and order total
			await page.waitForSelector( '.order-total' );
			await expect( page ).toMatchElement( '.order-total .amount', {
				text: '$0.00',
			} );
		} );

		it( 'restores cart total when coupons are removed', async () => {
			await removeCoupon( await getCouponId( 'fixed cart' ) );
			await removeCoupon( await getCouponId( 'fixed product' ) );
			await expect( page ).toMatchElement( '.order-total .amount', {
				text: '$9.99',
			} );
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runCartApplyCouponsTest;
