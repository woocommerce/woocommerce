const axios = require( 'axios' ).default;
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { ADMINSTATE, GITHUB_TOKEN, UPDATE_WC } = process.env;
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

/**
 *
 * Get download URL of a WooCommerce ZIP asset by sending a `GET` request to `List releases` GitHub API endpoint in the WooCommerce repository.
 *
 * If `GITHUB_TOKEN` is defined, use it as `Authorization` header.
 *
 * @return Download URL of the WooCommerce ZIP. This URL depends on whether `GITHUB_TOKEN` was specified or not.
 *
 * If `GITHUB_TOKEN` was defined, this function assumes that you're trying to access all releases, including drafts (as draft releases don't show up in the response of an unauthenticated GET `List releases` request ).
 * In this case, the returned value will be the `asset.url`.
 *
 * Otherwise, the returned value will be the `asset.browser_download_url`.
 *
 * @throws Error if:
 * - 'List releases' request was unsuccessful, or
 * - no release with the given tag was found, or
 * - when a WooCommerce ZIP asset was not found.
 *
 */
const getWCDownloadURL = async () => {
	const requestConfig = {
		method: 'get',
		url: 'https://api.github.com/repos/woocommerce/woocommerce/releases',
		headers: {
			Accept: 'application/vnd.github+json',
		},
		params: {
			per_page: 100,
		},
	};

	if ( GITHUB_TOKEN ) {
		requestConfig.headers.Authorization = `Bearer ${ GITHUB_TOKEN }`;
	}

	const response = await axios( requestConfig ).catch( ( error ) => {
		if ( error.response ) {
			console.log( error.response.data );
		}

		throw new Error( error.message );
	} );

	const release = response.data.find( ( { tag_name, name } ) =>
		[ tag_name, name ].includes( UPDATE_WC )
	);

	if ( ! release ) {
		throw new Error(
			`Release "${ UPDATE_WC }" not found. If "${ UPDATE_WC }" is a draft release, make sure to specify a GITHUB_TOKEN environment variable.`
		);
	}

	const wcZipAsset = release.assets.find( ( { name } ) =>
		name.match( /^woocommerce(-trunk-nightly)?\.zip$/ )
	);

	if ( wcZipAsset ) {
		return GITHUB_TOKEN ? wcZipAsset.url : wcZipAsset.browser_download_url;
	}

	throw new Error(
		`Release "${ UPDATE_WC }" found, but does not have a WooCommerce ZIP asset.`
	);
};

skipTestIfUndefined();

test.describe.serial( 'WooCommerce update', () => {
	test.use( { storageState: ADMINSTATE } );

	test.setTimeout( 5 * 60 * 1000 );

	test.beforeAll( async () => {
		await test.step( 'Download WooCommerce zip from GitHub', async () => {
			const url = await getWCDownloadURL();
			const params = { url };

			if ( GITHUB_TOKEN ) {
				params.authorizationToken = GITHUB_TOKEN;
			}

			woocommerceZipPath = await downloadZip( params );
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

		await test.step( 'Upload the WooCommerce plugin zip', async () => {
			await page.locator( 'a.upload-view-toggle' ).click();
			await expect( page.locator( 'p.install-help' ) ).toBeVisible();
			await expect( page.locator( 'p.install-help' ) ).toContainText(
				'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
			);
			const [ fileChooser ] = await Promise.all( [
				page.waitForEvent( 'filechooser' ),
				page.locator( '#pluginzip' ).click(),
			] );
			await fileChooser.setFiles( woocommerceZipPath );
			await page.locator( '#install-plugin-submit' ).click();
			await page.waitForLoadState( 'networkidle' );
		} );

		await test.step( 'Choose the option "Replace current with uploaded"', async () => {
			await page
				.locator( '.button-primary.update-from-upload-overwrite' )
				.click();
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

		await test.step( 'Assert that "WooCommerce" is listed and active', async () => {
			await expect(
				page.locator( '.plugin-title strong', {
					hasText: /^WooCommerce$/,
				} )
			).toBeVisible();
			await expect(
				page.locator( '#deactivate-woocommerce' )
			).toBeVisible();
		} );

		if ( UPDATE_WC !== 'nightly' ) {
			await test.step( 'Verify that WooCommerce was updated to the expected version', async () => {
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
					( { plugin } ) => plugin === 'woocommerce/woocommerce.php'
				);

				expect( wcPluginData.version ).toEqual( UPDATE_WC );
			} );
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

		await test.step( 'Skip this test if the "Update WooCommerce Database" button did not appear', async () => {
			test.skip(
				! ( await updateButton.isVisible() ),
				'The "Update WooCommerce Database" button did not appear after updating WooCommerce. Verify with the team if the WooCommerce version being tested does not really trigger a database update.'
			);
		} );

		await test.step( 'Notice appeared. Start DB update', async () => {
			await updateButton.click();
			await page.waitForLoadState( 'networkidle' );
		} );

		await test.step( 'Assert that the notice "WooCommerce database update complete." appears', async () => {
			await expect
				.poll(
					async () => {
						await page.goto( 'wp-admin/plugins.php', {
							waitUntil: 'networkidle',
						} );

						return await updateCompleteMessage.isVisible();
					},
					{
						intervals: [ 10_000 ],
						timeout: 120_000,
					}
				)
				.toEqual( true );
		} );
	} );
} );
