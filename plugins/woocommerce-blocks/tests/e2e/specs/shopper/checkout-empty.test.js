/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `Skipping checkout tests`, () => {} );

describe( 'Shopper → Checkout → Can view empty checkout message', () => {
	beforeAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'Shopper can view empty cart message on the checkout page', async () => {
		await shopper.block.goToCheckout();
		// Verify cart is empty'
		await expect( page ).toMatchElement(
			'strong.wc-block-checkout-empty__title',
			{
				text: 'Your cart is empty!',
			}
		);
	} );
} );
