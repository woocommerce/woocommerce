/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { customerFile } from '@woocommerce/e2e-utils';

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
	test.describe( 'Logged in shopper', () => {
		test.use( { storageState: customerFile } );

		test.beforeEach( async ( { requestUtils, frontendUtils } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-additional-checkout-fields'
			);

			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
		} );

		test( 'Shopper can fill in the checkout form with additional fields and can have different value for same field in shipping and billing address', async ( {
			checkoutPageObject,
			frontendUtils,
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
					order: {
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

			expect(
				await checkoutPageObject.verifyAdditionalFieldsDetails( [
					[ 'Government ID', '12345' ],
					[ 'Government ID', '54321' ],
					[ 'What is your favourite colour?', 'Blue' ],
					[
						'Enter a gift message to include in the package',
						'This is for you!',
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
			).toHaveValue( 'This is for you!' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Contact information',
					} )
					.getByLabel(
						'Is this a personal purchase or a business purchase?'
					)
			).toHaveValue( 'Business' );
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
					.getByLabel( 'How wide is your road?' )
			).toHaveValue( 'Wide' );
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
					.getByLabel( 'How wide is your road?' )
			).toHaveValue( 'Narrow' );
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
					order: {
						'How did you hear about us?': 'Other',
						'What is your favourite colour?': 'Blue',
					},
				}
			);

			// Fill select fields "manually" (Not part of "fillInCheckoutWithTestData"). This is a workaround for select
			// fields until we recreate the Combobox component. This is because the aria-label includes the value so getting
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
			await checkoutPageObject.waitForCustomerDataUpdate();

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
			await checkoutPageObject.waitForCustomerDataUpdate();

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
					order: {
						'What is your favourite colour?': 'Red',
					},
				}
			);
			await checkoutPageObject.page
				.getByRole( 'group', {
					name: 'Additional order information',
				} )
				.getByLabel( 'How did you hear about us?' )
				.click();
			await checkoutPageObject.page
				.getByRole( 'group', {
					name: 'Additional order information',
				} )
				.getByRole( 'option' )
				.first()
				.click();
			await checkoutPageObject.waitForCustomerDataUpdate();

			await checkoutPageObject.placeOrder();

			expect(
				await checkoutPageObject.verifyAdditionalFieldsDetails( [
					[ 'Government ID', '98765' ],
					[ 'Government ID', '43210' ],
					[ 'What is your favourite colour?', 'Red' ],
					[ 'Would you like a free gift with your order?', 'No' ],
					// One checkbox is checked, the other is unchecked.
					[ 'Can a truck fit down your road?', 'No' ],
					[ 'Can a truck fit down your road?', 'Yes' ],
					// Different values in different address types.
					[ 'How wide is your road?', 'Wide' ],
					[ 'How wide is your road?', 'Super wide' ],
					[
						'Enter a gift message to include in the package',
						'This is for you, from me!',
					],
					[ 'Do you want to subscribe to our newsletter?', 'No' ],
				] )
			).toBe( true );

			// This optional select field was unset, so it should not be visible on the confirmation. Can't check this
			// with the above function so we will check it "manually".
			await expect(
				checkoutPageObject.page.getByText(
					'How did you hear about us?'
				)
			).toBeHidden();

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
		} );

		test( 'Shopper can input unsanitized values that become sanitized after checkout', async ( {
			checkoutPageObject,
			frontendUtils,
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
							'Government ID': ' 1. 2 3 4 5 ',
							'Confirm government ID': '1      2345',
						},
						billing: {
							'Government ID': ' 5. 4 3 2 1 ',
							'Confirm government ID': '543 21',
						},
					},
					order: {
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

			expect(
				await checkoutPageObject.verifyAdditionalFieldsDetails( [
					[ 'Government ID', '12345' ],
					[ 'Government ID', '54321' ],
					[ 'What is your favourite colour?', 'Blue' ],
					[
						'Enter a gift message to include in the package',
						'This is for you!',
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
			).toHaveValue( 'This is for you!' );
			await expect(
				checkoutPageObject.page
					.getByRole( 'group', {
						name: 'Contact information',
					} )
					.getByLabel(
						'Is this a personal purchase or a business purchase?'
					)
			).toHaveValue( 'Business' );
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
					.getByLabel( 'How wide is your road?' )
			).toHaveValue( 'Wide' );
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
					.getByLabel( 'How wide is your road?' )
			).toHaveValue( 'Narrow' );
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
					order: { 'How did you hear about us?': 'Other' },
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
						'Please ensure your government ID matches the correct format.'
					)
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

			await checkoutPageObject.page.evaluate( () => {
				window.wp.data.dispatch( 'core/notices' ).removeAllNotices();
			} );

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
					order: { 'How did you hear about us?': 'Other' },
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

			expect(
				await checkoutPageObject.verifyAdditionalFieldsDetails( [
					[ 'Government ID', '12345' ],
					[ 'Government ID', '54321' ],
					[ 'How wide is your road?', 'Wide' ],
					[ 'How wide is your road?', 'Narrow' ],
				] )
			).toBe( true );
		} );

		test( 'Shopper can see and edit submitted fields in my account area. Values are also sanitized and validated in my account area.', async ( {
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
					order: { 'How did you hear about us?': 'Other' },
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

			expect(
				await checkoutPageObject.verifyAdditionalFieldsDetails( [
					[ 'Government ID', '12345' ],
					[ 'Government ID', '54321' ],
					[ 'How wide is your road?', 'Wide' ],
					[ 'How wide is your road?', 'Narrow' ],
					[
						'Enter a gift message to include in the package',
						'This is a nice gift',
					],
					[ 'Do you want to subscribe to our newsletter?', 'Yes' ],
					[ 'Can a truck fit down your road?', 'Yes' ],
					[ 'Can a truck fit down your road?', 'No' ],
				] )
			).toBe( true );

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
				checkoutPageObject.page.getByText(
					'How wide is your road?: Wide'
				)
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
			await confirmGovIdInput.fill( '44444' );
			await truckFittingCheckbox.check();
			await roadSizeSelect.selectOption( 'Super wide' );

			await checkoutPageObject.page.getByText( 'Save address' ).click();

			await expect(
				checkoutPageObject.page.getByText( 'Invalid Government ID.' )
			).toBeVisible();
			await expect(
				checkoutPageObject.page.getByText(
					'Please ensure your government ID matches the confirmation.'
				)
			).toBeVisible();

			await govIdInput.fill( '4444 4' );
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
			const shippingConfirmGovIdInput =
				checkoutPageObject.page.getByLabel( 'Confirm government ID *', {
					exact: true,
				} );
			await expect( shippingGovIdInput ).toHaveValue( '12345' );
			await expect( shippingConfirmGovIdInput ).toHaveValue( '12345' );

			// Check checkbox in edit mode match the expected value.
			const shippingTruckFittingCheckbox =
				checkoutPageObject.page.getByLabel(
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
				checkoutPageObject.page.getByText(
					'Confirm government ID: 44444'
				)
			).toBeVisible();
			await expect(
				checkoutPageObject.page.getByText( 'Government ID: 11111' )
			).toBeVisible();
			await expect(
				checkoutPageObject.page.getByText(
					'Confirm government ID: 11111'
				)
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
			await checkoutPageObject.page.waitForURL(
				'my-account/edit-account/'
			);

			// Check text inputs in edit mode match the expected values.
			const giftMessageInput = checkoutPageObject.page.getByLabel(
				'Enter a gift message to include in the package (optional)',
				{ exact: true }
			);
			await expect( giftMessageInput ).toHaveValue(
				'This is a nice gift'
			);
		} );
	} );
} );
