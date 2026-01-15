const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

const setFeatureFlag = async ( baseURL, name, value ) =>
	await setOption( request, baseURL, name, value );

const setBlockEmailEditorFeatureFlag = async ( baseURL, value ) =>
	await setFeatureFlag(
		baseURL,
		'woocommerce_feature_block_email_editor_enabled',
		value
	);

test.describe( 'WooCommerce Email Settings List View', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.afterAll( async ( { baseURL } ) => {
		await setBlockEmailEditorFeatureFlag( baseURL, 'no' );
	} );

	test( 'Email settings list view renders correctly and allows to edit email status and search', async ( {
		page,
		baseURL,
	} ) => {
		await setBlockEmailEditorFeatureFlag( baseURL, 'yes' );
		await setFeatureFlag(
			baseURL,
			'woocommerce_feature_point_of_sale_enabled',
			'no'
		);

		// Navigate to WooCommerce Email Settings page
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		// Check that the ListView component is present
		const listViewLocator = page.locator(
			'.woocommerce-email-listing-listview'
		);

		await expect( listViewLocator ).toBeVisible();

		// Check that "New order" email type exists within the list view
		await expect( listViewLocator.getByText( /New order/ ) ).toBeVisible();

		// Check table columns
		// Check that Title column exists
		await expect(
			listViewLocator.getByRole( 'columnheader', { name: 'Title' } )
		).toBeVisible();

		// Check that Status column exists
		await expect(
			listViewLocator.getByRole( 'columnheader', { name: 'Status' } )
		).toBeVisible();

		// Check that Recipients column exists
		await expect(
			listViewLocator.getByRole( 'columnheader', {
				name: 'Recipient(s)',
			} )
		).toBeVisible();

		// Check that Actions column exists
		await expect( listViewLocator.getByText( 'Actions' ) ).toBeVisible();

		// Check that first row shows Active status
		const firstRow = listViewLocator.locator( 'tr' ).nth( 1 ); // nth(1) because nth(0) is header row
		await expect( firstRow.locator( 'td' ).nth( 2 ) ).toHaveText(
			'Enabled'
		);

		// Open the first row more actions menu
		await firstRow.locator( '.dataviews-all-actions-button' ).click();

		// Check that the "Disable email" option is present and clickable
		await expect(
			page.getByRole( 'menuitem', { name: 'Disable email' } )
		).toBeVisible();
		await page.getByRole( 'menuitem', { name: 'Disable email' } ).click();

		// Check that the email status is now Draft
		await expect( firstRow.locator( 'td' ).nth( 2 ) ).toHaveText(
			'Inactive'
		);

		// Open the first row more actions menu again
		await firstRow.locator( '.dataviews-all-actions-button' ).click();

		// Check that the "Enable email" option is present and clickable
		await expect(
			page.getByRole( 'menuitem', { name: 'Enable email' } )
		).toBeVisible();
		await page.getByRole( 'menuitem', { name: 'Enable email' } ).click();

		// Check that the email status is now Active again
		await expect( firstRow.locator( 'td' ).nth( 2 ) ).toHaveText(
			'Enabled'
		);

		// I want to check that search works
		await page.getByPlaceholder( 'Search' ).fill( 'Failed order' );
		await expect(
			listViewLocator.getByText( 'Failed order' )
		).toBeVisible();

		// Check that only one row is visible after search
		const rows = listViewLocator.locator( 'tr' );
		// Add 1 to account for header row
		await expect( rows ).toHaveCount( 2 );
	} );
} );
