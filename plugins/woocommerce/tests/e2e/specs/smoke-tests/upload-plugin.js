/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

const { getRemotePluginZip, getLatestReleaseZipUrl, deleteDownloadedPluginFiles } = require( '@woocommerce/e2e-environment' );

/**
 * External dependencies
 */
const {
	it,
	beforeAll,
} = require( '@jest/globals' );

const { GITHUB_REPOSITORY, PLUGIN_NAME, GITHUB_TOKEN } = process.env;

let zipUrl;
let pluginPath;

describe( 'Upload and activate plugin', () => {
	beforeAll( async () => {
		zipUrl = await getLatestReleaseZipUrl( GITHUB_REPOSITORY, GITHUB_TOKEN );

		pluginPath = await getRemotePluginZip( zipUrl, GITHUB_TOKEN );

		await merchant.login();
	});

	afterAll( async () => {
		await merchant.logout();
		await deleteDownloadedPluginFiles();
	});

	it( 'can upload and activate the provided plugin', async () => {
		await merchant.uploadAndActivatePlugin( pluginPath, PLUGIN_NAME );
	});

	it( 'can remove downloaded plugin zip', async () => {
		await deleteDownloadedPluginFiles();
	} );

});
