const {
	ADMINSTATE,
	GITHUB_TOKEN,
	PLUGIN_NAME,
	PLUGIN_REPOSITORY,
	PLUGIN_SLUG,
} = process.env;
const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const {
	deletePlugin,
	deleteZip,
	downloadZip,
	installPluginThruWpCli,
} = require( '../../utils/plugin-utils' );

const skipMessage = 'Skipping this test because PLUGIN_REPOSITORY is undefined';
const deletePluginFromSite = async ( { request, baseURL } ) => {
	await deletePlugin( {
		request,
		baseURL,
		slug: PLUGIN_SLUG,
		username: admin.username,
		password: admin.password,
	} );
};

let pluginPath;

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
		pluginPath =
			await test.step( `Download ${ PLUGIN_NAME } plugin zip`, async () => {
				return downloadZip( {
					repository: PLUGIN_REPOSITORY,
					authorizationToken: GITHUB_TOKEN,
				} );
			} );

		await test.step( "Delete plugin from test site if it's installed.", async () => {
			await deletePluginFromSite( {
				request: playwright.request,
				baseURL,
			} );
		} );
	} );

	test.afterAll( async ( { playwright, baseURL } ) => {
		await test.step( "Delete plugin from test site if it's installed.", async () => {
			await deletePluginFromSite( {
				request: playwright.request,
				baseURL,
			} );
		} );

		await test.step( 'Delete the downloaded zip', async () => {
			await deleteZip( pluginPath );
		} );
	} );

	test( `can upload and activate "${ PLUGIN_NAME }"`, async ( { page } ) => {
		await test.step( `Install "${ PLUGIN_NAME }" through WP CLI`, async () => {
			await installPluginThruWpCli( pluginPath );
		} );

		await test.step( 'Go to the "Installed Plugins" page', async () => {
			await page.goto( 'wp-admin/plugins.php' );
		} );

		await test.step( `Expect "${ PLUGIN_NAME }" to be listed and active.`, async () => {
			await expect(
				page.getByLabel( `Deactivate ${ PLUGIN_NAME }` )
			).toBeVisible();
		} );

		await test.step( 'Expect the shop to load successfully.', async () => {
			const shopHeading = page.getByRole( 'heading', {
				name: 'Shop',
			} );

			await page.goto( '/shop' );
			await expect( shopHeading ).toBeVisible();
		} );

		await test.step( 'Expect the WooCommerce Homepage to load successfully.', async () => {
			const statsOverviewHeading = page.getByText( 'Stats overview' );
			const skipSetupStoreLink = page.getByRole( 'button', {
				name: 'Set up my store',
			} );

			await page.goto( '/wp-admin/admin.php?page=wc-admin' );
			await expect(
				statsOverviewHeading.or( skipSetupStoreLink )
			).toBeVisible();
		} );
	} );
} );
