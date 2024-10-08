/**
 * External dependencies
 */
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

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
	test.describe( 'Guest shopper', () => {
		test.use( { storageState: guestFile } );

		test.beforeEach( async ( { frontendUtils, requestUtils } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-additional-checkout-fields'
			);

			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
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
							'For my non-ascii named friend: niño',
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
					order: {
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
		} );

		test( 'Shopper can fill in the checkout form with additional fields and can have different value for same field in shipping and billing address', async ( {
			checkoutPageObject,
			frontendUtils,
		} ) => {
			await checkoutPageObject.unsyncBillingWithShipping();
			await checkoutPageObject.fillInCheckoutWithTestData(
				{},
				{
					contact: {
						'Enter a gift message to include in the package':
							'For my non-ascii named friend: niño',
						'Is this a personal purchase or a business purchase?':
							'business',
					},
					address: {
						shipping: {
							'Government ID': '12345',
							'Confirm government ID': '12345',
							'How wide is your road? (optional)': 'wide',
						},
						billing: {
							'Government ID': '54321',
							'Confirm government ID': '54321',
							'How wide is your road? (optional)': 'narrow',
						},
					},
					order: {
						'How did you hear about us? (optional)': 'other',
						'What is your favourite colour?': 'Blue',
					},
				}
			);

			await checkoutPageObject.page
				.getByLabel( 'Would you like a free gift with your order?' )
				.check();
			await checkoutPageObject.page
				.getByLabel( 'Do you want to subscribe to our newsletter?' )
				.check();
			await checkoutPageObject.page
				.getByRole( 'group', {
					name: 'Shipping address',
				} )
				.getByLabel( 'Can a truck fit down your road?' )
				.check();

			await checkoutPageObject.page
				.getByRole( 'group', {
					name: 'Billing address',
				} )
				.getByLabel( 'Can a truck fit down your road?' )
				.check();

			await checkoutPageObject.waitForCustomerDataUpdate();

			await checkoutPageObject.page
				.getByRole( 'group', {
					name: 'Billing address',
				} )
				.getByLabel( 'Can a truck fit down your road?' )
				.uncheck();

			await checkoutPageObject.waitForCustomerDataUpdate();

			await checkoutPageObject.waitForCheckoutToFinishUpdating();

			await checkoutPageObject.placeOrder();

			expect(
				await checkoutPageObject.verifyAdditionalFieldsDetails( [
					[ 'Government ID', '12345' ],
					[ 'Government ID', '54321' ],
					[ 'What is your favourite colour?', 'Blue' ],
					[
						'Enter a gift message to include in the package',
						'For my non-ascii named friend: niño',
					],
					[ 'Do you want to subscribe to our newsletter?', 'Yes' ],
					[ 'Would you like a free gift with your order?', 'Yes' ],
					[ 'Can a truck fit down your road?', 'Yes' ],
					[ 'Can a truck fit down your road?', 'No' ],
					[ 'How wide is your road?', 'Wide' ],
					[ 'How wide is your road?', 'Narrow' ],
					[
						'Is this a personal purchase or a business purchase?',
						'business',
					],
				] )
			).toBe( true );

			await frontendUtils.emptyCart();
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils.goToCheckout();

			await checkoutPageObject.editShippingDetails();
			await checkoutPageObject.editBillingDetails();

			// Now check all the fields previously filled are still filled on a fresh checkout.
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Contact information',
					} )
					.getByLabel(
						'Enter a gift message to include in the package'
					)
			).toHaveValue( 'For my non-ascii named friend: niño' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Contact information',
					} )
					.getByLabel(
						'Is this a personal purchase or a business purchase?'
					)
			).toHaveValue( 'business' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Contact information',
					} )
					.getByLabel( 'Do you want to subscribe to our newsletter?' )
			).toBeChecked();
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Shipping address',
					} )
					.getByLabel( 'Government ID', { exact: true } )
			).toHaveValue( '12345' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Shipping address',
					} )
					.getByLabel( 'Confirm Government ID' )
			).toHaveValue( '12345' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Shipping address',
					} )
					.getByLabel( 'Can a truck fit down your road?' )
			).toBeChecked();
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Shipping address',
					} )
					.getByLabel( 'How wide is your road? (optional)' )
			).toHaveValue( 'wide' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Billing address',
					} )
					.getByLabel( 'Government ID', { exact: true } )
			).toHaveValue( '54321' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Billing address',
					} )
					.getByLabel( 'Confirm Government ID' )
			).toHaveValue( '54321' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Billing address',
					} )
					.getByLabel( 'Can a truck fit down your road?' )
			).not.toBeChecked();
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Billing address',
					} )
					.getByLabel( 'How wide is your road? (optional)' )
			).toHaveValue( 'narrow' );
		} );
	} );
} );
