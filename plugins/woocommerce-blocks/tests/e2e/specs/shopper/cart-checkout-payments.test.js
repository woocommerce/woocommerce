/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { SIMPLE_PRODUCT_NAME } from '../../../utils/constants';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping Cart & Checkout tests`, () => {} );
}

describe( 'Shopper → Cart/Checkout → Can use express checkout', () => {
	it( 'Express Payment button is available on both Cart & Checkout pages', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCart();
		await shopper.block.mockExpressPaymentMethod();
		// We need to re-render the cart for the express payments block to be updated,
		// so we update the qty, which update one of it's attributes and in turn causes a re-render
		await page.click(
			'.wc-block-components-quantity-selector__button--plus'
		);
		await page.waitForNetworkIdle( { idleTime: 1000 } );
		await await expect( page ).toMatchElement(
			'#express-payment-method-mock_express_payment'
		);
		await shopper.block.goToCheckout();
		await shopper.block.mockExpressPaymentMethod();
		// We need to re-render the checkout for the express payments block to be updated,
		// so we just type a space in the email field.
		await expect( page ).toFill( '#email', ' ' );
		await expect( page ).toMatchElement(
			'#express-payment-method-mock_express_payment'
		);
	} );
} );
