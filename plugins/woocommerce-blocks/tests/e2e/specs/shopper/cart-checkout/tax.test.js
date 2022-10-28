/**
 * Internal dependencies
 */
import {
	shopper,
	getExpectedTaxes,
	getTaxesFromCurrentPage,
	getTaxesFromOrderSummaryPage,
	showTaxes,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from '../../../../utils';
import { Taxes, Products } from '../../../fixtures/fixture-data';

const taxRates = Taxes();
const productWooSingle1 = Products().find(
	( prod ) => prod.name === 'Woo Single #1'
);

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// Skips all the tests if it's a WooCommerce Core process environment.
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `Skipping Cart & Checkout tests`, () => {} );
}

describe( 'Shopper → Cart & Checkout → Taxes', () => {
	beforeEach( async () => {
		await shopper.block.emptyCart();
	} );

	describe( '"Enable tax rate calculations" is unchecked in WC settings -> general', () => {
		it( 'User cannot view the tax on Cart, Checkout & Order Summary', async () => {
			await showTaxes( false );
			await shopper.block.goToShop();
			await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await shopper.block.goToCart();

			const cartTaxes = await getTaxesFromCurrentPage();
			expect( cartTaxes ).toEqual( [] );

			await shopper.block.goToCheckout();
			const checkoutTaxes = await getTaxesFromCurrentPage();
			expect( checkoutTaxes ).toEqual( [] );

			await shopper.block.fillInCheckoutWithTestData();
			await shopper.block.placeOrder();
			await page.waitForSelector( 'h1.entry-title' );
			const orderSummaryTaxes = await getTaxesFromOrderSummaryPage(
				taxRates.filter( ( taxRate ) => taxRate.country === 'US' )
			);
			expect( orderSummaryTaxes ).toEqual( [] );
		} );
	} );

	describe( '"Enable tax rate calculations" is checked in WC settings -> general', () => {
		it( 'User can view the tax on Cart, Checkout & Order Summary', async () => {
			await showTaxes( true );
			await shopper.block.goToShop();
			await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await shopper.block.goToCart();

			const expectedTaxes = getExpectedTaxes( taxRates, 'US', [
				productWooSingle1,
			] );
			const cartTaxes = await getTaxesFromCurrentPage();
			expect( cartTaxes.sort() ).toEqual( expectedTaxes.sort() );

			await shopper.block.goToCheckout();
			const checkoutTaxes = await getTaxesFromCurrentPage();
			expect( checkoutTaxes.sort() ).toEqual( expectedTaxes.sort() );

			await shopper.block.fillInCheckoutWithTestData();
			await shopper.block.placeOrder();
			await page.waitForSelector( 'h1.entry-title' );
			const orderSummaryTaxes = await getTaxesFromOrderSummaryPage(
				taxRates.filter( ( taxRate ) => taxRate.country === 'US' )
			);
			expect( orderSummaryTaxes.sort() ).toEqual( expectedTaxes.sort() );
		} );
	} );
} );
