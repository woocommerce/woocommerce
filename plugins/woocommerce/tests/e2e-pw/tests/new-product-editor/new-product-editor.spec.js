const { test, expect } = require( '@playwright/test' );

const EDIT_PRODUCT_URL = 'wp-admin/edit.php?post_type=product';
const NEW_PRODUCT_URL = 'wp-admin/post-new.php?post_type=product';

const isNewProductEditorSupposedToBeEnabled = !! process.env
	.ENABLE_NEW_PRODUCT_EDITOR;

async function clickAddNewMenuItem( page ) {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

test.describe( 'New product editor', () => {
	test.describe( 'Default (disabled)', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.skip(
			isNewProductEditorSupposedToBeEnabled,
			'The new product editor is being tested'
		);

		test( 'is feature flag disabled', async ( { page } ) => {
			// we have to go to a WCAdmin page to get the wcAdminFeatures global
			await page.goto( EDIT_PRODUCT_URL );

			const wcAdminFeatures = await page.evaluate(
				'window.wcAdminFeatures'
			);

			expect(
				!! wcAdminFeatures[ 'new-product-management-experience' ]
			).toBeFalsy();
		} );

		test( 'is not hooked up to sidebar "Add New" when disabled', async ( {
			page,
		} ) => {
			await page.goto( EDIT_PRODUCT_URL );

			await clickAddNewMenuItem( page );

			// make sure the old product editor is shown
			await expect(
				page.locator( '#woocommerce-product-data h2' )
			).toContainText( 'Product data' );
		} );

		/*
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
	*/
	} );

	test.describe( 'Enabled', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.skip(
			! isNewProductEditorSupposedToBeEnabled,
			'The new product editor is not being tested'
		);

		test( 'is feature flag enabled', async ( { page } ) => {
			// we have to go to a WCAdmin page to get the wcAdminFeatures global
			await page.goto( EDIT_PRODUCT_URL );

			const wcAdminFeatures = await page.evaluate(
				'window.wcAdminFeatures'
			);

			expect(
				!! wcAdminFeatures[ 'new-product-management-experience' ]
			).toBeTruthy();
		} );

		test( 'is hooked up to sidebar "Add New" when enabled', async ( {
			page,
		} ) => {
			await page.goto( EDIT_PRODUCT_URL );

			await clickAddNewMenuItem( page );

			// make sure the new product editor is shown
			await expect(
				page.locator( '.woocommerce-product-title__wrapper' )
			).toContainText( 'New product' );
		} );
	} );
} );
