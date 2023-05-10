const {
	ADMINSTATE,
	GITHUB_TOKEN,
	PLUGIN_NAME,
	PLUGIN_REPOSITORY,
} = process.env;
const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const path = require( 'path' );
const {
	deletePlugin,
	deleteZip,
	downloadZip,
	installPluginThruWpCli,
} = require( '../../utils/plugin-utils' );

const skipMessage = 'Skipping this test because PLUGIN_REPOSITORY is undefined';

let pluginPath;
let pluginSlug;

test.skip( () => {
	const shouldSkip = ! PLUGIN_REPOSITORY;

	if ( shouldSkip ) {
		console.log( skipMessage );
	}

	return shouldSkip;
}, skipMessage );

test.describe( `${ PLUGIN_NAME } plugin can be uploaded and activated`, () => {
	test.use( { storageState: ADMINSTATE } );

	test.beforeAll( async ( { playwright, baseURL } ) => {
		pluginSlug = path.basename( PLUGIN_REPOSITORY );

		// Download plugin.
		pluginPath = await downloadZip( {
			repository: PLUGIN_REPOSITORY,
			authorizationToken: GITHUB_TOKEN,
		} );

		// Delete plugin from test site if it's installed.
		await deletePlugin( {
			request: playwright.request,
			baseURL,
			slug: pluginSlug,
			username: admin.username,
			password: admin.password,
		} );
	} );

	test.afterAll( async ( {} ) => {
		// Delete the downloaded zip.
		await deleteZip( pluginPath );
	} );

	test( `can upload and activate ${ PLUGIN_NAME }`, async ( { page } ) => {
		await installPluginThruWpCli( pluginPath );

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
