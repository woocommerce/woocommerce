const axios = require( 'axios' ).default;
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { ADMINSTATE, UPDATE_WC } = process.env;
const { downloadZip, deleteZip } = require( '../../utils/plugin-utils' );
const { test, expect } = require( '@playwright/test' );

let woocommerceZipPath;

const skipTestIfUndefined = () => {
	const skipMessage = `Skipping this test because UPDATE_WC was undefined.\nTo run this test, set UPDATE_WC to a valid WooCommerce release tag that has a WooCommerce ZIP, like "nightly" or "7.1.0-beta.2".`;

	test.skip( () => {
		const shouldSkip = UPDATE_WC === undefined;

		if ( shouldSkip ) {
			console.log( skipMessage );
		}

		return shouldSkip;
	}, skipMessage );
};

const getWCDownloadURL = async () => {
	let woocommerceZipAsset;

	const response = await axios
		.get(
			`https://api.github.com/repos/woocommerce/woocommerce/releases/tags/${ UPDATE_WC }`
		)
		.catch( ( error ) => {
			console.log( error.toJSON() );
			return error.response;
		} );

	if (
		response.status === 200 &&
		response.data.assets &&
		response.data.assets.length > 0 &&
		( woocommerceZipAsset = response.data.assets.find(
			( { browser_download_url } ) =>
				browser_download_url.match(
					/woocommerce(-trunk-nightly)?\.zip$/
				)
		) )
	) {
		return woocommerceZipAsset.browser_download_url;
	} else {
		const error = `You're attempting to get the download URL of a WooCommerce release zip with tag "${ UPDATE_WC }". But "${ UPDATE_WC }" is an invalid WooCommerce release tag, or a tag without a WooCommerce release zip asset like "7.2.0-rc.2".`;
		throw new Error( error );
	}
};

skipTestIfUndefined();

test.describe.serial( 'WooCommerce update', () => {
	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async () => {
		await test.step( 'Download WooCommerce zip from GitHub', async () => {
			const url = await getWCDownloadURL();
			woocommerceZipPath = await downloadZip( { url } );
		} );
	} );

	test.afterAll( async () => {
		await test.step( 'Clean up downloaded WooCommerce zip', async () => {
			await deleteZip( woocommerceZipPath );
		} );
	} );

	test( `can update WooCommerce to "${ UPDATE_WC }"`, async ( {
		page,
		baseURL,
	} ) => {
		await test.step( 'Open the plugin install page', async () => {
			await page.goto( 'wp-admin/plugin-install.php', {
				waitUntil: 'networkidle',
			} );
		} );

		await test.step( 'Upload the plugin zip', async () => {
			await page.click( 'a.upload-view-toggle' );
			await expect( page.locator( 'p.install-help' ) ).toBeVisible();
			await expect( page.locator( 'p.install-help' ) ).toContainText(
				'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
			);
			const [ fileChooser ] = await Promise.all( [
				page.waitForEvent( 'filechooser' ),
				page.click( '#pluginzip' ),
			] );
			await fileChooser.setFiles( woocommerceZipPath );
			await page.click( '#install-plugin-submit' );
			await page.waitForLoadState( 'networkidle' );
		} );

		await test.step( 'Replace current with uploaded', async () => {
			await page.click( '.button-primary.update-from-upload-overwrite' );
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page.getByText( 'Plugin updated successfully.' )
			).toBeVisible();
		} );

		await test.step( 'Go to "Installed plugins" page', async () => {
			await page.goto( 'wp-admin/plugins.php', {
				waitUntil: 'networkidle',
			} );
		} );

		await test.step(
			'Assert that "WooCommerce" is listed and active',
			async () => {
				await expect(
					page.locator( '.plugin-title strong', {
						hasText: /^WooCommerce$/,
					} )
				).toBeVisible();
				await expect(
					page.locator( '#deactivate-woocommerce' )
				).toBeVisible();
			}
		);

		if ( UPDATE_WC !== 'nightly' ) {
			await test.step(
				'Verify that WooCommerce was updated to the expected version',
				async () => {
					const api = new wcApi( {
						url: baseURL,
						consumerKey: process.env.CONSUMER_KEY,
						consumerSecret: process.env.CONSUMER_SECRET,
						version: 'wc/v3',
					} );

					const response = await api
						.get( 'system_status' )
						.catch( ( error ) => {
							throw new Error( error.message );
						} );

					const wcPluginData = response.data.active_plugins.find(
						( { plugin } ) =>
							plugin === 'woocommerce/woocommerce.php'
					);

					expect( wcPluginData.version ).toEqual( UPDATE_WC );
				}
			);
		}
	} );

	test( 'can run the database update', async ( { page } ) => {
		const updateButton = page.locator( 'text=Update WooCommerce Database' );
		const updateCompleteMessage = page.locator(
			'text=WooCommerce database update complete.'
		);

		await test.step( "Navigate to 'Installed Plugins' page", async () => {
			await page.goto( 'wp-admin/plugins.php', {
				waitUntil: 'networkidle',
			} );
		} );

		await test.step(
			'Skip this test if the "Update WooCommerce Database" button did not appear',
			async () => {
				test.skip(
					! ( await updateButton.isVisible() ),
					'The "Update WooCommerce Database" button did not appear after updating WooCommerce. Verify with the team if the WooCommerce version being tested does not really trigger a database update.'
				);
			}
		);

		await test.step( 'Notice appeared. Start DB update', async () => {
			await updateButton.click();
			await page.waitForLoadState( 'networkidle' );
		} );

		await test.step(
			'Repeatedly reload the Plugins page up to 10 times until the message "WooCommerce database update complete." appears',
			async () => {
				for (
					let reloads = 0;
					reloads < 10 &&
					! ( await updateCompleteMessage.isVisible() );
					reloads++
				) {
					await page.goto( 'wp-admin/plugins.php', {
						waitUntil: 'networkidle',
					} );

					// Wait 10s before the next reload.
					await page.waitForTimeout( 10000 );
				}

				await expect( updateCompleteMessage ).toBeVisible();
			}
		);
	} );
} );
