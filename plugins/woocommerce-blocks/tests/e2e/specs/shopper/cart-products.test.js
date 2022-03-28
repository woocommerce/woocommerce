/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { SIMPLE_PRODUCT_NAME } from '../../../utils/constants';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( 'Shopper → Cart → Can remove product', () => {
	beforeAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'Can remove product from cart', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCart();
		const removeProductLink = await page.$(
			'.wc-block-cart-item__remove-link'
		);

		await removeProductLink.click();
		// we need to wait to ensure the cart is updated
		await page.waitForSelector( '.wc-block-cart__empty-cart__title' );

		// Verify product is removed from the cart'
		await expect( page ).toMatchElement( 'h2', {
			text: 'Your cart is currently empty!',
		} );
	} );
} );
