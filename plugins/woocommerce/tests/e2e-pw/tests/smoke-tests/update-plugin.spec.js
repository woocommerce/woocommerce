const {
	ADMINSTATE,
	ADMIN_USER,
	ADMIN_PASSWORD,
	GITHUB_TOKEN,
	PLUGIN_NAME,
	PLUGIN_REPOSITORY,
} = process.env;
const { test, expect } = require( '@playwright/test' );
const path = require( 'path' );
const {
	deletePlugin,
	downloadZip,
	deleteZip,
	getLatestReleaseZipUrl,
} = require( '../../utils/plugin-utils' );

const pluginZipPath = path.resolve( __dirname, '../../tmp/woocommerce.zip' );
let pluginSlug;

test.describe( 'Plugin can be uploaded and activated', () => {
	// Skip test if PLUGIN_REPOSITORY is falsy.
	test.skip(
		! PLUGIN_REPOSITORY,
		`Skipping this test because value of PLUGIN_REPOSITORY was falsy: ${ PLUGIN_REPOSITORY }`
	);

	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async () => {
		pluginSlug = PLUGIN_REPOSITORY.split( '/' ).pop();

		// Download the needed plugin.
		const pluginDownloadURL = getLatestReleaseZipUrl( {
			repository: PLUGIN_REPOSITORY,
			authorizationToken: GITHUB_TOKEN,
		} );

		await downloadZip( {
			url: pluginDownloadURL,
			downloadPath: pluginZipPath,
			authToken: GITHUB_TOKEN,
		} );
	} );

	test.afterAll( async () => {
		// mytodo uncomment
		await deleteZip( pluginZipPath );
	} );

	test( `can upload and activate ${ PLUGIN_NAME }`, async ( {
		page,
		playwright,
		baseURL,
	} ) => {
		// Delete the plugin if it's installed.
		await deletePlugin( {
			request: playwright.request,
			baseURL,
			slug: pluginSlug,
			username: ADMIN_USER,
			password: ADMIN_PASSWORD,
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

		// Assert that the plugin is listed and active
		await expect(
			page.locator( '.plugin-title strong', {
				hasText: PLUGIN_NAME,
			} )
		).toBeVisible();
		await expect(
			page.locator( `#deactivate-${ pluginSlug }` )
		).toBeVisible();
	} );
} );
