const { ADMINSTATE, WOOCOMMERCE_ZIP_PATH } = process.env;
const { test, expect } = require( '@playwright/test' );
const { deleteZip } = require( '../../utils/plugin-utils' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

let initWooCommerceVersion;

const skipMessage =
	'Skipping this test because WOOCOMMERCE_ZIP_PATH is undefined';

test.skip( () => {
	const shouldSkip = WOOCOMMERCE_ZIP_PATH === undefined;

	if ( shouldSkip ) {
		console.log( skipMessage );
	}

	return shouldSkip;
}, skipMessage );

test.describe.serial(
	'WooCommerce plugin can be uploaded and activated',
	() => {
		test.use( { storageState: ADMINSTATE } );

		test.beforeAll( async ( { baseURL } ) => {
			// Get initial WooCommerce version
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			const response = await api
				.get( 'system_status' )
				.catch( ( error ) => {
					throw new Error(
						`${ error.response.status } ${
							error.response.statusText
						}\n${ JSON.stringify( error.response.data, null, 2 ) }`
					);
				} );

			initWooCommerceVersion = +response.data.environment.version;
		} );

		test.afterAll( async () => {
			// Clean up downloaded zip
			await deleteZip( WOOCOMMERCE_ZIP_PATH );
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
			await fileChooser.setFiles( WOOCOMMERCE_ZIP_PATH );
			await page.click( '#install-plugin-submit' );
			await page.waitForLoadState( 'networkidle' );

			// Replace current with uploaded
			await page.click( '.button-primary.update-from-upload-overwrite' );
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page.getByText( 'Plugin updated successfully.' )
			).toBeVisible();

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
