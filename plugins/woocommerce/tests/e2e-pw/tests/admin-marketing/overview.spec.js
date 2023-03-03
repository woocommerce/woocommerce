const { test, expect } = require( '@playwright/test' );

test.describe( 'Marketing page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'A user can disable the Multichannel Marketing feature in WC Settings and view the Marketing > Overview page without it crashing', async ( {
		page,
	} ) => {
		// Go to WC Settings > Advanced > Features page.
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);

		// Disable multichannel marketing experience by unchecking the checkbox and clicking on "Save changes" button.
		await page
			.locator(
				'"Enables the new WooCommerce Multichannel Marketing experience in the Marketing page"'
			)
			.uncheck();
		await page.locator( '"Save changes"' ).click();

		// Go to the Marketing page.
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );

		// Users should see the knowledge base card.
		await expect(
			page.locator( '"WooCommerce knowledge base"' )
		).toBeVisible();
	} );

	test( 'A user can enable the Multichannel Marketing feature in WC Settings and view the Marketing > Overview page without it crashing', async ( {
		page,
	} ) => {
		// Go to WC Settings > Advanced > Features page.
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);

		// Enable multichannel marketing experience by checking the checkbox and clicking on "Save changes" button.
		await page
			.locator(
				'"Enables the new WooCommerce Multichannel Marketing experience in the Marketing page"'
			)
			.check();
		await page.locator( '"Save changes"' ).click();

		// Go to the Marketing page.
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );

		// Users should see the "Learn about marketing a store" card.
		await expect(
			page.locator( '"Learn about marketing a store"' )
		).toBeVisible();
	} );
} );
