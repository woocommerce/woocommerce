/**
 * Internal dependencies
 */
const { merchant, utils } = require( '@woocommerce/e2e-utils' );

const { getRemotePluginZip } = require( '@woocommerce/e2e-environment' );

/**
 * External dependencies
 */
const {
	it,
	beforeAll,
} = require( '@jest/globals' );

const { UPDATE_WC } = process.env;

const nightlyZip = 'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
const pluginName = 'WooCommerce';

let pluginPath;

utils.describeIf( UPDATE_WC )( 'WooCommerce plugin can be uploaded and activated', () => {
	beforeAll( async () => {
		pluginPath = await getRemotePluginZip( nightlyZip );
		await merchant.login();
	});

	afterAll( async () => {
		await merchant.logout();
	});

	it( 'can upload and activate the WooCommerce plugin', async () => {
		await merchant.uploadAndActivatePlugin( pluginPath, pluginName );
	});

});
