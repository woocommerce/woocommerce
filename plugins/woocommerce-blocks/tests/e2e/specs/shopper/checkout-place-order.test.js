/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import {
	SIMPLE_VIRTUAL_PRODUCT_NAME,
	BILLING_DETAILS,
} from '../../../utils/constants';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `Skipping checkout tests`, () => {} );

describe( 'Shopper → Checkout → Can place an order', () => {
	it( 'allows customer to place an order as a guest', async () => {
		await shopper.logout();
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.fillBillingDetails( BILLING_DETAILS );
		await shopper.block.placeOrder();
		await expect( page ).toMatch( 'Your order has been received.' );
	} );

	it( 'allows customer to place an order as a logged in user', async () => {
		await shopper.login();
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.fillBillingDetails( BILLING_DETAILS );
		await shopper.block.placeOrder();
		await expect( page ).toMatch( 'Your order has been received.' );
		await shopper.logout();
	} );
} );
