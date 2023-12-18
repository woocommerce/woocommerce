/**
 * Internal dependencies
 */
import {
	shopper,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../../../../utils';
import { BASE_URL } from '../../../../utils/constants';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// Skips all the tests if it's a WooCommerce Core process environment.
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `Skipping Cart & Checkout tests`, () => {} );
}

describe.skip( 'Shopper → Cart', () => {
	beforeAll( async () => {
		await page.goto( `${ BASE_URL }/?setup_cross_sells` );
		// eslint-disable-next-line jest/no-standalone-expect
		await expect( page ).toMatch( 'Cross-Sells products set up.' );
		await shopper.block.emptyCart();
	} );

	afterAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'User can see Cross-Sells products block', async () => {
		await shopper.block.emptyCart();
		await shopper.block.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await shopper.block.goToCart();
		await page.waitForSelector(
			'.wp-block-woocommerce-cart-cross-sells-block'
		);
		await shopper.block.addCrossSellsProductToCart();
		// To avoid flakiness: Wait until the cart contains two entries.
		await page.waitForSelector(
			'.wp-block-woocommerce-cart-line-items-block tr:nth-child(2)'
		);
		await expect(
			shopper.block.productIsInCart( '32GB USB Stick', 1 )
		).toBeTruthy();
	} );

	it( 'User can proceed to checkout', async () => {
		await shopper.block.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await shopper.block.goToCart();

		// Click on "Proceed to Checkout" button
		await shopper.block.proceedToCheckout();

		// Verify that you see the Checkout Block page
		await expect( page ).toMatchElement( 'h1', { text: 'Checkout' } );
	} );
} );
