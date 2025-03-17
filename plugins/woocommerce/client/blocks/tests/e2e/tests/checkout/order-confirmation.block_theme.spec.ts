/**
 * External dependencies
 */
import { test as base, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from './checkout.page';
import {
	FREE_SHIPPING_NAME,
	FREE_SHIPPING_PRICE,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
	TEST_ADDRESS,
} from './constants';

const test = base.extend< { pageObject: CheckoutPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper (logged-in) → Order Confirmation', () => {
	test.beforeEach( async ( { admin, editor, localPickupUtils } ) => {
		await localPickupUtils.disableLocalPickup();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//order-confirmation',
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.transformIntoBlocks();
	} );

	test( 'Place order', async ( {
		frontendUtils,
		pageObject,
		page,
		requestUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		expect(
			await pageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await pageObject.fillInCheckoutWithTestData( TEST_ADDRESS );
		await pageObject.placeOrder();

		// Confirm Order Confirmation Block sections are visible when logged in
		// order data are visible and correct
		await pageObject.verifyOrderConfirmationDetails( page );

		// Store order received URL to use later
		const orderReceivedURL = page.url();

		// Confirm downloads section is visible when logged in
		// Open order we created
		const orderId = pageObject.getOrderId();
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );
		// Update order status to 'Processing'
		await page.locator( '#order_status' ).selectOption( 'wc-processing' );
		await page.locator( 'button.save_order' ).click();
		// Go back to order received page
		await page.goto( orderReceivedURL );
		// Confirm downloads section is visible
		const DownloadSection = page.locator(
			'[data-block-name="woocommerce/order-confirmation-downloads"]'
		);
		await expect( DownloadSection ).toBeVisible();

		// Access page without order key (test visibility of default message, no order details)
		const urlObj = new URL( orderReceivedURL );
		urlObj.searchParams.delete( 'key' );
		await page.goto( urlObj.toString() );
		// Confirm default message is visible
		await expect(
			page.getByText(
				'Great news! Your order has been received, and a confirmation will be sent to your email address. Have an account with us?'
			)
		).toBeVisible();
		// Confirm order details are not visible
		await pageObject.verifyOrderConfirmationDetails( page, false );

		// Access page without order ID or key (test visibility of default message)
		await page.goto( '/checkout/order-received' );
		// Confirm default message is visible
		await expect(
			page.getByText(
				"If you've just placed an order, give your email a quick check for the confirmation. Have an account with us?"
			)
		).toBeVisible();
		// Confirm order details are not visible
		await pageObject.verifyOrderConfirmationDetails( page, false );

		await test.step( 'Logout the user and revisit the order received page to verify that details are displayed when woocommerce_order_received_verify_known_shoppers is disabled', async () => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-order-confirmation-filters'
			);
			await page.goto( '/my-account' );
			await page
				.locator(
					'li.woocommerce-MyAccount-navigation-link--customer-logout a'
				)
				.click();
			await expect(
				page.getByRole( 'button', { name: 'Log in' } )
			).toBeVisible();
			await page.goto( orderReceivedURL );
			await pageObject.verifyOrderConfirmationDetails( page );
		} );
	} );
} );

test.describe( 'Shopper (guest) → Order Confirmation', () => {
	test.use( { storageState: guestFile } );

	test( 'Place order', async ( { frontendUtils, pageObject, page } ) => {
		await page.goto( '/my-account' );

		await expect(
			page.getByRole( 'heading', { name: 'Login' } ),
			'User is not logged out'
		).toBeVisible();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		expect(
			await pageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );

		await pageObject.fillInCheckoutWithTestData( TEST_ADDRESS );
		await pageObject.placeOrder();

		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();
	} );
} );

