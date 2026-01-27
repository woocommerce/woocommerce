const { test, expect } = require( '@playwright/test' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );
const {
	enableEmailEditor,
	disableEmailEditor,
	resetWCTransactionalEmail,
} = require( './helpers/enable-email-editor-feature' );
const { accessTheEmailEditor } = require( '../../utils/email' );

/**
 * Helper function to switch from email editor to template editing mode.
 *
 * @param {import('@playwright/test').Page} page The Playwright page.
 */
async function switchToTemplateEditingMode( page ) {
	// Open the Settings panel if not already open
	const settingsPanel = page.locator(
		'.woocommerce-email-editor__settings-panel'
	);
	const isPanelExpanded = await settingsPanel.evaluate( ( elem ) =>
		elem.classList.contains( 'is-opened' )
	);

	if ( ! isPanelExpanded ) {
		// Click the Settings button within the Email panel
		await page
			.getByLabel( 'Email' )
			.getByRole( 'button', { name: 'Settings' } )
			.click();
	}

	// Click the "Woo Email Template" button to open dropdown
	await page.getByRole( 'button', { name: 'Template actions' } ).click();

	// Click "Edit template" in the dropdown
	await page.getByRole( 'menuitem', { name: 'Edit template' } ).click();

	// Click "Edit template" button in the modal
	await page
		.getByRole( 'button', { name: 'Edit template', exact: true } )
		.click();

	// Wait for template editor to load
	await expect(
		page.locator( 'iframe[name="editor-canvas"]' )
	).toBeVisible();
}

test.describe( 'WooCommerce Email Editor Reset Template', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL );
	} );

	test.afterAll( async ( { baseURL } ) => {
		await resetWCTransactionalEmail( baseURL, 'customer_note' );
		await disableEmailEditor( baseURL );
	} );

	test( 'Can reset a customized email template to default', async ( {
		page,
	} ) => {
		// Access the email editor
		await accessTheEmailEditor( page, 'Customer note' );

		// Wait for the editor to load
		await expect(
			page.locator( '#woocommerce-email-editor' )
		).toBeVisible();

		// Switch to template editing mode
		await switchToTemplateEditingMode( page );

		// Make a customization - edit an existing paragraph block
		const uniqueText = `CUSTOM TEXT ${ Date.now() }`;
		const editorFrame = page
			.frameLocator( 'iframe[name="editor-canvas"]' )
			.first();

		// Click on the last paragraph to select it (the footer paragraph is editable)
		await editorFrame.locator( 'p' ).last().click();

		// Clear existing text and type new text
		await page.keyboard.press( 'ControlOrMeta+A' ); // Select all
		await page.keyboard.type( uniqueText );

		// Save the customization
		await page.getByRole( 'button', { name: 'Save', exact: true } ).click();

		// Wait for the save to complete
		// eslint-disable-next-line playwright/no-wait-for-timeout -- wait for content to be saved
		await page.waitForTimeout( 1000 );

		// Verify the customization is present
		await expect( editorFrame.getByText( uniqueText ) ).toBeVisible();

		// Switch to Template tab to access the Actions button
		await page.getByRole( 'tab', { name: 'Template' } ).click();

		// Wait for Actions button to be enabled
		await expect(
			page.getByRole( 'button', { name: 'Actions' } )
		).toBeEnabled();

		// Open the actions dropdown (three dots menu)
		await page.getByRole( 'button', { name: 'Actions' } ).click();

		// Verify the Reset action is present
		await expect(
			page.getByRole( 'menuitem', { name: 'Reset' } )
		).toBeVisible();

		// Click the Reset action
		await page.getByRole( 'menuitem', { name: 'Reset' } ).click();

		// Verify the reset confirmation modal appears
		await expect(
			page.getByText( /Are you sure you want to reset.*to default\?/ )
		).toBeVisible();

		// Verify Cancel button exists
		await expect(
			page.getByRole( 'button', { name: 'Cancel' } )
		).toBeVisible();

		// Click the Reset button in the modal
		await page
			.getByRole( 'button', { name: 'Reset', exact: true } )
			.click();

		// Wait for the reset operation to complete
		// eslint-disable-next-line playwright/no-wait-for-timeout -- wait for reset to complete
		await page.waitForTimeout( 2000 );

		// Verify the custom content is gone (template is reset)
		const editorFrameAfterReset = page
			.frameLocator( 'iframe[name="editor-canvas"]' )
			.first();
		await expect(
			editorFrameAfterReset.getByText( uniqueText )
		).toBeHidden();

		// Verify Save button is disabled (no changes to save)
		await expect(
			page.getByRole( 'button', { name: 'Save', exact: true } )
		).toBeDisabled();

		// Verify Actions button is disabled (template is no longer custom)
		await expect(
			page.getByRole( 'button', { name: 'Actions' } )
		).toBeDisabled();
	} );
} );
