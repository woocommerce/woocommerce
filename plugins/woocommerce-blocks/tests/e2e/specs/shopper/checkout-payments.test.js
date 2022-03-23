/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';

import { SIMPLE_PRODUCT_NAME } from '../../../utils/constants';

const PAYMENT_COD = 'Cash on delivery';
const PAYMENT_BACS = 'Direct bank transfer';
const PAYMENT_CHEQUE = 'Check payments';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `Skipping checkout tests`, () => {} );

describe( 'Shopper → Checkout → Can choose payment option', () => {
	beforeEach( async () => {
		await shopper.block.emptyCart();
	} );

	afterAll( async () => {
		await shopper.block.emptyCart();
	} );

	it( 'allows customer to pay using Direct bank transfer', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.selectPayment( PAYMENT_BACS );
		await shopper.block.fillInCheckoutWithTestData();
		await shopper.block.placeOrder();
		await expect( page ).toMatch( 'Order received' );
		await expect( page ).toMatch( PAYMENT_BACS );
	} );

	it( 'allows customer to pay using Cash on delivery', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.selectPayment( PAYMENT_COD );
		await shopper.block.fillInCheckoutWithTestData();
		await shopper.block.placeOrder();
		await expect( page ).toMatch( 'Order received' );
		await expect( page ).toMatch( PAYMENT_COD );
	} );

	it( 'allows customer to pay using Check payments', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await shopper.block.selectPayment( PAYMENT_CHEQUE );
		await shopper.block.fillInCheckoutWithTestData();
		await shopper.block.placeOrder();
		await expect( page ).toMatch( 'Order received' );
		await expect( page ).toMatch( PAYMENT_CHEQUE );
	} );
} );
