/**
 * External dependencies
 */
import { verifyCheckboxIsSet } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { SIMPLE_PRODUCT_NAME, BILLING_DETAILS } from '../../../utils/constants';

const block = {
	name: 'Checkout',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( 'Shopper → Checkout → Can place an order as guest', () => {
	beforeEach( async () => {
		await shopper.block.emptyCart();
	} );

	afterAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'allows customer to place an order as guest', async () => {
		await shopper.logout();
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.fillBillingDetails( BILLING_DETAILS );

		// Select the "Cash on delivery" payment method & verify it was set
		await expect( page ).toClick(
			'.wc-block-components-payment-method-label',
			{
				text: 'Cash on delivery',
			}
		);
		await verifyCheckboxIsSet(
			'#radio-control-wc-payment-method-options-cod'
		);

		// Click on the "Place Order" button
		await shopper.block.placeOrder();

		// Verify that the order was successful
		await expect( page ).toMatch(
			'Thank you. Your order has been received.'
		);
	} );
} );
