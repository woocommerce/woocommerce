/**
 * External dependencies
 */
import { test, expect, type Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	enableEmailEditor,
	disableEmailEditor,
	resetWCTransactionalEmail,
} from './helpers/enable-email-editor-feature';
import { accessTheEmailEditor, waitForEditorSaved } from '../../utils/email';
import { locks } from '../../fixtures/fixtures';

/**
 * Helper function to switch from email editor to template editing mode.
 *
 * @param page The Playwright page.
 */
async function switchToTemplateEditingMode( page: Page ) {
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

	// Click the "Edit template" link in the sidebar
	await page
		.getByRole( 'button', { name: 'Edit template', exact: true } )
		.click();

	// Confirm in the modal
	await page
		.getByRole( 'dialog' )
		.getByRole( 'button', { name: 'Edit template', exact: true } )
		.click();

	// Wait for template editor to load
	await expect(
		page.locator( 'iframe[name="editor-canvas"]' )
	).toBeVisible();
}

test.describe(
	'WooCommerce Email Editor Reset Template',
	{ lock: locks.EMAIL_FEATURE_FLAGS },
	() => {
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

			// Update the footer text and add a custom text
			await page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByText( 'All Rights Reserved' )
				.fill( `All Rights Reserved. Custom text: ${ uniqueText }` );

			// Save the customization
			await page
				.getByRole( 'button', { name: 'Save', exact: true } )
				.click();

			await waitForEditorSaved( page );

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

			// Verify the reset confirmation modal appears. Up to WP 7.1 the email
			// editor shows its own reset action, from WP 7.2 core's.
			await expect(
				page.getByText(
					/Are you sure you want to reset.*to default\?|Reset to default and clear all customizations\?/
				)
			).toBeVisible();

			// Verify Cancel button exists
			await expect(
				page.getByRole( 'button', { name: 'Cancel' } )
			).toBeVisible();

			// Click the Reset button in the modal
			await page
				.getByRole( 'button', { name: 'Reset', exact: true } )
				.click();

			// The editor marks the template as reset before the server deletes
			// the custom copy, so wait for the server to serve the default one.
			await expect
				.poll( () =>
					page.evaluate( async () => {
						const id = window.wp.data
							.select( 'core/editor' )
							.getCurrentPostId();
						const savedTemplate = await window.wp.apiFetch( {
							path: `/wp/v2/templates/${ id }?context=edit`,
						} );
						return savedTemplate.source;
					} )
				)
				.not.toBe( 'custom' );

			// Verify the custom content is gone (template is reset)
			await expect( editorFrame.getByText( uniqueText ) ).toBeHidden();

			// Verify Save button is disabled (no changes to save)
			await expect(
				page.getByRole( 'button', { name: 'Save', exact: true } )
			).toBeDisabled();

			// Verify Actions button is disabled (template is no longer custom)
			await expect(
				page.getByRole( 'button', { name: 'Actions' } )
			).toBeDisabled();
		} );
	}
);
