/**
 * Internal dependencies
 */
const { merchant, utils } = require( '@woocommerce/e2e-utils' );

const {
	getRemotePluginZip,
	getLatestReleaseZipUrl,
	deleteDownloadedPluginFiles,
} = require( '@woocommerce/e2e-environment' );

/**
 * External dependencies
 */
const { it, beforeAll } = require( '@jest/globals' );

const { GITHUB_REPOSITORY, PLUGIN_NAME, GITHUB_TOKEN, PLUGIN_REPOSITORY } =
	process.env;

// allows us to upload plugins from different repositories.
const pluginName = PLUGIN_NAME ? PLUGIN_NAME : 'WooCommerce';
const repository = PLUGIN_REPOSITORY ? PLUGIN_REPOSITORY : GITHUB_REPOSITORY;

let zipUrl;
let pluginPath;

utils.describeIf( repository )(
	`Upload and activate ${ pluginName } from ${ repository }`,
	() => {
		beforeAll( async () => {
			zipUrl = await getLatestReleaseZipUrl( repository, GITHUB_TOKEN );

			pluginPath = await getRemotePluginZip( zipUrl, GITHUB_TOKEN );

			await merchant.login();
		} );

		afterAll( async () => {
			await merchant.logout();
		} );

		/* eslint-disable jest/expect-expect */
		it( 'can upload and activate the provided plugin', async () => {
			await merchant.uploadAndActivatePlugin( pluginPath, PLUGIN_NAME );
		} );

		/* eslint-disable jest/expect-expect */
		it( 'can remove downloaded plugin zip', async () => {
			await deleteDownloadedPluginFiles();
		} );
	}
);
