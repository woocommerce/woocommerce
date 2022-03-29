/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );
}

describe( 'Shopper → Cart → Can view empty cart message', () => {
	beforeAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'Shopper Can view empty cart message', async () => {
		await shopper.block.goToCart();

		// Verify cart is empty'
		await expect( page ).toMatchElement( 'h2', {
			text: 'Your cart is currently empty!',
		} );
	} );
} );
