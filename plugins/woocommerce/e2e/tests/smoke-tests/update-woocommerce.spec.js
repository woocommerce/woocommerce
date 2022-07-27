const { UPDATE_WC, ADMINSTATE, PLUGIN_PATH } = process.env;
const { test, expect } = require( '@playwright/test' );

test.describe( 'WooCommerce plugin can be uploaded and activated', () => {
	test.skip(
		() => ! Boolean( UPDATE_WC ),
		'Skipping the WooCommerce plugin upload test.'
	);

	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async () => {
		// mytodo delete current WooCommerce plugin
	} );

	test( 'can upload and activate the WooCommerce plugin', async ( {
		page,
	} ) => {
		// mytodo move this later to beforeAll hook
		// Delete current WooCommerce plugin
		// Navigate to 'Installed Plugins' page
		await page.goto( 'wp-admin/plugins.php', {
			waitUntil: 'networkidle',
		} );

		// Deactivate WooCommerce
		await page
			.locator( '[data-slug="woocommerce"] #deactivate-woocommerce' )
			.click();
		await page.waitForLoadState( 'networkidle' );

		// Delete WooCommerce
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page
			.locator( '[data-slug="woocommerce"] #delete-woocommerce' )
			.click();
		await page.waitForLoadState( 'networkidle' );

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
		expect( page.locator( '.plugin-title strong' ) ).toHaveText(
			/^WooCommerce$/
		);
		expect( page.locator( '#deactivate-woocommerce' ) ).toBeVisible();
	} );
} );
