const {
	ADMINSTATE,
	GITHUB_TOKEN,
	PLUGIN_NAME,
	PLUGIN_PATH,
	PLUGIN_REPOSITORY,
} = process.env;
const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const path = require( 'path' );
const {
	createPlugin,
	deletePlugin,
	downloadZip,
	deleteZip,
	getLatestReleaseZipUrl,
} = require( '../../utils/plugin-utils' );

let pluginSlug;
let pluginZipPath;

test.describe( `${ PLUGIN_NAME } plugin can be uploaded and activated`, () => {
	test.skip(
		! ( PLUGIN_PATH || PLUGIN_REPOSITORY ),
		'Skipped: Either PLUGIN_PATH or PLUGIN_REPOSITORY should be defined. PLUGIN_PATH takes precedence over PLUGIN_REPOSITORY.'
	);

	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async () => {
		// If PLUGIN_PATH was specified, use it to set up the test, ignoring PLUGIN_REPOSITORY.
		if ( PLUGIN_PATH ) {
			const extname = path.extname( PLUGIN_PATH );
			pluginSlug = path.basename( PLUGIN_PATH, extname );
			pluginZipPath = PLUGIN_PATH;

			return;
		}

		// Set up the test using PLUGIN_REPOSITORY only when PLUGIN_PATH was not defined.
		if ( PLUGIN_REPOSITORY ) {
			pluginSlug = path.basename( PLUGIN_REPOSITORY );

			// Get the download URL and filename of the plugin
			const pluginDownloadURL = await getLatestReleaseZipUrl( {
				repository: PLUGIN_REPOSITORY,
				authorizationToken: GITHUB_TOKEN,
			} );
			const zipFilename = path.basename( pluginDownloadURL );
			pluginZipPath = path.resolve(
				__dirname,
				`../../tmp/${ zipFilename }`
			);

			// Download the plugin.
			await downloadZip( {
				url: pluginDownloadURL,
				downloadPath: pluginZipPath,
				authToken: GITHUB_TOKEN,
			} );
		}
	} );

	test.afterAll( async ( { baseURL, playwright } ) => {
		// Delete the downloaded zip.
		await deleteZip( pluginZipPath );

		// Delete the plugin from the test site.
		await deletePlugin( {
			request: playwright.request,
			baseURL,
			slug: pluginSlug,
			username: admin.username,
			password: admin.password,
		} );
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
			username: admin.username,
			password: admin.password,
		} );

		// Install and activate plugin
		await createPlugin( {
			request: playwright.request,
			baseURL,
			slug: pluginSlug,
			username: admin.username,
			password: admin.password,
		} );

		// Go to 'Installed plugins' page.
		// Repeat in case the newly installed plugin redirects to their own onboarding screen upon first install, like what Yoast SEO does.
		let reload = 2;
		do {
			await page.goto( 'wp-admin/plugins.php', {
				waitUntil: 'networkidle',
			} );
		} while ( ! page.url().includes( '/plugins.php' ) && --reload );

		// Assert that the plugin is listed and active
		await expect(
			page.locator( `#deactivate-${ pluginSlug }` )
		).toBeVisible();
	} );
} );
