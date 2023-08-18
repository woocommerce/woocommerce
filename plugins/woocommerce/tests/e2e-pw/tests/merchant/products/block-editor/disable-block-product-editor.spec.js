const { test } = require( '@playwright/test' );
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
const SETTINGS_URL =
	'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features';

let isNewProductEditorEnabled = false;

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

async function dismissFeedbackModalIfShown( page ) {
	// if ( ! isTrackingSupposedToBeEnabled ) {
	// 	// no modal should be shown, so don't even look for button
	// 	console.log('Feedback modal not shown');
	// 	return;
	// }

	try {
		await page
			.getByText('Skip').nth(3)
			.click( { timeout: 5000 } );
	} catch ( error ) {}
}

test.describe.serial( 'Disable block product editor', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		isNewProductEditorEnabled = await isBlockProductEditorEnabled( page );
		if ( ! isNewProductEditorEnabled ) {
			await toggleBlockProductEditor( 'enable', page );
		}
	} );

	test.afterEach( async ( { browser } ) => {
		const context = await browser.newContext();
		const page = await context.newPage();
		isNewProductEditorEnabled = await isBlockProductEditorEnabled( page );
		if ( isNewProductEditorEnabled ) {
			await toggleBlockProductEditor( 'disable', page );
		}
	} );

	test.skip(
		isNewProductEditorEnabled && isTrackingSupposedToBeEnabled,
		'The block product editor is not being tested'
	);

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
	test( 'can be disabled from settings', async ( { page } ) => {
		await toggleBlockProductEditor( 'disable', page );
		await page.goto( ALL_PRODUCTS_URL );
		await clickAddNewMenuItem( page );
		await expectOldProductEditor( page );
	} );
} );
