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
				contact: {
					'Enter a gift message to include in the package':
						'This is for you!',
					'Is this a personal purchase or a business purchase?':
						'business',
				},
				address: {
					shipping: {
						'Government ID': '12345',
						'Confirm government ID': '12345',
					},
					billing: {
						'Government ID': '54321',
						'Confirm government ID': '54321',
					},
				},
				additional: {
					'How did you hear about us?': 'Other',
					'What is your favourite colour?': 'Blue',
				},
			}
		);

		// Fill select fields "manually" (Not part of "fillInCheckoutWithTestData"). This is a workaround for select
		// fields until we recreate th Combobox component. This is because the aria-label includes the value so getting
		// by label alone is not reliable unless we know the value.
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Shipping address',
			} )
			.getByLabel( 'How wide is your road?' )
			.fill( 'wide' );
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.getByLabel( 'How wide is your road?' )
			.fill( 'narrow' );

		await checkoutPageObject.page
			.getByLabel( 'Would you like a free gift with your order?' )
			.check();
		await checkoutPageObject.page
			.getByLabel( 'Do you want to subscribe to our newsletter?' )
			.check();
		await checkoutPageObject.page
			.getByLabel( 'Can a truck fit down your road?' )
			.first()
			.check();

		await checkoutPageObject.placeOrder();

		await expect(
			checkoutPageObject.page.getByText( 'Government ID12345' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID54321' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'What is your favourite colour?Blue'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Enter a gift message to include in the packageThis is for you!'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Do you want to subscribe to our newsletter?Yes'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Would you like a free gift with your order?Yes'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?Yes'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?No'
			)
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
				contact: {
					'Enter a gift message to include in the package':
						'This is for you!',
					'Is this a personal purchase or a business purchase?':
						'business',
				},
				address: {
					shipping: {
						'Government ID': '',
						'Confirm government ID': '',
					},
					billing: {
						'Government ID': '54321',
						'Confirm government ID': '54321',
					},
				},
				additional: {
					'How did you hear about us?': 'Other',
					'What is your favourite colour?': 'Blue',
				},
			}
		);

		// Use the data store to specifically unset the field value - this is because it might be saved in the user-state.
		await checkoutPageObject.page.evaluate( () => {
			window.wp.data.dispatch( 'wc/store/cart' ).setShippingAddress( {
				'first-plugin-namespace/road-size': '',
			} );
		} );

		await checkoutPageObject.placeOrder( false );

		await expect(
			checkoutPageObject.page.getByText(
				'Please enter a valid government id'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Please select a valid how wide is your road?'
			)
		).toBeVisible();
	} );

	test( 'Shopper can change the values of fields multiple times and place the order', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();

		const optInCheckbox = checkoutPageObject.page.getByLabel(
			'Do you want to subscribe to our newsletter? (optional)'
		);
		await optInCheckbox.check();
		await optInCheckbox.uncheck();

		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: {
					shipping: {
						'Government ID': '12345',
						'Confirm government ID': '12345',
					},
					billing: {
						'Government ID': '54321',
						'Confirm government ID': '54321',
					},
				},
				additional: { 'How did you hear about us?': 'Other' },
			}
		);

		// First change after initial input.
		await optInCheckbox.check();
		await optInCheckbox.uncheck();
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: {
					shipping: {
						'Government ID': '54321',
						'Confirm government ID': '54321',
					},
					billing: {
						'Government ID': '12345',
						'Confirm government ID': '12345',
					},
				},
				additional: { 'How did you hear about us?': 'Facebook' },
			}
		);

		// Second change after initial input.
		await optInCheckbox.check();
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: {
					shipping: {
						'Government ID': '98765',
						'Confirm government ID': '98765',
					},
					billing: {
						'Government ID': '43210',
						'Confirm government ID': '43210',
					},
				},
				additional: { 'How did you hear about us?': 'Google' },
			}
		);

		await checkoutPageObject.placeOrder();

		// Check the order was placed successfully.
		await expect(
			checkoutPageObject.page.getByText( 'Government ID98765' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID43210' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'How did you hear about us?Google'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Do you want to subscribe to our newsletter?Yes'
			)
		).toBeVisible();
	} );

	test( 'Shopper can see server-side validation errors', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();

		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: {
					shipping: {
						'Government ID': 'abcde',
						'Confirm government ID': '12345',
					},
					billing: {
						'Government ID': 'fghij',
						'Confirm government ID': '12345',
					},
				},
				additional: { 'How did you hear about us?': 'Other' },
			}
		);

		await checkoutPageObject.placeOrder( false );
		await checkoutPageObject.page.waitForResponse( ( response ) => {
			return response.url().indexOf( 'wc/store/v1/checkout' ) !== -1;
		} );

		await expect(
			checkoutPageObject.page
				.locator( '#billing-fields' )
				.getByText( 'Invalid government ID.' )
		).toBeVisible();

		await expect(
			checkoutPageObject.page
				.locator( '#billing-fields' )
				.getByText(
					'Please ensure your government ID matches the confirmation.'
				)
		).toBeVisible();
		await expect(
			checkoutPageObject.page
				.locator( '#shipping-fields' )
				.getByText( 'Invalid government ID.' )
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				address: {
					shipping: {
						'Government ID': '12345',
						'Confirm government ID': '12345',
					},
					billing: {
						'Government ID': '54321',
						'Confirm government ID': '54321',
					},
				},
				additional: { 'How did you hear about us?': 'Other' },
			}
		);

		await checkoutPageObject.placeOrder();

		// Check the order was placed successfully.
		await expect(
			checkoutPageObject.page.getByText( 'Government ID12345' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID54321' )
		).toBeVisible();
	} );
} );
