const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );
const { getWooEmails } = require( '../../utils/email' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

const setFeatureFlag = async ( baseURL, value ) =>
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_block_email_editor_enabled',
		value
	);

/**
 * The purpose of this test is to alert us if the selectors that are used to track telemetry events in the email editor are changed.
 *
 * The test checks that the selectors that are used to track telemetry events in the email editor are present.
 */
test.describe( 'WooCommerce Email Editor Tracking Selectors', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.afterAll( async ( { baseURL } ) => {
		await setFeatureFlag( baseURL, 'no' );
	} );

	test( 'Check selectors for tracking events', async ( {
		page,
		baseURL,
	} ) => {
		await setFeatureFlag( baseURL, 'yes' );

		// Navigate to WooCommerce Email Settings page to generate email posts
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );
		const emails = await getWooEmails();

		await page.goto(
			`wp-admin/post.php?post=${ emails.data[ 0 ].id }&action=edit`
		);

		// Check that the Editor is present
		const editorLocator = page.locator( '#woocommerce-email-editor' );
		await expect( editorLocator ).toBeVisible();

		// Check listview sidebar toggle button selector
		await expect(
			editorLocator.locator(
				'.editor-document-tools__document-overview-toggle'
			)
		).toBeVisible();
		// Check inserter sidebar toggle button selector
		await expect(
			editorLocator.locator( '.editor-document-tools__inserter-toggle' )
		).toBeVisible();
		// Check Email tab in the sidebar
		await expect(
			editorLocator.locator( '[data-tab-id="edit-post/block"]' )
		).toBeVisible();
		// Check Block tab in the sidebar
		await expect(
			editorLocator.locator( '[data-tab-id="edit-post/document"]' )
		).toBeVisible();
		// Check preview dropdown toggle
		await expect(
			editorLocator.locator( '.editor-preview-dropdown__toggle' )
		).toBeVisible();

		// Check inserter sidebar close icon
		// Click inserter sidebar toggle button
		await editorLocator
			.locator( '.editor-document-tools__inserter-toggle' )
			.click();
		// Check inserter sidebar close icon is now visible after opening
		await expect(
			editorLocator.locator(
				'.block-editor-inserter__menu .block-editor-tabbed-sidebar__close-button'
			)
		).toBeVisible();

		// Check save button selector
		await expect(
			editorLocator.locator( '.editor-post-publish-button' )
		).toBeVisible();

		// Check more menu toggle selector
		await expect(
			editorLocator.locator(
				'.components-dropdown-menu__toggle[aria-label="Options"]'
			)
		).toBeVisible();

		// Check open preview in new tab button
		// Click preview dropdown toggle
		await editorLocator
			.locator( '.editor-preview-dropdown__toggle' )
			.click();
		// Check open in new tab selector
		await expect(
			page.locator( '.editor-preview-dropdown__button-external' )
		).toBeVisible();
		// Close preview dropdown
		await editorLocator
			.locator( '.editor-preview-dropdown__toggle' )
			.click();

		// Check command bar button
		await editorLocator.locator( '.editor-document-bar' ).click();
		// Fill command bar input with 'a' to get some results
		await page
			.locator( '.commands-command-menu__header input' )
			.fill( 'a' );
		// Check command selected selector
		await expect(
			page.locator(
				'.commands-command-menu__container [role="option"]:first-child'
			)
		).toBeVisible();
		// Press Escape key to close command bar
		await page.keyboard.press( 'Escape' );

		// Check header block tools toggle button
		// Enable header block tools
		await editorLocator
			.locator(
				'.components-dropdown-menu__toggle[aria-label="Options"]'
			)
			.click();
		// Check header preview dropdown preview in new tab selected
		// Click header preview dropdown preview in new tab
		await page
			.locator( '.components-popover' )
			.getByText( 'Top toolbar' )
			.click();
		// Click canvas to select a block to make top toolbar visible
		await page
			.frameLocator( 'iframe[name="editor-canvas"]' )
			.locator( '.wp-block-heading' )
			.first()
			.click();
		await expect(
			editorLocator.locator( '.editor-collapsible-block-toolbar__toggle' )
		).toBeVisible();
	} );
} );
