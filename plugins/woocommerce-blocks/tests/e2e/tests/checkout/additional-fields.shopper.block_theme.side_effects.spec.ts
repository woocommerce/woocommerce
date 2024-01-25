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

		await checkoutPageObject.page.evaluate(
			'document.activeElement.blur()'
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
			.uncheck();

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
		await expect(
			checkoutPageObject.page.getByText( 'How wide is your road?Wide' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'How wide is your road?Narrow' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Is this a personal purchase or a business purchase?business'
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
		await checkoutPageObject.page.waitForResponse( ( response ) => {
			return response.url().indexOf( 'wc/store/v1/batch' ) !== -1;
		} );

		// Change the shipping and billing select fields again.
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.getByLabel( 'How wide is your road?' )
			.fill( 'wide' );
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Shipping address',
			} )
			.getByLabel( 'How wide is your road?' )
			.fill( 'super-wide' );

		await checkoutPageObject.page.waitForResponse( ( response ) => {
			return response.url().indexOf( 'wc/store/v1/batch' ) !== -1;
		} );

		await checkoutPageObject.page
			.getByLabel( 'Would you like a free gift with your order?' )
			.check();
		await checkoutPageObject.page
			.getByLabel( 'Do you want to subscribe to our newsletter?' )
			.check();

		// Check both "Can a truck fit down your road?" checkboxes (one in shipping, one in billing).
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Shipping address',
			} )
			.getByLabel( 'Can a truck fit down your road?' )
			.check();
		// Check this one here, but don't uncheck it later.
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.getByLabel( 'Can a truck fit down your road?' )
			.check();

		await checkoutPageObject.page
			.getByLabel( 'Would you like a free gift with your order?' )
			.uncheck();
		await checkoutPageObject.page
			.getByLabel( 'Do you want to subscribe to our newsletter?' )
			.uncheck();
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Shipping address',
			} )
			.getByLabel( 'Can a truck fit down your road?' )
			.uncheck();

		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				contact: {
					'Enter a gift message to include in the package':
						'This is for you, from me!',
					'Is this a personal purchase or a business purchase?':
						'personal',
				},
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
				additional: {
					'How did you hear about us?': 'Facebook',
					'What is your favourite colour?': 'Red',
				},
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
				'How did you hear about us?Facebook'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Do you want to subscribe to our newsletter?No'
			)
		).toBeVisible();

		await expect(
			checkoutPageObject.page.getByText(
				'What is your favourite colour?Red'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Enter a gift message to include in the packageThis is for you, from me!'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Would you like a free gift with your order?No'
			)
		).toBeVisible();

		// Checking that one of the boxes is checked, and one is unchecked
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?No'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?Yes'
			)
		).toBeVisible();

		await expect(
			checkoutPageObject.page.getByText( 'How wide is your road?Wide' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'How wide is your road?Super wide'
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
				contact: {
					'Is this a personal purchase or a business purchase?':
						'business',
				},
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
				contact: {
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
				additional: { 'How did you hear about us?': 'Other' },
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
		await checkoutPageObject.page.evaluate(
			'document.activeElement.blur()'
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

	test( 'Shopper can see and edit submitted fields in my account area', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();

		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				contact: {
					'Enter a gift message to include in the package (optional)':
						'This is a nice gift',
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
				additional: { 'How did you hear about us?': 'Other' },
			}
		);

		// Check checkboxes manually since checking them as part of fillInCheckoutWithTestData is not supported.
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Shipping address',
			} )
			.getByLabel( 'Can a truck fit down your road?' )
			.check();
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Contact information',
			} )
			.getByLabel(
				'Do you want to subscribe to our newsletter? (optional)'
			)
			.check();

		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.getByLabel( 'Can a truck fit down your road?' )
			.uncheck();
		await checkoutPageObject.page.waitForResponse( ( response ) => {
			return response.url().indexOf( 'wc/store/v1/batch' ) !== -1;
		} );

		// Fill select fields manually. (Not part of "fillInCheckoutWithTestData"). This is a workaround for select
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

		// Blur after editing the select fields since they need to be blurred to save.
		await checkoutPageObject.page.evaluate(
			'document.activeElement.blur()'
		);
		await checkoutPageObject.placeOrder();

		// Check the order was placed successfully.
		await expect(
			checkoutPageObject.page.getByText( 'Government ID12345' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID54321' )
		).toBeVisible();

		await checkoutPageObject.page.goto( '/my-account' );
		await checkoutPageObject.page
			.getByText( 'Addresses', { exact: true } )
			.click();

		// Check the fields are visible in the addresses.
		await expect(
			checkoutPageObject.page.getByText( 'Government ID: 12345' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID: 54321' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?: Yes'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'How wide is your road?: Wide' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'How wide is your road?: Narrow'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?: No'
			)
		).toBeVisible();

		// Go to the edit page, check the info matches what was submitted during checkout, then edit it and save it.
		const billingTitle =
			checkoutPageObject.page.getByText( 'Billing address' );
		const billingEdit = checkoutPageObject.page
			.locator( '.woocommerce-Address-title' )
			.filter( { has: billingTitle } )
			.getByText( 'Edit' );
		await billingEdit.click();
		await checkoutPageObject.page.waitForURL(
			'my-account/edit-address/billing/'
		);

		// Check text inputs in edit mode match the expected values.
		const govIdInput = checkoutPageObject.page.getByLabel(
			'Government ID *',
			{ exact: true }
		);
		const confirmGovIdInput = checkoutPageObject.page.getByLabel(
			'Confirm government ID *',
			{ exact: true }
		);
		await expect( govIdInput ).toHaveValue( '54321' );
		await expect( confirmGovIdInput ).toHaveValue( '54321' );

		// Check select in edit mode match the expected value.
		const roadSizeSelect = checkoutPageObject.page.getByLabel(
			'How wide is your road?'
		);
		await expect( roadSizeSelect ).toHaveValue( 'narrow' );

		// Check checkbox in edit mode match the expected value.
		const truckFittingCheckbox = checkoutPageObject.page.getByLabel(
			'Can a truck fit down your road? (optional)'
		);
		await expect( truckFittingCheckbox ).not.toBeChecked();

		// Change the values and save.
		await govIdInput.fill( '444444' );
		await confirmGovIdInput.fill( '444444' );
		await truckFittingCheckbox.check();
		await roadSizeSelect.selectOption( 'Super wide' );

		await checkoutPageObject.page.getByText( 'Save address' ).click();

		const shippingTitle =
			checkoutPageObject.page.getByText( 'Shipping address' );
		const shippingEdit = checkoutPageObject.page
			.locator( '.woocommerce-Address-title' )
			.filter( { has: shippingTitle } )
			.getByText( 'Edit' );
		await shippingEdit.click();

		await checkoutPageObject.page.waitForURL(
			'my-account/edit-address/shipping/'
		);

		// Check text inputs in edit mode match the expected values.
		const shippingGovIdInput = checkoutPageObject.page.getByLabel(
			'Government ID *',
			{ exact: true }
		);
		const shippingConfirmGovIdInput = checkoutPageObject.page.getByLabel(
			'Confirm government ID *',
			{ exact: true }
		);
		await expect( shippingGovIdInput ).toHaveValue( '12345' );
		await expect( shippingConfirmGovIdInput ).toHaveValue( '12345' );

		// Check checkbox in edit mode match the expected value.
		const shippingTruckFittingCheckbox = checkoutPageObject.page.getByLabel(
			'Can a truck fit down your road? (optional)'
		);
		await expect( shippingTruckFittingCheckbox ).toBeChecked();

		// Check select in edit mode match the expected value.
		const shippingRoadSizeSelect = checkoutPageObject.page.getByLabel(
			'How wide is your road?'
		);
		await expect( shippingRoadSizeSelect ).toHaveValue( 'wide' );

		await govIdInput.fill( '11111' );
		await confirmGovIdInput.fill( '11111' );
		await shippingTruckFittingCheckbox.uncheck();
		await shippingRoadSizeSelect.selectOption( 'Narrow' );
		await checkoutPageObject.page.getByText( 'Save address' ).click();

		// Check the updated values are visible in the addresses.
		await expect(
			checkoutPageObject.page.getByText( 'Government ID: 44444' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Confirm government ID: 44444' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Government ID: 11111' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText( 'Confirm government ID: 11111' )
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'How wide is your road?: Super wide'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'How wide is your road?: Narrow'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?: Yes'
			)
		).toBeVisible();
		await expect(
			checkoutPageObject.page.getByText(
				'Can a truck fit down your road?: No'
			)
		).toBeVisible();

		// Go to the "Account information" section and check the values from "contact" are visible there.
		await checkoutPageObject.page
			.getByText( 'Account details', { exact: true } )
			.click();
		await checkoutPageObject.page.waitForURL( 'my-account/edit-account/' );

		// Check text inputs in edit mode match the expected values.
		const giftMessageInput = checkoutPageObject.page.getByLabel(
			'Enter a gift message to include in the package (optional)',
			{ exact: true }
		);
		await expect( giftMessageInput ).toHaveValue( 'This is a nice gift' );
	} );
} );
