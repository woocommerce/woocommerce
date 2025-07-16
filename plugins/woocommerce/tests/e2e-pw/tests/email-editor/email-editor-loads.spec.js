/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );
const {
	disableEmailEditor,
} = require( './helpers/enable-email-editor-feature' );
const { accessTheEmailEditor } = require( '../../utils/email' );

test.describe( 'WooCommerce Email Editor Core', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.afterAll( async ( { baseURL } ) => {
		await disableEmailEditor( baseURL );
	} );

	test( 'Can enable the email editor', async ( { page } ) => {
		// Navigate to the settings page.
		await page.goto( '/wp-admin/admin.php?page=wc-settings' );

		// Enable the email editor using the UI.
		await page.getByRole( 'link', { name: 'Advanced' } ).click();
		await page.getByRole( 'link', { name: 'Features' } ).click();
		await page
			.getByRole( 'checkbox', { name: 'Enable the block-based email' } )
			.check();
		await page.getByRole( 'button', { name: 'Save changes' } ).click();
		await page.getByRole( 'link', { name: 'Emails' } ).click();
		await expect(
			page.locator( '#email_notification_settings-description' )
		).toContainText(
			'Manage email notifications sent from WooCommerce below'
		);
	} );

	test( 'Can access the email editor', async ( { page } ) => {
		// Try with the new order email.
		await accessTheEmailEditor( page, 'New order' );
		await page.getByRole( 'tab', { name: 'Email' } ).click();
		await expect(
			page.locator( '.editor-post-card-panel__title' )
		).toContainText( 'New order' );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByLabel( 'Block: Heading' )
		).toContainText( `New order: #[woocommerce/order-number],` );
	} );

	test( 'Can preview in new tab', async ( { page } ) => {
		await accessTheEmailEditor( page, 'New order' );
		await page.getByRole( 'button', { name: 'View', exact: true } ).click();

		const [ newPage ] = await Promise.all( [
			page.waitForEvent( 'popup' ), // Waits for the new tab to open
			page
				.getByRole( 'menuitem', { name: 'Preview in new tab' } )
				.click(),
		] );
		await newPage.bringToFront();
		await newPage.waitForLoadState( 'domcontentloaded' );
		// eslint-disable-next-line playwright/no-wait-for-selector -- wait for the tab to be loaded.
		await newPage.waitForSelector( '.wp-block-heading' );
		await page.close(); // close the original tab.
		await expect( newPage.url() ).toContain( 'preview=true' );
		await expect( newPage.locator( 'body' ) ).toContainText(
			'New order: #12345,'
		);
	} );

	test( 'Can send test email', async ( { page } ) => {
		await accessTheEmailEditor( page, 'New order' );
		await page.getByRole( 'button', { name: 'View', exact: true } ).click();
		await page
			.getByRole( 'menuitem', { name: 'Send a test email' } )
			.click();
		await expect(
			page.locator( '.components-modal__header' )
		).toContainText( 'Send a test email' );
		await expect(
			page.getByRole( 'button', { name: 'Send test email' } )
		).toBeEnabled();
		await expect(
			page.getByRole( 'button', { name: 'Cancel' } )
		).toBeEnabled();
		await page.getByRole( 'button', { name: 'Send test email' } ).click();
		await expect(
			page.locator( '.woocommerce-send-preview-modal-notice-error' )
		).toContainText( 'Sorry, we were unable to send this email.' );
		await page.getByRole( 'button', { name: 'Close' } ).click();
	} );

	test( 'Can edit and save content', async ( { page } ) => {
		await accessTheEmailEditor( page, 'New order' );
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'You’ve received a new' )
		).toBeVisible();

		await page
			.locator( 'iframe[name="editor-canvas"]' )
			.contentFrame()
			.getByText( 'You’ve received a new' )
			.fill(
				'Hello world from Woo plugin\nYou’ve received a new order from [woocommerce/customer-full-name]'
			);
		await expect(
			page.getByRole( 'button', { name: 'Save', exact: true } )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect(
			page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'Hello world from Woo' )
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'View', exact: true } )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'View', exact: true } ).click();
		const page1Promise = page.waitForEvent( 'popup' );
		await page
			.getByRole( 'menuitem', { name: 'Preview in new tab' } )
			.click();
		const page1 = await page1Promise;
		await expect( page1.locator( 'body' ) ).toContainText(
			'Hello world from Woo plugin'
		);
	} );
} );
