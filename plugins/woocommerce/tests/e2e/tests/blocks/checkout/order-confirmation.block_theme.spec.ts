/**
 * External dependencies
 */
import {
	test as base,
	expect,
	guestFile,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

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

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page, requestUtils }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
			requestUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper (logged-in) → Order Confirmation', () => {
	test.beforeEach( async ( { admin, editor, localPickupUtils } ) => {
		await localPickupUtils.disableLocalPickup();

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//order-confirmation`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.transformIntoBlocks();
	} );

	test( 'Known shopper details require owner and key unless the filter opts out', async ( {
		frontendUtils,
		checkoutPageObject,
		page,
		requestUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await checkoutPageObject.fillInCheckoutWithTestData( TEST_ADDRESS );
		await checkoutPageObject.placeOrder();

		await checkoutPageObject.verifyOrderConfirmationDetails();

		const orderReceivedURL = page.url();
		await page.context().clearCookies();
		await page.goto( '/my-account' );
		await expect(
			page.getByRole( 'button', { name: 'Log in' } )
		).toBeVisible();

		await page.goto( orderReceivedURL );
		await checkoutPageObject.verifyOrderConfirmationDetails( false );

		const missingKeyURL = new URL( orderReceivedURL );
		missingKeyURL.searchParams.delete( 'key' );
		await page.goto( missingKeyURL.toString() );
		await checkoutPageObject.verifyOrderConfirmationDetails( false );

		const wrongKeyURL = new URL( orderReceivedURL );
		wrongKeyURL.searchParams.set( 'key', 'wc_order_wrong' );
		await page.goto( wrongKeyURL.toString() );
		await checkoutPageObject.verifyOrderConfirmationDetails( false );

		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-order-confirmation-filters'
		);
		await page.goto( orderReceivedURL );
		await checkoutPageObject.verifyOrderConfirmationDetails();
	} );
} );

test.describe( 'Shopper (guest) → Order Confirmation → Create Account', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach(
		async ( { frontendUtils, checkoutPageObject, requestUtils } ) => {
			await requestUtils.setFeatureFlag( 'experimental-blocks', true );
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
			expect(
				await checkoutPageObject.selectAndVerifyShippingOption(
					FREE_SHIPPING_NAME,
					FREE_SHIPPING_PRICE
				)
			).toBe( true );
			await checkoutPageObject.fillInCheckoutWithTestData( TEST_ADDRESS );
			await checkoutPageObject.placeOrder();
		}
	);

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

test.describe( 'Shopper → Order Confirmation → Downloadable Products', () => {
	let confirmationPageUrl: string;

	test.beforeEach( async ( { frontendUtils, checkoutPageObject } ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		confirmationPageUrl = checkoutPageObject.page.url();
	} );

	test( 'Completed downloadable order exposes named entitlement links', async ( {
		checkoutPageObject,
		admin,
	} ) => {
		// While order is pending the downloads are hidden.
		await expect(
			checkoutPageObject.page.getByRole( 'heading', {
				name: 'Downloads',
			} )
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
		await checkoutPageObject.page.goto( confirmationPageUrl );
		await expect(
			checkoutPageObject.page.getByRole( 'heading', {
				name: 'Downloads',
			} )
		).toBeVisible();

		const downloadsSection = checkoutPageObject.page.locator(
			'[data-block-name="woocommerce/order-confirmation-downloads"]'
		);
		for ( const downloadName of [ 'Single 1', 'Single 2' ] ) {
			const downloadLink = downloadsSection.getByRole( 'link', {
				name: downloadName,
				exact: true,
			} );
			await expect( downloadLink ).toBeVisible();
			await expect( downloadLink ).toHaveAttribute( 'href', /.+/ );
		}
	} );
} );
