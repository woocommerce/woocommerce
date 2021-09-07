/**
 * Internal dependencies
 */
const { merchant, utils } = require( '@woocommerce/e2e-utils' );

const { getRemotePluginZip, getLatestReleaseZipUrl } = require( '@woocommerce/e2e-environment' );

/**
 * External dependencies
 */
const {
	it,
	beforeAll,
} = require( '@jest/globals' );

const { UPDATE_WC, TEST_RELEASE } = process.env;

let zipUrl;
const pluginName = 'WooCommerce';

let pluginPath;

utils.describeIf( UPDATE_WC )( 'WooCommerce plugin can be uploaded and activated', () => {
	beforeAll( async () => {

		if ( TEST_RELEASE ) {
			zipUrl = await getLatestReleaseZipUrl( 'woocommerce', 'woocommerce' );
		} else {
			zipUrl = 'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
		}

		pluginPath = await getRemotePluginZip( zipUrl );
		await merchant.login();
	});

	afterAll( async () => {
		await merchant.logout();
	});

	it( 'can upload and activate the WooCommerce plugin', async () => {
		await merchant.uploadAndActivatePlugin( pluginPath, pluginName );
	});

	it( 'can run the database update', async () => {
		// Check for, and run, the database upgrade if needed
		await merchant.runDatabaseUpdate();
	});

});
