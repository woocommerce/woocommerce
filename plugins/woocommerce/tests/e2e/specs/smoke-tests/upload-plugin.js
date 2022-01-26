/**
 * Internal dependencies
 */
const { merchant, utils } = require( '@woocommerce/e2e-utils' );

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

utils.describeIf( GITHUB_REPOSITORY )(
	`Upload and activate ${ PLUGIN_NAME } from ${ GITHUB_REPOSITORY }`,
	() => {
		beforeAll( async () => {
			zipUrl = await getLatestReleaseZipUrl(
				GITHUB_REPOSITORY,
				GITHUB_TOKEN
			);

			pluginPath = await getRemotePluginZip( zipUrl, GITHUB_TOKEN );

			await merchant.login();
		} );

		afterAll( async () => {
			await merchant.logout();
		} );

		it( 'can upload and activate the provided plugin', async () => {
			await merchant.uploadAndActivatePlugin( pluginPath, PLUGIN_NAME );
		} );

		it( 'can remove downloaded plugin zip', async () => {
			await deleteDownloadedPluginFiles();
		} );
	}
);
