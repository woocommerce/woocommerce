/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';

const block = {
	name: 'Mini Cart Block',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 3 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( 'Shopper → Mini Cart → Can see the icon with a badge', () => {
	it( 'Shopper can see the Mini Cart icon and it badge on the front end', async () => {
		await shopper.goToBlockPage( block.name );

		await expect( page ).toMatchElement( '.wc-block-mini-cart' );
		await expect( page ).toMatchElement( '.wc-block-mini-cart__button' );
		await expect( page ).toMatchElement(
			'.wc-block-mini-cart__quantity-badge'
		);

		// Make sure the initial quantity is 0.
		await expect( page ).toMatchElement( '.wc-block-mini-cart__amount', {
			text: '$0',
		} );
		await expect( page ).toMatchElement( '.wc-block-mini-cart__badge', {
			text: '0',
		} );
	} );
} );
