/**
 * Internal dependencies
 */
import { shopper, SIMPLE_VIRTUAL_PRODUCT_NAME } from '../../../../utils';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// Skips all the tests if it's a WooCommerce Core process environment.
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `Skipping Cart & Checkout tests`, () => {} );
}

describe( 'Shopper → Cart', () => {
	beforeAll( async () => {
		await shopper.block.emptyCart();
	} );

	afterAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'User can view empty cart message', async () => {
		await shopper.block.goToCart();

		await page.waitForSelector( '.wc-block-cart__empty-cart__title' );

		// Verify cart is empty'
		await expect( page ).toMatchElement(
			'.wc-block-cart__empty-cart__title',
			{
				text: 'Your cart is currently empty!',
			}
		);
	} );

	it( 'User can remove a product from cart', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
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

	it( 'User can update product quantity via the input field', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await shopper.block.goToCart();
		await shopper.block.setCartQuantity( SIMPLE_VIRTUAL_PRODUCT_NAME, 4 );

		await expect( page ).toMatchElement(
			'button.wc-block-cart__submit-button[disabled]'
		);

		// To avoid flakiness: The default "idleTime: 500" fails in headless mode
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await expect( page ).toMatchElement( 'a.wc-block-cart__submit-button' );

		await shopper.block.productIsInCart( SIMPLE_VIRTUAL_PRODUCT_NAME, 4 );
	} );

	it( 'User can increase product quantity via the plus button', async () => {
		await shopper.block.increaseCartQuantityByOne(
			SIMPLE_VIRTUAL_PRODUCT_NAME
		);
		await expect( page ).toMatchElement(
			'button.wc-block-cart__submit-button[disabled]'
		);

		// To avoid flakiness: The default "idleTime: 500" fails in headless mode
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await expect( page ).toMatchElement( 'a.wc-block-cart__submit-button' );

		await shopper.block.productIsInCart( SIMPLE_VIRTUAL_PRODUCT_NAME, 5 );
	} );

	it( 'User can decrease product quantity via the minus button', async () => {
		await shopper.block.decreaseCartQuantityByOne(
			SIMPLE_VIRTUAL_PRODUCT_NAME
		);
		await expect( page ).toMatchElement(
			'button.wc-block-cart__submit-button[disabled]'
		);

		// To avoid flakiness: The default "idleTime: 500" fails in headless mode
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await expect( page ).toMatchElement( 'a.wc-block-cart__submit-button' );

		await shopper.block.productIsInCart( SIMPLE_VIRTUAL_PRODUCT_NAME, 4 );
	} );

	it( 'User can proceed to checkout', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await shopper.block.goToCart();

		// Click on "Proceed to Checkout" button
		await shopper.block.proceedToCheckout();

		// Verify that you see the Checkout Block page
		await expect( page ).toMatchElement( 'h1', { text: 'Checkout' } );
	} );
} );
