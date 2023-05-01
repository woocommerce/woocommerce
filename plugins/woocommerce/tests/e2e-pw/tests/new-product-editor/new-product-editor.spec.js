const { test, expect } = require( '@playwright/test' );

const ALL_PRODUCTS_URL = 'wp-admin/edit.php?post_type=product';
const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin//admin.php?page=wc-admin&path=%2Fadd-product';

const isNewProductEditorSupposedToBeEnabled = !! process.env
	.ENABLE_NEW_PRODUCT_EDITOR;
const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

async function clickAddNewMenuItem( page ) {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

async function dismissGuideIfShown( page ) {
	try {
		await page
			.getByRole( 'button', {
				name: "I'll explore on my own",
			} )
			.click( { timeout: 5000 } );
	} catch ( error ) {}
}

async function dismissFeedbackModalIfShown( page ) {
	if ( ! isTrackingSupposedToBeEnabled ) {
		// no modal should be shown, so don't even look for button
		return;
	}

	try {
		await page
			.getByRole( 'button', { name: 'Skip' } )
			.click( { timeout: 5000 } );
	} catch ( error ) {}
}

async function expectOldProductEditor( page ) {
	await expect(
		page.locator( '#woocommerce-product-data h2' )
	).toContainText( 'Product data' );
}

async function expectNewProductEditor( page ) {
	await expect(
		page.locator( '.woocommerce-product-title__wrapper' )
	).toContainText( 'New product' );
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

		test.skip(
			! isNewProductEditorSupposedToBeEnabled,
			'The new product editor is not being tested'
		);

		test( 'is feature flag enabled', async ( { page } ) => {
			// we have to go to a WCAdmin page to get the wcAdminFeatures global
			await page.goto( ALL_PRODUCTS_URL );

			const wcAdminFeatures = await page.evaluate(
				'window.wcAdminFeatures'
			);

			expect(
				!! wcAdminFeatures[ 'new-product-management-experience' ]
			).toBeTruthy();
		} );

		test( 'is hooked up to sidebar "Add New"', async ( { page } ) => {
			await page.goto( ALL_PRODUCTS_URL );
			await clickAddNewMenuItem( page );
			await expectNewProductEditor( page );
		} );

		test( 'can be disabled from the header', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await dismissGuideIfShown( page );

			// turn off new product editor from the header
			await page
				.getByRole( 'button', { name: 'More product options' } )
				.click();
			await page
				.getByRole( 'menuitem', { name: 'Use the classic editor' } )
				.click();

			await dismissFeedbackModalIfShown( page );

			await expectOldProductEditor( page );
		} );

		test( 'can be disabled from the feedback footer', async ( {
			page,
		} ) => {
			test.skip(
				! isTrackingSupposedToBeEnabled,
				'Tracking is not enabled'
			);

			// ideally we would have a way to reset from the test whether
			// the CES feedback modal was shown so we could test this;
			// currently, this will always be skipped if the disabling
			// from the header test succeeds

			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await dismissGuideIfShown( page );

			let footerShown = true;

			// turn off new product editor from the feedback footer, if shown
			try {
				await page
					.getByRole( 'button', { name: 'Turn it off' } )
					.click( { timeout: 5000 } );
			} catch ( error ) {
				footerShown = false;
			}

			test.skip( ! footerShown, 'Feedback footer was not shown' );

			await dismissFeedbackModalIfShown( page );

			await expectOldProductEditor( page );
		} );
	} );
} );