test.describe( 'Shopper (guest) → Order Confirmation → Create Account', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { frontendUtils, pageObject, requestUtils } ) => {
		await requestUtils.setFeatureFlag( 'experimental-blocks', true );
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		expect(
			await pageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await pageObject.fillInCheckoutWithTestData( TEST_ADDRESS );
		await pageObject.placeOrder();
	} );

	test( 'Delayed account creation flows', async ( {
		page,
		requestUtils,
	} ) => {
		// If delayed account creation is off, no form is shown.
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_delayed_account_creation',
			data: { value: 'no' },
		} );
		await page.reload();
		await expect( page.getByText( 'Create an account with' ) ).toBeHidden();

		// Turn on delayed account creation.
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_delayed_account_creation',
			data: { value: 'yes' },
		} );
		await page.reload();
		await expect(
			page.getByText( 'Create an account with' )
		).toBeVisible();

		// Configure so password field is visible.
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_registration_generate_password',
			data: { value: 'no' },
		} );
		await page.reload();

		// Check visible form elements.
		await expect(
			page.getByText( 'Set a password for john.doe@test.com' )
		).toBeVisible();

		// Fill out the form and test creation works.
		await page
			.getByLabel( 'Password', { exact: true } )
			.fill( 'V3ryStrongP@ssw0rd123!' );
		await page.getByRole( 'button', { name: 'Create account' } ).click();
		await page.waitForURL( /\/checkout\/order-received\// );

		// Verify the account was created.
		await expect(
			page.getByText( 'Your account has been successfully created' )
		).toBeVisible();

		// Verify the user was logged in.
		await page.goto( '/my-account' );
		await expect( page.getByText( 'Hello John Doe' ) ).toBeVisible();
	} );
} );

test.describe( 'Shopper → Order Confirmation → Local Pickup', () => {
	test( 'Confirm shipping address section is hidden, but billing is visible', async ( {
		pageObject,
		frontendUtils,
		admin,
	} ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
		await admin.page.getByLabel( 'Enable local pickup' ).uncheck();
		await admin.page.getByLabel( 'Enable local pickup' ).check();
		await admin.page
			.getByRole( 'button', { name: 'Add pickup location' } )
			.click();
		await admin.page.getByLabel( 'Location name' ).fill( 'Testing' );
		await admin.page.getByPlaceholder( 'Address' ).fill( 'Test Address' );
		await admin.page.getByPlaceholder( 'City' ).fill( 'Test City' );
		await admin.page.getByPlaceholder( 'Postcode / ZIP' ).fill( '90210' );
		await admin.page
			.getByLabel( 'Pickup details' )
			.fill( 'Pickup method.' );
		await admin.page.getByRole( 'button', { name: 'Done' } ).click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();
		await admin.page
			.getByRole( 'button', { name: 'Dismiss this notice' } )
			.click();
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await pageObject.page.getByRole( 'radio', { name: 'Pickup' } ).click();
		await pageObject.fillInCheckoutWithTestData();
		await pageObject.placeOrder();
		await expect(
			pageObject.page.getByRole( 'heading', { name: 'Shipping address' } )
		).toBeHidden();
		await expect(
			pageObject.page.getByRole( 'heading', { name: 'Billing address' } )
		).toBeVisible();
	} );
} );

test.describe( 'Shopper → Order Confirmation → Downloadable Products', () => {
	let confirmationPageUrl: string;

	test.beforeEach( async ( { frontendUtils, pageObject } ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await pageObject.fillInCheckoutWithTestData();
		await pageObject.placeOrder();
		confirmationPageUrl = pageObject.page.url();
	} );

	test( 'Confirm shipping address section is hidden, but billing is visible', async ( {
		pageObject,
	} ) => {
		await expect(
			pageObject.page.getByRole( 'heading', { name: 'Shipping address' } )
		).toBeHidden();
		await expect(
			pageObject.page.getByRole( 'heading', { name: 'Billing address' } )
		).toBeVisible();
	} );

	test( 'Confirm order downloads are visible', async ( {
		pageObject,
		admin,
	} ) => {
		// While order is pending the downloads are hidden.
		await expect(
			pageObject.page.getByRole( 'heading', { name: 'Downloads' } )
		).toBeHidden();

		// Update last order status to completed.
		await admin.visitAdminPage( 'edit.php', 'post_type=shop_order' );
		await admin.page.locator( '.wp-list-table' ).waitFor();
		await admin.page.click(
			'.wp-list-table tbody tr:first-child a.order-view'
		);
		await admin.page.getByRole( 'textbox', { name: 'On hold' } ).click();
		await admin.page.getByRole( 'option', { name: 'Completed' } ).click();
		await admin.page
			.getByRole( 'button', { name: 'Update' } )
			.first()
			.click();

		// Go back to page.
		await pageObject.page.goto( confirmationPageUrl );
		await expect(
			pageObject.page.getByRole( 'heading', { name: 'Downloads' } )
		).toBeVisible();
	} );
} );
