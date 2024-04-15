/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	FREE_SHIPPING_NAME,
	FREE_SHIPPING_PRICE,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from './constants';
import { CheckoutPage } from './checkout.page';

const testData = {
	firstname: 'John',
	lastname: 'Doe',
	addressfirstline: '123 Easy Street',
	addresssecondline: 'Testville',
	country: 'United States (US)',
	city: 'New York',
	state: 'New York',
	postcode: '90210',
	email: 'john.doe@test.com',
	phone: '01234567890',
};

const test = base.extend< { pageObject: CheckoutPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Order Confirmation (logged in user)', () => {
	test.beforeEach( async ( { admin, editorUtils, localPickupUtils } ) => {
		await localPickupUtils.disableLocalPickup();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//order-confirmation',
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editorUtils.transformIntoBlocks();
	} );

	test( 'Place order', async ( { frontendUtils, pageObject, page } ) => {
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
		await pageObject.fillInCheckoutWithTestData( testData );
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
		// Update order status to Processing
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

		// The following tests are skipped until the multiple sign in roles is implemented
		// - Confirm details are hidden when logged out
		// - Confirm data is hidden without valid session/key
	} );
} );

test.describe( 'Shopper → Order Confirmation (guest user)', () => {
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

		await pageObject.fillInCheckoutWithTestData( testData );
		await pageObject.placeOrder();

		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();
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
		await pageObject.page
			.getByRole( 'radio', { name: 'Local Pickup free' } )
			.click();
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
		await admin.page.waitForSelector( '.wp-list-table' );
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
