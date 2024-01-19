/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { BlockData } from '@woocommerce/e2e-types';
import { customerFile, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from './constants';
import { CheckoutPage } from './checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Additional Checkout Fields', () => {
	test.use( { storageState: customerFile } );
	test.beforeAll( async ( { browser } ) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await page.goto( '/?enable_custom_checkout_fields=true' );
		await expect(
			page.getByText( 'Enabled custom checkout fields' )
		).toBeVisible();
		await page.close();
		await context.close();
	} );
	test.afterAll( async ( { browser } ) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		await page.goto( '/?disable_custom_checkout_fields=true' );
		await expect(
			page.getByText( 'Disabled custom checkout fields' )
		).toBeVisible();
		await page.close();
		await context.close();
	} );

	test.beforeEach( async ( { frontendUtils } ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
	} );

	test( 'Shopper can fill in the checkout form with additional fields', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: [ { label: 'Government ID', value: '12345' } ],
				additional: [
					{ label: 'How did you hear about us?', value: 'other' },
				],
			}
		);
		await checkoutPageObject.placeOrder();

		const shippingGovernmentIdText = checkoutPageObject.page
			.getByText( 'Government ID' )
			.first();
		const governmentId = await shippingGovernmentIdText.evaluate(
			( node ) => node.nextSibling?.textContent
		);
		await expect( shippingGovernmentIdText ).toBeVisible();
		expect( governmentId ).toEqual( '12345' );
	} );
} );
