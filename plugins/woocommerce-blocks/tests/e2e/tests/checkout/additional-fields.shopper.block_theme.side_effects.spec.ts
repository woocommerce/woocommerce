/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { customerFile } from '@woocommerce/e2e-utils';
import {
	installPluginFromPHPFile,
	uninstallPluginFromPHPFile,
} from '@woocommerce/e2e-mocks/custom-plugins';

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
	test.beforeAll( async () => {
		await installPluginFromPHPFile(
			`${ __dirname }/additional-checkout-fields-plugin.php`
		);
	} );
	test.afterAll( async () => {
		await uninstallPluginFromPHPFile(
			`${ __dirname }/additional-checkout-fields-plugin.php`
		);
	} );

	test.beforeEach( async ( { frontendUtils } ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
	} );

	test( 'Shopper can fill in the checkout form with additional fields and can have different value for same field in shipping and billing address', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: {
					shipping: { 'Government ID': '12345' },
					billing: { 'Government ID': '54321' },
				},
				additional: { 'How did you hear about us?': 'Other' },
			}
		);
		await checkoutPageObject.placeOrder();

		await expect(
			checkoutPageObject.page.getByText( 'Government ID12345' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID54321' )
		).toBeVisible();
	} );

	test( 'Shopper can see an error message when a required field is not filled in the checkout form', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				// Purposely skipping the "Government ID" field here.
				address: {
					shipping: { 'Government ID': '' },
					billing: { 'Government ID': '12345' },
				},
				additional: { 'How did you hear about us?': 'Other' },
			}
		);
		await checkoutPageObject.placeOrder( false );

		await expect(
			checkoutPageObject.page.getByText(
				'Please enter a valid government id'
			)
		).toBeVisible();
	} );
} );
