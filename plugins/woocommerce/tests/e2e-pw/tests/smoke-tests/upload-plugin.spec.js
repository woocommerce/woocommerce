const {
	ADMINSTATE,
	GITHUB_TOKEN,
	PLUGIN_NAME,
	PLUGIN_REPOSITORY,
} = process.env;
const { test, expect } = require( '@playwright/test' );
const { admin, getTextForLanguage } = require( '../../test-data/data' );
const path = require( 'path' );
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
		slug: pluginSlug,
		username: admin.username,
		password: admin.password,
	} );
};

let pluginSlug, pluginPath;

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

		pluginPath = await test.step(
			`Download ${ PLUGIN_NAME } plugin zip`,
			async () => {
				return downloadZip( {
					repository: PLUGIN_REPOSITORY,
					authorizationToken: GITHUB_TOKEN,
				} );
			}
		);

		await test.step(
			"Delete plugin from test site if it's installed.",
			async () => {
				await deletePluginFromSite( {
					request: playwright.request,
					baseURL,
				} );
			}
		);
	} );

	test.afterAll( async ( { playwright, baseURL } ) => {
		await test.step(
			"Delete plugin from test site if it's installed.",
			async () => {
				await deletePluginFromSite( {
					request: playwright.request,
					baseURL,
				} );
			}
		);

		await test.step( 'Delete the downloaded zip', async () => {
			await deleteZip( pluginPath );
		} );
	} );

	test( `can upload and activate "${ PLUGIN_NAME }"`, async ( { page } ) => {
		await test.step(
			`Install "${ PLUGIN_NAME }" through WP CLI`,
			async () => {
				await installPluginThruWpCli( pluginPath );
			}
		);

		await test.step( 'Go to the "Installed Plugins" page', async () => {
			await page.goto( 'wp-admin/plugins.php' );
		} );

		await test.step(
			`Expect "${ PLUGIN_NAME }" to be listed and active.`,
			async () => {
				await expect(
					page.locator( `#deactivate-${ pluginSlug }` )
				).toBeVisible();
			}
		);

		await test.step( 'Expect the shop to load successfully.', async () => {
			const shopHeading = page.getByRole( 'heading', {
				name: 'Shop',
			} );

			await page.goto( '/shop' );
			await expect( shopHeading ).toBeVisible();
		} );

		await test.step(
			'Expect the WooCommerce Homepage to load successfully.',
			async () => {
				const statsOverviewHeading = page.getByText( getTextForLanguage()['Statsoverview'] );
				const skipSetupStoreLink = page.getByRole( 'button', {
					name: getTextForLanguage()['Skipsetupstoredetails'],
				} );

				await page.goto( '/wp-admin/admin.php?page=wc-admin' );
				await expect(
					statsOverviewHeading.or( skipSetupStoreLink )
				).toBeVisible();
			}
		);
	} );
} );
