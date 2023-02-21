const { test, expect } = require( '@playwright/test' );

const EXPERIMENTAL_FEATURES_SETTINGS_URL =
	'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features';
const EDIT_PRODUCT_URL = 'wp-admin/edit.php?post_type=product';
const NEW_PRODUCT_URL = 'wp-admin/post-new.php?post_type=product';

const NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR =
	'#woocommerce_new_product_management_enabled';

async function expectExperimentalFeatureExists( page ) {
	// make sure the Advanced tab is active
	await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
		'Advanced'
	);

	// make sure the Features sub-tab is active
	await expect(
		page.locator( 'ul.subsubsub > li > a.current' )
	).toContainText( 'Features' );

	// make sure the new product editor experimental feature is shown
	await expect(
		page.locator( NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR )
	).toBeVisible();
}

async function clickAddNewMenuItem( page ) {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

test.describe( 'New Product Editor', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'is disabled by default', async ( { page } ) => {
		await page.goto( EXPERIMENTAL_FEATURES_SETTINGS_URL );

		await expectExperimentalFeatureExists( page );

		// make sure the new product editor is unchecked
		await expect(
			page.locator( NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR )
		).not.toBeChecked();
	} );

	test( 'is not used when disabled by default', async ( { page } ) => {
		await page.goto( EDIT_PRODUCT_URL );

		await clickAddNewMenuItem( page );

		// make sure the old product editor is shown
		await expect(
			page.locator( '#woocommerce-product-data h2' )
		).toContainText( 'Product data' );
	} );

	test( 'can be enabled', async ( { page } ) => {
		await page.goto( EXPERIMENTAL_FEATURES_SETTINGS_URL );

		await expectExperimentalFeatureExists( page );

		// enable the new product editor
		await page.check( NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR );

		// save changes
		await page.click( 'text=Save changes' );

		// make sure settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved'
		);

		// make sure the new product editor is enabled
		await expect(
			page.locator( NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR )
		).toBeChecked();
	} );

	test( 'is used when enabled', async ( { page } ) => {
		await page.goto( EDIT_PRODUCT_URL );

		await clickAddNewMenuItem( page );

		// make sure the new product editor is shown
		await expect(
			page.locator( '.woocommerce-product-title__wrapper' )
		).toContainText( 'New product' );
	} );

	test( 'can be disabled', async ( { page } ) => {
		await page.goto( EXPERIMENTAL_FEATURES_SETTINGS_URL );

		await expectExperimentalFeatureExists( page );

		// disable the new product editor
		await page.uncheck( NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR );

		// save changes
		await page.click( 'text=Save changes' );

		// make sure settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved'
		);

		// make sure the new product editor is disabled
		await expect(
			page.locator( NEW_PRODUCT_EDITOR_EXPERIMENTAL_FEATURE_SELECTOR )
		).not.toBeChecked();
	} );

	test( 'is not used when disabled', async ( { page } ) => {
		await page.goto( EDIT_PRODUCT_URL );

		await clickAddNewMenuItem( page );

		// make sure the old product editor is shown
		await expect(
			page.locator( '#woocommerce-product-data h2' )
		).toContainText( 'Product data' );
	} );
} );
