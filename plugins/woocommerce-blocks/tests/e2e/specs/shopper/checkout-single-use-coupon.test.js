/**
 * External dependencies
 */
import { withRestApi } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { createCoupon } from '../../utils';
import { SIMPLE_PRODUCT_NAME } from '../../../utils/constants';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `Skipping checkout tests`, () => {} );

let coupon;

beforeAll( async () => {
	coupon = await createCoupon( { usageLimit: 1 } );
	await shopper.block.emptyCart();
} );

afterAll( async () => {
	await withRestApi.deleteCoupon( coupon.id );
	await shopper.block.emptyCart();
} );

describe( 'Shopper → Checkout → Can apply single-use coupon once', () => {
	it( 'allows checkout to apply single-use coupon once', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.applyCouponFromCheckout( coupon.code );

		const discountBlockSelector = '.wc-block-components-totals-discount';
		const discountAppliedCouponCodeSelector =
			'.wc-block-components-totals-discount__coupon-list-item span.wc-block-components-chip__text';
		const discountValueSelector =
			'.wc-block-components-totals-discount .wc-block-components-totals-item__value';

		// Verify that the discount had been applied correctly on the checkout page.
		await page.waitForSelector( discountBlockSelector );
		await expect( page ).toMatchElement( discountValueSelector, {
			text: coupon.amount,
		} );
		await expect( page ).toMatchElement(
			discountAppliedCouponCodeSelector,
			{
				text: coupon.code,
			}
		);

		await shopper.block.placeOrder();
		await expect( page ).toMatch( 'Your order has been received.' );

		// Verify that the discount had been applied correctly on the order confirmation page.
		await expect( page ).toMatchElement( `th`, { text: 'Discount' } );
		await expect( page ).toMatchElement( `span.woocommerce-Price-amount`, {
			text: coupon.amount,
		} );
	} );

	it( 'Prevents checkout applying single-use coupon twice', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.applyCouponFromCheckout( coupon.code );
		await expect( page ).toMatch( 'Coupon usage limit has been reached.' );
	} );
} );
