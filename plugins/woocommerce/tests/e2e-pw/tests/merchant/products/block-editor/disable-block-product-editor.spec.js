const { test } = require( '@playwright/test' );
const {
	clickAddNewMenuItem,
	expectBlockProductEditor,
	expectOldProductEditor,
	isBlockProductEditorEnabled,
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );
const { toggleBlockProductTour } = require( '../../../../utils/tours' );

let isNewProductEditorEnabled = false;

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

async function dismissFeedbackModalIfShown( page ) {
	try {
		await page
			.getByRole( 'button', { name: 'Skip' } )
			.click( { timeout: 10000 } );
	} catch ( error ) {}
}

test.describe.serial( 'Disable block product editor', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { request } ) => {
		await toggleBlockProductTour( request, false );
	} );

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
		await page.goto( '/wp-admin/edit.php?post_type=product' );
		await clickAddNewMenuItem( page );
		await expectBlockProductEditor( page );
	} );

	test( 'can be disabled from the header', async ( { page } ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fadd-product'
		);

		try {
			// dismiss feature highlight if shown
			await page
				.getByRole( 'button', { name: 'Close Tour' } )
				.click( { timeout: 5000 } );
		} catch ( e ) {}

		// turn off block product editor from the header
		await page.locator( 'button[aria-label="Options"]' ).click();
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
		await page.goto( '/wp-admin/edit.php?post_type=product' );
		await clickAddNewMenuItem( page );
		await expectOldProductEditor( page );
	} );
} );
