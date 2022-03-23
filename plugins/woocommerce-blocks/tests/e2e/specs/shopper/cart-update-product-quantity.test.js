/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { SIMPLE_PRODUCT_NAME } from '../../../utils/constants';

const block = {
	name: 'Cart',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( 'Shopper → Cart → Can update product quantity', () => {
	beforeAll( async () => {
		await shopper.block.emptyCart();
	} );

	afterAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'allows customer to update product quantity via the input field', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCart();

		await shopper.block.setCartQuantity( SIMPLE_PRODUCT_NAME, 4 );
		await expect( page ).toMatchElement(
			'button.wc-block-cart__submit-button[disabled]'
		);

		// To avoid flakiness: The default "idleTime: 500" fails in headless mode
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await expect( page ).toMatchElement( 'a.wc-block-cart__submit-button' );

		await shopper.block.productIsInCart( SIMPLE_PRODUCT_NAME, 4 );
	} );

	it( 'allows customer to increase product quantity via the plus button', async () => {
		await shopper.block.increaseCartQuantityByOne( SIMPLE_PRODUCT_NAME );
		await expect( page ).toMatchElement(
			'button.wc-block-cart__submit-button[disabled]'
		);

		// To avoid flakiness: The default "idleTime: 500" fails in headless mode
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await expect( page ).toMatchElement( 'a.wc-block-cart__submit-button' );

		await shopper.block.productIsInCart( SIMPLE_PRODUCT_NAME, 5 );
	} );

	it( 'allows customer to decrease product quantity via the minus button', async () => {
		await shopper.block.decreaseCartQuantityByOne( SIMPLE_PRODUCT_NAME );
		await expect( page ).toMatchElement(
			'button.wc-block-cart__submit-button[disabled]'
		);

		// To avoid flakiness: The default "idleTime: 500" fails in headless mode
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await expect( page ).toMatchElement( 'a.wc-block-cart__submit-button' );

		await shopper.block.productIsInCart( SIMPLE_PRODUCT_NAME, 4 );
	} );
} );
