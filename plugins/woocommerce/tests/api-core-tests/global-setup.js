const { ENABLE_HPOS, GITHUB_TOKEN, UPDATE_WC } = process.env;
const { downloadZip, deleteZip } = require( './utils/plugin-utils' );
const axios = require( 'axios' ).default;
const playwrightConfig = require( './playwright.config' );
const { site } = require( './utils' );

/**
 *
 * @param {import('@playwright/test').FullConfig} config
 */
module.exports = async ( config ) => {
	// If API_BASE_URL is configured and doesn't include localhost, running on daily host
	if (
		process.env.API_BASE_URL &&
		! process.env.API_BASE_URL.includes( 'localhost' )
	) {
		const { chromium, expect } = require( '@playwright/test' );

		const { baseURL, userAgent } = config.projects[ 0 ].use;
		const contextOptions = { baseURL, userAgent };

		const browser = await chromium.launch();
		const setupContext = await browser.newContext( contextOptions );
		const setupPage = await setupContext.newPage();

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
			const releaseWithTagName = response.data.find(
				( { tag_name } ) => tag_name === UPDATE_WC
			);
			if ( ! releaseWithTagName ) {
				throw new Error(
					`No release with tag_name="${ UPDATE_WC }" found. If "${ UPDATE_WC }" is a draft release, make sure to specify a GITHUB_TOKEN environment variable.`
				);
			}
			const wcZipAsset = releaseWithTagName.assets.find( ( { name } ) =>
				name.match( /^woocommerce(-trunk-nightly)?\.zip$/ )
			);
			if ( wcZipAsset ) {
				return GITHUB_TOKEN
					? wcZipAsset.url
					: wcZipAsset.browser_download_url;
			}
			throw new Error(
				`WooCommerce release with tag "${ UPDATE_WC }" found, but does not have a WooCommerce ZIP asset.`
			);
		};

		const url = await getWCDownloadURL();
		const params = { url };

		if ( GITHUB_TOKEN ) {
			params.authorizationToken = GITHUB_TOKEN;
		}

		const woocommerceZipPath = await downloadZip( params );

		let adminLoggedIn = false;
		let pluginActive = false;

		console.log( '--------------------------------------' );
		console.log( 'Running daily tests, resetting site...' );
		console.log( '--------------------------------------' );

		const adminRetries = 5;
		for ( let i = 0; i < adminRetries; i++ ) {
			try {
				console.log( 'Trying to log-in as admin...' );
				await setupPage.goto( '/wp-admin' );
				await setupPage
					.locator( 'input[name="log"]' )
					.fill( process.env.USER_KEY );
				await setupPage
					.locator( 'input[name="pwd"]' )
					.fill( process.env.USER_SECRET );
				await setupPage.locator( 'text=Log In' ).click();

				await expect( setupPage.locator( 'div.wrap > h1' ) ).toHaveText(
					'Dashboard'
				);
				console.log( 'Logged-in as admin successfully.' );
				adminLoggedIn = true;
				break;
			} catch ( e ) {
				console.log(
					`Admin log-in failed, Retrying... ${ i }/${ adminRetries }`
				);
				console.log( e );
			}
		}

		if ( ! adminLoggedIn ) {
			console.error(
				'Cannot proceed api test, as admin login failed. Please check if the test site has been setup correctly.'
			);
			process.exit( 1 );
		}

		await setupPage.goto( 'wp-admin/plugins.php' );
		await expect( setupPage.locator( 'div.wrap > h1' ) ).toHaveText(
			'Plugins'
		);

		console.log( 'Deactivating WooCommerce Plugin...' );
		await setupPage.locator( '#deactivate-woocommerce' ).click();
		await expect( setupPage.locator( 'div#message' ) ).toHaveText(
			'Plugin deactivated.Dismiss this notice.'
		);

		console.log( 'Deleting WooCommerce Plugin...' );
		setupPage.on( 'dialog', ( dialog ) => dialog.accept() );
		await setupPage.locator( '#delete-woocommerce' ).click();
		await expect( setupPage.locator( '#woocommerce-deleted' ) ).toHaveText(
			'WooCommerce was successfully deleted.'
		);

		for ( let i = 0; i < adminRetries; i++ ) {
			try {
				console.log( 'Reinstalling WooCommerce Plugin...' );
				await setupPage.goto( 'wp-admin/plugin-install.php' );
				await setupPage.locator( 'a.upload-view-toggle' ).click();
				await expect(
					setupPage.locator( 'p.install-help' )
				).toBeVisible();
				await expect(
					setupPage.locator( 'p.install-help' )
				).toContainText(
					'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
				);
				const [ fileChooser ] = await Promise.all( [
					setupPage.waitForEvent( 'filechooser' ),
					setupPage.locator( '#pluginzip' ).click(),
				] );
				await fileChooser.setFiles( woocommerceZipPath );
				console.log( 'Uploading nightly build...' );
				await setupPage
					.locator( '#install-plugin-submit' )
					.click( { timeout: 60000 } );
				await setupPage.waitForLoadState( 'networkidle', {
					timeout: 60000,
				} );
				await expect(
					setupPage.getByRole(
						'link',
						{ name: 'Activate Plugin' },
						{ timeout: 60000 }
					)
				).toBeVisible();
				console.log( 'Activating Plugin...' );
				await setupPage
					.getByRole( 'link', { name: 'Activate Plugin' } )
					.click( { timeout: 60000 } );
				pluginActive = true;
				break;
			} catch ( e ) {
				console.log(
					`Installing and activating plugin failed, Retrying... ${ i }/${ adminRetries }`
				);
				console.log( e );
			}
		}
		if ( ! pluginActive ) {
			console.error(
				'Cannot proceed api test, as installing WC failed. Please check if the test site has been setup correctly.'
			);
			process.exit( 1 );
		}

		console.log( 'WooCommerce Re-installed.' );
		await expect(
			setupPage.getByRole( 'heading', { name: 'Welcome to Woo!' } )
		).toBeVisible();

		await deleteZip( woocommerceZipPath );

		// Might need to update the database
		await setupPage.goto( 'wp-admin/plugins.php' );
		const updateButton = setupPage.locator(
			'text=Update WooCommerce Database'
		);
		const updateCompleteMessage = setupPage.locator(
			'text=WooCommerce database update complete.'
		);
		await expect( setupPage.locator( 'div.wrap > h1' ) ).toHaveText(
			'Plugins'
		);
		if ( await updateButton.isVisible() ) {
			console.log( 'Database update button present. Click it.' );
			await updateButton.click( { timeout: 60000 } );
			await expect( updateCompleteMessage ).toBeVisible();
		} else {
			console.log( 'No DB update needed' );
		}
	} else {
		// running on localhost using wp-env so ensure HPOS is set if ENABLE_HPOS env variable is passed
		if ( ENABLE_HPOS ) {
			let hposConfigured = false;
			const value = ENABLE_HPOS === '0' ? 'no' : 'yes';
			try {
				const auth = {
					username: playwrightConfig.userKey,
					password: playwrightConfig.userSecret,
				};
				const hposResponse = await axios.post(
					playwrightConfig.use.baseURL +
						'/wp-json/wc/v3/settings/advanced/woocommerce_custom_orders_table_enabled',
					{ value },
					{ auth }
				);
				if ( hposResponse.data.value === value ) {
					console.log(
						`HPOS Switched ${
							value === 'yes' ? 'on' : 'off'
						} successfully`
					);
					hposConfigured = true;
				}
			} catch ( error ) {
				console.log( 'HPOS setup failed.' );
				console.log( error );
				process.exit( 1 );
			}
			if ( ! hposConfigured ) {
				console.error(
					'Cannot proceed to api tests, HPOS configuration failed. Please check if the correct ENABLE_HPOS value was used and the test site has been setup correctly.'
				);
				process.exit( 1 );
			}
		}

		await site.useCartCheckoutShortcodes( config );
	}
};
