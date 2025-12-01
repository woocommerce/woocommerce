const { test, expect } = require( '@playwright/test' );
const {
	clickAddNewMenuItem,
	expectBlockProductEditor,
	expectOldProductEditor,
} = require( '../../../utils/simple-products' );
const { toggleBlockProductTour } = require( '../../../utils/tours' );
const { tags } = require( '../../../fixtures/fixtures' );
const { ADMIN_STATE_PATH } = require( '../../../playwright.config' );
const { wpCLI } = require( '../../../utils/cli' );
const { skipTestsForDeprecatedFeature } = require( './helpers/skip-tests' );

skipTestsForDeprecatedFeature();

async function dismissFeedbackModalIfShown( page ) {
	try {
		await page
			.getByRole( 'button', { name: 'Skip' } )
			.click( { timeout: 10000 } );
	} catch ( error ) {}
}

test.describe.serial(
	'Disable block product editor',
	{ tag: tags.GUTENBERG },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { request } ) => {
			await toggleBlockProductTour( request, false );
		} );

		test.beforeEach( async () => {
			await wpCLI(
				'wp option set woocommerce_feature_product_block_editor_enabled yes'
			);
		} );

		test.afterAll( async () => {
			await wpCLI(
				'wp option set woocommerce_feature_product_block_editor_enabled no'
			);
		} );

		// expectBlockProductEditor function contains the assertion
		// eslint-disable-next-line playwright/expect-expect
		test( 'is hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( 'wp-admin/edit.php?post_type=product' );
			await clickAddNewMenuItem( page );
			await expectBlockProductEditor( page );
		} );

		// expectOldProductEditor function contains the assertion
		// eslint-disable-next-line playwright/expect-expect
		test( 'can be disabled from the header', async ( { page } ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product'
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
					name: 'Turn off the new product editor',
				} )
				.click();
			await dismissFeedbackModalIfShown( page );
			await expectOldProductEditor( page );
		} );

		// expectOldProductEditor function contains the assertion
		// eslint-disable-next-line playwright/expect-expect
		test( 'can be disabled from settings', async ( { page } ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
			);

			await page
				.locator( '#woocommerce_feature_product_block_editor_enabled' )
				.click();

			await page
				.getByRole( 'button', {
					name: 'Save changes',
				} )
				.click();

			await expect(
				page
					.locator( '#message' )
					.getByText( 'Your settings have been saved' )
			).toBeVisible();
			await page.goto( 'wp-admin/edit.php?post_type=product' );
			await clickAddNewMenuItem( page );
			await expectOldProductEditor( page );
		} );
	}
);
