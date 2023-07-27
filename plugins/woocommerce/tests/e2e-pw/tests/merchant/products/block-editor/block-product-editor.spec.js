const { test, expect } = require( '@playwright/test' );

const ALL_PRODUCTS_URL = 'wp-admin/edit.php?post_type=product';
const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin//admin.php?page=wc-admin&path=%2Fadd-product';
const SETTINGS_URL =
	'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features';

let isNewProductEditorSupposedToBeEnabled = false;

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

async function toggleBlockProductEditor( page ) {
	await page
		.locator( '#woocommerce_feature_product_block_editor_enabled' )
		.check();
	await page
		.locator( '.submit' )
		.getByRole( 'button', {
			name: 'Save changes',
		} )
		.click();
}

async function clickAddNewMenuItem( page ) {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

async function dismissFeedbackModalIfShown( page ) {
	if ( ! isTrackingSupposedToBeEnabled ) {
		// no modal should be shown, so don't even look for button
		return;
	}

	try {
		await page
			.locator( '.woocommerce-product-mvp-feedback-modal' )
			.getByRole( 'button', { name: 'Skip' } )
			.click( { timeout: 5000 } );
	} catch ( error ) {}
}

async function expectOldProductEditor( page ) {
	await expect(
		page.locator( '#woocommerce-product-data h2' )
	).toContainText( 'Product data' );
}

async function isBlockProductEditorEnabled( page ) {
	await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
	return await page
		.locator( '.woocommerce-product-header__inner h1' )
		.isVisible();
}

async function expectBlockProductEditor( page ) {
	await expect(
		page.locator( '.woocommerce-product-header__inner h1' )
	).toContainText( 'Add new product' );
}

test.describe( 'Toggle block product editor', () => {
	test.describe( 'Default (disabled)', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorSupposedToBeEnabled =
				await isBlockProductEditorEnabled( page );
		} );

		test.skip(
			isNewProductEditorSupposedToBeEnabled &&
				isTrackingSupposedToBeEnabled,
			'The block product editor is being tested'
		);

		test( 'is feature flag disabled', async ( { page } ) => {
			// we have to go to a WCAdmin page to get the wcAdminFeatures global
			await page.goto( ALL_PRODUCTS_URL );

			const wcAdminFeatures = await page.evaluate(
				'window.wcAdminFeatures'
			);

			expect(
				!! wcAdminFeatures[ 'new-product-management-experience' ]
			).toBeFalsy();
		} );

		test( 'is not hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( ALL_PRODUCTS_URL );
			await clickAddNewMenuItem( page );
			await expectOldProductEditor( page );
		} );
	} );

	test.describe( 'Enabled', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorSupposedToBeEnabled =
				await isBlockProductEditorEnabled( page );
		} );

		test.skip(
			isNewProductEditorSupposedToBeEnabled &&
				isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can enable the block product editor', async ( { page } ) => {
			await page.goto( SETTINGS_URL );
			await toggleBlockProductEditor( page );
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await expectBlockProductEditor( page );
		} );

		test( 'is hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( ALL_PRODUCTS_URL );
			await clickAddNewMenuItem( page );
			await expectBlockProductEditor( page );
		} );

		test( 'can be disabled from the header', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );

			// turn off block product editor from the header
			await page
				.locator( '.components-dropdown-menu' )
				.getByRole( 'button', { name: 'Options' } )
				.click();
			await page
				.getByRole( 'menuitem', {
					name: 'Turn off the new product form',
				} )
				.click();

			await dismissFeedbackModalIfShown( page );

			await expectOldProductEditor( page );
		} );
	} );
} );
