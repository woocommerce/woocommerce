const { ADMINSTATE, UPDATE_WC } = process.env;
const { admin } = require( '../../test-data/data' );
const { test, expect } = require( '@playwright/test' );
const {
	deletePlugin,
	downloadZip,
	deleteZip,
} = require( '../../utils/plugin-utils' );

const skipMessage = 'Skipping this test because UPDATE_WC is not "true"';

let pluginZipPath;

test.skip( () => {
	const shouldSkip = UPDATE_WC !== 'true';

	if ( shouldSkip ) {
		console.log( skipMessage );
	}

	return shouldSkip;
}, skipMessage );

test.describe.serial(
	'WooCommerce plugin can be uploaded and activated',
	() => {
		test.use( { storageState: ADMINSTATE } );

		test.beforeAll( async () => {
			// Download WooCommerce ZIP
			pluginZipPath = await downloadZip( {
				url:
					'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip',
			} );
		} );

		test.afterAll( async () => {
			// Clean up downloaded zip
			await deleteZip( pluginZipPath );
		} );

		test( 'can upload and activate the WooCommerce plugin', async ( {
			page,
			playwright,
			baseURL,
		} ) => {
			// Delete WooCommerce if it's installed.
			await deletePlugin( {
				request: playwright.request,
				baseURL,
				slug: 'woocommerce',
				username: admin.username,
				password: admin.password,
			} );

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
			await fileChooser.setFiles( pluginZipPath );
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
				page.locator( '.plugin-title strong', {
					hasText: /^WooCommerce$/,
				} )
			).toBeVisible();
			await expect(
				page.locator( '#deactivate-woocommerce' )
			).toBeVisible();
		} );

		test( 'can run the database update', async ( { page } ) => {
			const updateButton = page.locator(
				'text=Update WooCommerce Database'
			);
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

			// Repeatedly reload the Plugins page up to 10 times until the message "WooCommerce database update complete." appears.
			for (
				let reloads = 0;
				reloads < 10 && ! ( await updateCompleteMessage.isVisible() );
				reloads++
			) {
				await page.goto( 'wp-admin/plugins.php', {
					waitUntil: 'networkidle',
				} );

				// Wait 10s before the next reload.
				await page.waitForTimeout( 10000 );
			}

			await expect( updateCompleteMessage ).toBeVisible();
		} );
	}
);
