/**
 * External dependencies
 */
import { test, expect, request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption } from '../../utils/options';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const setFeatureFlag = async ( baseURL: string, name: string, value: string ) =>
	await setOption( request, baseURL, name, value );

const setBlockEmailEditorFeatureFlag = async (
	baseURL: string,
	value: string
) =>
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

		// Navigate to WooCommerce Email Settings page
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		// Check that the ListView component is present
		const listViewLocator = page.locator(
			'.woocommerce-email-listing-listview'
		);

		await expect( listViewLocator ).toBeVisible();

		// The listing's DataViews styles ship in its lazy chunk. Without them
		// the table falls back to the browser default (border-collapse:
		// separate) and renders as plain, unstyled rows.
		await expect(
			listViewLocator.locator( '.dataviews-view-table' )
		).toHaveCSS( 'border-collapse', 'collapse' );

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

		// Target the "New order" row explicitly so the test is independent of list ordering.
		const newOrderRow = listViewLocator.locator( 'tr', {
			hasText: 'New order',
		} );
		await expect( newOrderRow.locator( 'td' ).nth( 2 ) ).toHaveText(
			'Active'
		);

		// Open the row's more actions menu
		await newOrderRow.locator( '.dataviews-all-actions-button' ).click();

		// Check that the "Deactivate email" option is present and clickable
		await expect(
			page.getByRole( 'menuitem', { name: 'Deactivate email' } )
		).toBeVisible();
		await page
			.getByRole( 'menuitem', { name: 'Deactivate email' } )
			.click();

		// Check that the email status is now Draft
		await expect( newOrderRow.locator( 'td' ).nth( 2 ) ).toHaveText(
			'Inactive'
		);

		// Open the row's more actions menu again
		await newOrderRow.locator( '.dataviews-all-actions-button' ).click();

		// Check that the "Activate email" option is present and clickable
		await expect(
			page.getByRole( 'menuitem', { name: 'Activate email' } )
		).toBeVisible();
		await page.getByRole( 'menuitem', { name: 'Activate email' } ).click();

		// Check that the email status is now Active again
		await expect( newOrderRow.locator( 'td' ).nth( 2 ) ).toHaveText(
			'Active'
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

	test( 'Preview action renders the file template for emails without a saved post', async ( {
		page,
		baseURL,
	} ) => {
		await setBlockEmailEditorFeatureFlag( baseURL, 'yes' );

		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );
		const listViewLocator = page.locator(
			'.woocommerce-email-listing-listview'
		);
		await expect( listViewLocator ).toBeVisible();

		// A row no other spec creates a post for, so it renders from the file
		// template and the Preview action must use the admin preview page.
		const row = listViewLocator.locator( 'tr', {
			hasText: 'Order on hold',
		} );
		const popupPromise = page.waitForEvent( 'popup' );
		await row.getByRole( 'button', { name: 'Preview' } ).click();
		const popup = await popupPromise;

		await expect( popup ).toHaveURL( /preview_woo_block_email/ );
		// The wooemailtemplate chrome proves the block pipeline rendered it.
		await expect( popup.locator( 'body' ) ).toContainText(
			'All Rights Reserved'
		);
	} );

	test( 'Email listing assets load only where the listing renders', async ( {
		page,
		baseURL,
	} ) => {
		// The chunk's stylesheet link is its durable footprint: webpack removes
		// the chunk script element once it has run. On these pages the listing
		// fill never registers, because its slot element is not rendered, so no
		// code path can request the chunk at all.
		const listingAssets = () =>
			page.locator( 'link[href*="settings-email-listing"]' );

		// Other settings tabs run the same settings-embed script but never
		// fetch the listing chunk.
		await setBlockEmailEditorFeatureFlag( baseURL, 'yes' );
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=general' );
		await expect(
			page.locator( 'script[src*="settings-embed"]' )
		).toHaveCount( 1 );
		await expect( listingAssets() ).toHaveCount( 0 );

		// With the block email editor off there is no listing slot, so the
		// Emails tab does not fetch the chunk either.
		await setBlockEmailEditorFeatureFlag( baseURL, 'no' );
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );
		await expect(
			page.locator( '#wc_settings_email_listing_slotfill' )
		).toHaveCount( 0 );
		await expect( listingAssets() ).toHaveCount( 0 );
	} );
} );
