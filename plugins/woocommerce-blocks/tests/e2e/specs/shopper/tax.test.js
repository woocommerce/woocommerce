/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { Taxes, Products } from '../../fixtures/fixture-data';
import {
	getExpectedTaxes,
	getTaxesFromCurrentPage,
	getTaxesFromOrderSummaryPage,
	showTaxes,
} from '../../../utils/taxes';

const taxRates = Taxes();
const productWooSingle1 = Products().find(
	( prod ) => prod.name === 'Woo Single #1'
);

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( 'Shopper -> Tax', () => {
	beforeEach( async () => {
		await shopper.block.emptyCart();
	} );

	describe( '"Enable tax rate calculations" is unchecked in WC settings -> general', () => {
		it( 'Tax is not displayed', async () => {
			await showTaxes( false );
			await shopper.goToShop();
			await shopper.block.searchForProduct( productWooSingle1.name );
			await shopper.addToCart();
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
		it( 'Tax is displayed correctly on Cart & Checkout ', async () => {
			await showTaxes( true );
			await shopper.goToShop();
			await shopper.block.searchForProduct( productWooSingle1.name );
			await shopper.addToCart();
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
