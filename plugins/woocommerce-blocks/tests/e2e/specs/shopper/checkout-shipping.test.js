/**
 * External dependencies
 */
import { merchant } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';

const productName = '128GB USB Stick';
const freeShippingName = 'Free Shipping';
const freeShippingPrice = '$0.00';
const normalShippingName = 'Normal Shipping';
const normalShippingPrice = '$20.00';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( 'Skipping Checkout tests', () => {} );

describe( `Shopper → Checkout → Can choose shipping option`, () => {
	beforeAll( async () => {
		await merchant.login();
	} );

	afterAll( async () => {
		await merchant.logout();
	} );

	it( 'allows customer to choose free shipping', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( productName );
		await shopper.block.goToCheckout();
		await shopper.block.selectAndVerifyShippingOption(
			freeShippingName,
			freeShippingPrice
		);
		await shopper.block.placeOrder();
		await page.waitForTimeout( 2000 );
		await expect( page ).toMatch( 'Order received' );
		await expect( page ).toMatch( freeShippingName );
	} );

	it( 'allows customer to choose flat rate shipping', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( productName );
		await shopper.block.goToCheckout();
		await shopper.block.selectAndVerifyShippingOption(
			normalShippingName,
			normalShippingPrice
		);
		await shopper.block.placeOrder();
		await page.waitForTimeout( 2000 );
		await expect( page ).toMatch( 'Order received' );
		await expect( page ).toMatch( normalShippingName );
	} );
} );
