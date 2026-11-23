/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import {
	expect,
	test as base,
	adminFile,
	enableTestPlugin,
	guestFile,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from './checkout.page';

const test = base.extend< {
	checkoutPageObject: CheckoutPage;
	adminPage: Page;
} >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
	// The order is placed as a guest, because all workers share the admin
	// cart. The merchant views it in a separate admin context.
	adminPage: async ( { browser }, use ) => {
		const context = await browser.newContext( {
			storageState: adminFile,
		} );
		await enableTestPlugin( context, 'additional-checkout-fields' );
		await use( await context.newPage() );
		await context.close();
	},
} );

test.describe( 'Merchant → Additional Checkout Fields', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach(
		async ( { context, page, createHiddenProduct, frontendUtils } ) => {
			await enableTestPlugin( context, 'additional-checkout-fields' );

			const { id } = await createHiddenProduct( {
				name: 'Additional fields merchant product',
				regular_price: '20',
			} );
			await page.goto( `/?add-to-cart=${ id }` );
			await frontendUtils.goToCheckout();
		}
	);

	test( 'Merchant can see additional fields in the order admin page', async ( {
		checkoutPageObject,
		adminPage,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				contact: {
					'Alternative Email': 'test@test.com',
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
			.selectOption( 'wide' );
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.getByLabel( 'How wide is your road?' )
			.selectOption( 'narrow' );

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

		await checkoutPageObject.page
			.getByLabel( 'Test required checkbox' )
			.check();

		await checkoutPageObject.placeOrder();

		const orderId = checkoutPageObject.getOrderId();
		await adminPage.goto(
			`wp-admin/post.php?post=${ orderId }&action=edit`
		);

		await expect(
			adminPage.getByText( 'Government ID: 12345', { exact: true } )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Confirm government ID: 12345', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Government ID: 54321', { exact: true } )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Confirm government ID: 54321', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'What is your favourite colour?: Blue' )
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Enter a gift message to include in the package: This is for you!'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Do you want to subscribe to our newsletter?: Yes'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Would you like a free gift with your order?: Yes'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Can a truck fit down your road?: Yes' )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Can a truck fit down your road?: No' )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'How wide is your road?: Wide' )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'How wide is your road?: Narrow' )
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Is this a personal purchase or a business purchase?: Business'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText( 'How did you hear about us?: Other' )
		).toBeVisible();
	} );

	test( 'Merchant can edit custom fields from the order admin page', async ( {
		checkoutPageObject,
		adminPage,
	} ) => {
		await checkoutPageObject.editShippingDetails();
		await checkoutPageObject.unsyncBillingWithShipping();
		await checkoutPageObject.editBillingDetails();
		await checkoutPageObject.fillInCheckoutWithTestData(
			{},
			{
				contact: {
					'Alternative Email': 'test@test.com',
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
			.selectOption( 'wide' );
		await checkoutPageObject.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.getByLabel( 'How wide is your road?' )
			.selectOption( 'narrow' );

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

		await checkoutPageObject.page
			.getByLabel( 'Test required checkbox' )
			.check();

		await checkoutPageObject.placeOrder();

		const orderId = checkoutPageObject.getOrderId();
		await adminPage.goto(
			`wp-admin/post.php?post=${ orderId }&action=edit`
		);

		await adminPage
			.getByRole( 'heading', { name: 'Billing Edit' } )
			.getByRole( 'link' )
			.click();

		// Change all the billing details
		await adminPage
			.getByRole( 'textbox', {
				name: 'Government ID',
				exact: true,
			} )
			.fill( '99999' );
		await adminPage
			.getByRole( 'textbox', {
				name: 'Confirm government ID',
				exact: true,
			} )
			.fill( '99999' );
		await adminPage
			.getByRole( 'checkbox', {
				name: 'Can a truck fit down your road?',
			} )
			.check();

		// Use Locator here because the select2 box is duplicated in shipping.
		await adminPage
			.locator(
				'[id="\\_wc_billing\\/first-plugin-namespace\\/road-size"]'
			)
			.selectOption( 'wide' );

		// Handle changing the contact fields.
		await adminPage
			.getByLabel( 'Do you want to subscribe to our newsletter?' )
			.uncheck();
		await adminPage
			.getByLabel( 'Enter a gift message to include in the package' )
			.fill( 'Some other message' );
		await adminPage
			.getByLabel( 'Is this a personal purchase or a business purchase?' )
			.selectOption( 'personal' );

		const clickPromise = adminPage
			.getByRole( 'button', { name: 'Update' } )
			.first()
			.click();

		const navigationPromise = adminPage.waitForEvent( 'domcontentloaded' );

		// When update is clicked without waiting for DOMContentLoaded the page becomes
		// available before click handlers are attached to the shipping edit link.
		await Promise.all( [ clickPromise, navigationPromise ] );

		await adminPage
			.getByRole( 'heading', { name: 'Shipping Edit' } )
			.getByRole( 'link' )
			.click();

		// Change all the shipping details
		await adminPage
			.getByRole( 'textbox', {
				name: 'Government ID',
				exact: true,
			} )
			.fill( '88888' );
		await adminPage
			.getByRole( 'textbox', {
				name: 'Confirm government ID',
				exact: true,
			} )
			.fill( '88888' );
		await adminPage
			.getByRole( 'checkbox', {
				name: 'Can a truck fit down your road?',
			} )
			.uncheck();

		// Use Locator here because the select2 box is duplicated in billing.
		await adminPage
			.locator(
				'[id="\\_wc_shipping\\/first-plugin-namespace\\/road-size"]'
			)
			.selectOption( 'super-wide' );

		// Handle changing the additional information fields.
		await adminPage
			.getByLabel( 'Would you like a free gift with your order?' )
			.uncheck();
		await adminPage
			.getByLabel( 'What is your favourite colour?' )
			.fill( 'Green' );
		await adminPage
			.getByLabel( 'How did you hear about us?' )
			.selectOption( 'google' );

		await adminPage
			.getByRole( 'button', { name: 'Update' } )
			.first()
			.click();

		await adminPage.waitForLoadState( 'domcontentloaded' );

		await expect(
			adminPage.getByText( 'Government ID: 88888', { exact: true } )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Confirm government ID: 88888', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Government ID: 99999', { exact: true } )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Confirm government ID: 99999', {
				exact: true,
			} )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'What is your favourite colour?: Green' )
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Enter a gift message to include in the package: Some other message'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Do you want to subscribe to our newsletter?: No'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Would you like a free gift with your order?: No'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Can a truck fit down your road?: Yes' )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'Can a truck fit down your road?: No' )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'How wide is your road?: Super wide' )
		).toBeVisible();
		await expect(
			adminPage.getByText( 'How wide is your road?: Wide' )
		).toBeVisible();
		await expect(
			adminPage.getByText(
				'Is this a personal purchase or a business purchase?: Personal'
			)
		).toBeVisible();
		await expect(
			adminPage.getByText( 'How did you hear about us?: Google' )
		).toBeVisible();
	} );
} );
