const { test, expect } = require( '@playwright/test' );
const {
	clickAddNewMenuItem,
	isBlockProductEditorEnabled,
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );

const ALL_PRODUCTS_URL = 'wp-admin/edit.php?post_type=product';
const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

let isNewProductEditorEnabled = false;

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

async function expectOldProductEditor( page ) {
	await expect(
		page.locator( '#woocommerce-product-data h2' )
	).toContainText( 'Product data' );
}

async function expectBlockProductEditor( page ) {
	await expect(
		page.locator( '.woocommerce-product-header__inner h1' )
	).toContainText( 'Add new product' );
}

test.describe( 'Enable block product editor', () => {
	test.describe( 'Default (disabled)', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);
		} );

		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is being tested'
		);

		test( 'is feature flag disabled', async ( { page } ) => {
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);

			expect( isNewProductEditorEnabled ).toBeFalsy();
		} );

		test( 'is not hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( ALL_PRODUCTS_URL );
			await clickAddNewMenuItem( page );
			await expectOldProductEditor( page );
		} );
	} );

	test.describe( 'Enabled', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.afterAll( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);
			if ( isNewProductEditorEnabled ) {
				await toggleBlockProductEditor( 'disable', page );
			}
		} );

		test.skip(
			isNewProductEditorEnabled && isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can enable the block product editor', async ( { page } ) => {
			await toggleBlockProductEditor( 'enable', page );
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await expectBlockProductEditor( page );
		} );

		test( 'is hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( ALL_PRODUCTS_URL );
			await clickAddNewMenuItem( page );
			await expectBlockProductEditor( page );
		} );
	} );
} );
