/**
 * Internal dependencies
 */
 const { merchant } = require( '@woocommerce/e2e-utils' );

 const { getRemotePluginZip } = require( '@woocommerce/e2e-environment' );

 /**
  * External dependencies
  */
 const {
	  it,
	  describe,
	  beforeAll,
  } = require( '@jest/globals' );


 const nightlyZip = 'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
 const pluginName = 'WooCommerce';

 let pluginPath;

 describe( 'WooCommerce plugin can be uploaded and activated', () => {
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
