/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
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

test.describe( 'Merchant → Additional Checkout Fields', () => {
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

	test( 'Merchant can see additional fields in the order admin page', async ( {
		checkoutPageObject,
		admin,
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

		const orderId = checkoutPageObject.getOrderId();
		await admin.page.goto(
			`wp-admin/post.php?post=${ orderId }&action=edit`
		);

		await expect(
			admin.page.getByText( 'Government ID: 12345', { exact: true } )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Confirm government ID: 12345', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Government ID: 54321', { exact: true } )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Confirm government ID: 54321', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'What is your favourite colour?: Blue' )
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Enter a gift message to include in the package: This is for you!'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Do you want to subscribe to our newsletter?: Yes'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Would you like a free gift with your order?: Yes'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Can a truck fit down your road?: Yes' )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Can a truck fit down your road?: No' )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'How wide is your road?: Wide' )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'How wide is your road?: Narrow' )
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Is this a personal purchase or a business purchase?: Business'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText( 'How did you hear about us?: Other' )
		).toBeVisible();
	} );

	test( 'Merchant can edit custom fields from the order admin page', async ( {
		checkoutPageObject,
		admin,
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

		const orderId = checkoutPageObject.getOrderId();
		await admin.page.goto(
			`wp-admin/post.php?post=${ orderId }&action=edit`
		);

		await admin.page
			.getByRole( 'heading', { name: 'Billing Edit' } )
			.getByRole( 'link' )
			.click();

		// Change all the billing details
		await admin.page
			.getByRole( 'textbox', {
				name: 'Government ID',
				exact: true,
			} )
			.fill( '99999' );
		await admin.page
			.getByRole( 'textbox', {
				name: 'Confirm government ID',
				exact: true,
			} )
			.fill( '99999' );
		await admin.page
			.getByRole( 'checkbox', {
				name: 'Can a truck fit down your road?',
			} )
			.check();

		// Use Locator here because the select2 box is duplicated in shipping.
		await admin.page
			.locator( '[id="\\/billing\\/first-plugin-namespace\\/road-size"]' )
			.selectOption( 'wide' );

		// Handle changing the contact fields.
		await admin.page
			.getByLabel( 'Do you want to subscribe to our newsletter?' )
			.uncheck();
		await admin.page
			.getByLabel( 'Enter a gift message to include in the package' )
			.fill( 'Some other message' );
		await admin.page
			.getByLabel( 'Is this a personal purchase or a business purchase?' )
			.selectOption( 'personal' );

		const clickPromise = admin.page
			.getByRole( 'button', { name: 'Update' } )
			.first()
			.click();

		const navigationPromise = admin.page.waitForEvent( 'domcontentloaded' );

		// When update is clicked without waiting for DOMContentLoaded the page becomes
		// available before click handlers are attached to the shipping edit link.
		await Promise.all( [ clickPromise, navigationPromise ] );

		await admin.page
			.getByRole( 'heading', { name: 'Shipping Edit' } )
			.getByRole( 'link' )
			.click();

		// Change all the shipping details
		await admin.page
			.getByRole( 'textbox', {
				name: 'Government ID',
				exact: true,
			} )
			.fill( '88888' );
		await admin.page
			.getByRole( 'textbox', {
				name: 'Confirm government ID',
				exact: true,
			} )
			.fill( '88888' );
		await admin.page
			.getByRole( 'checkbox', {
				name: 'Can a truck fit down your road?',
			} )
			.uncheck();

		// Use Locator here because the select2 box is duplicated in billing.
		await admin.page
			.locator(
				'[id="\\/shipping\\/first-plugin-namespace\\/road-size"]'
			)
			.selectOption( 'super-wide' );

		// Handle changing the additional information fields.
		await admin.page
			.getByLabel( 'Would you like a free gift with your order?' )
			.uncheck();
		await admin.page
			.getByLabel( 'What is your favourite colour?' )
			.fill( 'Green' );
		await admin.page
			.getByLabel( 'How did you hear about us?' )
			.selectOption( 'google' );

		await admin.page
			.getByRole( 'button', { name: 'Update' } )
			.first()
			.click();

		await admin.page.waitForLoadState( 'domcontentloaded' );

		await expect(
			admin.page.getByText( 'Government ID: 88888', { exact: true } )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Confirm government ID: 88888', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Government ID: 99999', { exact: true } )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Confirm government ID: 99999', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'What is your favourite colour?: Green' )
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Enter a gift message to include in the package: Some other message'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Do you want to subscribe to our newsletter?: No'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Would you like a free gift with your order?: No'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Can a truck fit down your road?: Yes' )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'Can a truck fit down your road?: No' )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'How wide is your road?: Super wide' )
		).toBeVisible();
		await expect(
			admin.page.getByText( 'How wide is your road?: Wide' )
		).toBeVisible();
		await expect(
			admin.page.getByText(
				'Is this a personal purchase or a business purchase?: Personal'
			)
		).toBeVisible();
		await expect(
			admin.page.getByText( 'How did you hear about us?: Google' )
		).toBeVisible();
	} );
} );
