const { ADMINSTATE, UPDATE_WC, ADMIN_USER, ADMIN_PASSWORD } = process.env;
const { test, expect } = require( '@playwright/test' );
const { deletePlugin } = require( '../../utils/plugin-utils' );

test.describe( 'WooCommerce plugin can be uploaded and activated', () => {
	// Skip test if UPDATE_WC is falsy.
	test.skip(
		! Boolean( UPDATE_WC ),
		`Skipping this test because UPDATE_WC is falsy: ${ UPDATE_WC }`
	);

	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async ( { playwright, baseURL } ) => {
		await deletePlugin( {
			request: playwright.request,
			baseURL,
			slug: 'woocommerce/woocommerce',
			username: ADMIN_USER,
			password: ADMIN_PASSWORD,
		} );
	} );

	// mytodo: unskip
	test.skip( 'can upload and activate the WooCommerce plugin', async ( {
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
		await fileChooser.setFiles( PLUGIN_ZIP_PATH );
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

	// mytodo: unskip
	test.skip( 'can run the database update', async ( { page } ) => {
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
			'The expected message "WooCommerce database update complete." did not appear after waiting for a long while.'
		);

		// If the message appeared, click on the "Thanks!" button.
		await page.locator( 'a', { hasText: 'Thanks!' } ).click();

		// Verify that the "WooCommerce database update complete" message is gone.
		await expect( updateCompleteMessage ).not.toBeVisible();
	} );

	test( 'dummy', () => {
		expect( true ).toEqual( true );
	} );
} );
