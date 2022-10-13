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
	createPlugin,
	deletePlugin,
	downloadZip,
	deleteZip,
	getLatestReleaseZipUrl,
} = require( '../../utils/plugin-utils' );

const adminUsername = ADMIN_USER ?? 'admin';
const adminPassword = ADMIN_PASSWORD ?? 'password';

let pluginZipPath;
let pluginSlug;

test.describe( `${ PLUGIN_NAME } plugin can be uploaded and activated`, () => {
	// Skip test if PLUGIN_REPOSITORY is falsy.
	test.skip(
		! PLUGIN_REPOSITORY,
		`Skipping this test because value of PLUGIN_REPOSITORY was falsy: ${ PLUGIN_REPOSITORY }`
	);

	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async () => {
		pluginSlug = PLUGIN_REPOSITORY.split( '/' ).pop();

		// Get the download URL and filename of the plugin
		const pluginDownloadURL = await getLatestReleaseZipUrl( {
			repository: PLUGIN_REPOSITORY,
			authorizationToken: GITHUB_TOKEN,
		} );
		const zipFilename = pluginDownloadURL.split( '/' ).pop();
		pluginZipPath = path.resolve( __dirname, `../../tmp/${ zipFilename }` );

		// Download the needed plugin.
		await downloadZip( {
			url: pluginDownloadURL,
			downloadPath: pluginZipPath,
			authToken: GITHUB_TOKEN,
		} );
	} );

	test.afterAll( async ( { baseURL, playwright } ) => {
		// Delete the downloaded zip.
		await deleteZip( pluginZipPath );

		// Delete the plugin from the test site.
		await deletePlugin( {
			request: playwright.request,
			baseURL,
			slug: pluginSlug,
			username: adminUsername,
			password: adminPassword,
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
			username: adminUsername,
			password: adminPassword,
		} );

		// Install and activate plugin
		await createPlugin( {
			request: playwright.request,
			baseURL,
			slug: pluginSlug.split( '/' ).pop(),
			username: adminUsername,
			password: adminPassword,
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
