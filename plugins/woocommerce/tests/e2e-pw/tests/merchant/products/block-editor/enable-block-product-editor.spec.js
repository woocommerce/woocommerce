const qit = require('/qitHelpers');
const { test, expect } = require( '@playwright/test' );
const {
	clickAddNewMenuItem,
	expectBlockProductEditor,
	expectOldProductEditor,
	isBlockProductEditorEnabled,
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );

const ALL_PRODUCTS_URL = 'wp-admin/edit.php?post_type=product';
const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

let isNewProductEditorEnabled = false;

const isTrackingSupposedToBeEnabled = !! qit.getEnv('ENABLE_TRACKING');

async function disableNewEditorIfEnabled( browser ) {
	const context = await browser.newContext();
	const page = await context.newPage();
	isNewProductEditorEnabled = await isBlockProductEditorEnabled( page );
	if ( isNewProductEditorEnabled ) {
		await toggleBlockProductEditor( 'disable', page );
	}
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'Enable block product editor', () => {
	test.describe( 'Enabled', () => {
		test.use( { storageState: qit.getEnv('ADMINSTATE') } );

		test.beforeEach( async ( { browser } ) => {
			await disableNewEditorIfEnabled( browser );
		} );

		test.afterEach( async ( { browser } ) => {
			await disableNewEditorIfEnabled( browser );
		} );

		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'is not hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( ALL_PRODUCTS_URL );
			await clickAddNewMenuItem( page );
			await expectOldProductEditor( page );
		} );

		test( 'can enable the block product editor', async ( { page } ) => {
			await toggleBlockProductEditor( 'enable', page );
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await expectBlockProductEditor( page );
		} );
	} );
} );
