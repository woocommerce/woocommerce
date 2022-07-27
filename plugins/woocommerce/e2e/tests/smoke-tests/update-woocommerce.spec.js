const { UPDATE_WC, ADMINSTATE, PLUGIN_PATH } = process.env;
const { test, expect } = require( '@playwright/test' );

test.describe( 'WooCommerce plugin can be uploaded and activated', () => {
	// Skip this test group when the UPDATE_WC environment variable wasn't set, for example in PR smoke tests.
	test.skip(
		! Boolean( UPDATE_WC ),
		'Skipping the WooCommerce plugin upload test.'
	);

	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		// Create a browser context
		const context = await browser.newContext();
		const page = await context.newPage();

		// Navigate to 'Installed Plugins' page
		await page.goto( 'wp-admin/plugins.php', {
			waitUntil: 'networkidle',
		} );

		// Deactivate WooCommerce
		await page
			.locator( '[data-slug="woocommerce"] #deactivate-woocommerce' )
			.click();
		await expect(
			page.locator( '#message', { hasText: 'Plugin deactivated.' } )
		).toBeVisible();

		// Delete WooCommerce
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page
			.locator( '[data-slug="woocommerce"] #delete-woocommerce' )
			.click();

		// Assert that a confirmation message was displayed.
		await expect(
			page.locator( '#woocommerce-deleted', {
				hasText: 'WooCommerce was successfully deleted.',
			} )
		).toBeVisible();

		// Close the created context as a best practice. See https://playwright.dev/docs/api/class-browser#browser-new-context.
		await context.close();
	} );

	test( 'can upload and activate the WooCommerce plugin', async ( {
		page,
	} ) => {
		// Open the plugin install page
		await page.goto( 'wp-admin/plugin-install.php', {
			waitUntil: 'networkidle',
		} );

		// Upload the plugin zip
		await page.click( 'a.upload-view-toggle' );
		await expect( page.locator( 'p.install-help' ) ).toBeVisible();
		await expect( page.locator( 'p.install-help' ) ).toContainText(
			'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
		);
		const [ fileChooser ] = await Promise.all( [
			page.waitForEvent( 'filechooser' ),
			page.click( '#pluginzip' ),
		] );
		await fileChooser.setFiles( PLUGIN_PATH );
		await page.click( '#install-plugin-submit' );
		await page.waitForLoadState( 'networkidle' );

		// Activate the plugin
		await page.click( '.button-primary' );
		await page.waitForLoadState( 'networkidle' );

		// Go to 'Installed plugins' page
		await page.goto( 'wp-admin/plugins.php', {
			waitUntil: 'networkidle',
		} );

		// Assert that 'WooCommerce' is listed and active
		await expect(
			page.locator( '.plugin-title strong', { hasText: /^WooCommerce$/ } )
		).toBeVisible();
		await expect( page.locator( '#deactivate-woocommerce' ) ).toBeVisible();
	} );

	test( 'can run the database update', async ( { page } ) => {
		const updateButton = page.locator( 'text=Update WooCommerce Database' );
		const updateCompleteMessage = page.locator(
			'text=WooCommerce database update complete.'
		);

		// Navigate to 'Installed Plugins' page
		await page.goto( 'wp-admin/plugins.php', {
			waitUntil: 'networkidle',
		} );

		// Skip this test if the "Update WooCommerce Database" button didn't appear.
		test.skip(
			! ( await updateButton.isVisible() ),
			'The "Update WooCommerce Database" button did not appear after updating WooCommerce. Verify with the team if the WooCommerce version being tested does not really trigger a database update.'
		);

		// If the notice appears, start DB update
		await updateButton.click();
		await page.waitForLoadState( 'networkidle' );

		// For 1.5 minutes, repeatedly reload the page every 5 seconds until the "WooCommerce database update complete." message appears.
		for (
			let reloads = 0;
			reloads < 18 && ! ( await updateCompleteMessage.isVisible() );
			reloads++
		) {
			await page.reload( { waitUntil: 'networkidle' } );
			await page.waitForTimeout( 5000 );
		}

		// Fail the test if the message didn't appear.
		test.fail(
			! ( await updateCompleteMessage.isVisible() ),
			'The "WooCommerce database update complete." was not visible after 1 minute.'
		);

		// If the message appeared, click on the "Thanks!" button.
		await page.locator( 'a', { hasText: 'Thanks!' } ).click();

		// Verify that the "WooCommerce database update complete" message is gone.
		await expect( updateCompleteMessage ).not.toBeVisible();
	} );
} );
