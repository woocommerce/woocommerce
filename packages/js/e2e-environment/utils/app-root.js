/**
 * External dependencies
 */
const path = require( 'path' );

const getAppRoot = () => {
	// Figure out where we're installed.
	// Typically will be in node_modules/, but WooCommerce
	// uses a local file path (packages/js/e2e-environment).
	let appPath = false;
	const moduleDir = path.dirname(
		require.resolve( '@woocommerce/e2e-environment' )
	);

	if ( moduleDir.indexOf( 'node_modules' ) > -1 ) {
		appPath = moduleDir.split( 'node_modules' )[ 0 ];
	} else if ( moduleDir.indexOf( 'packages/js/e2e-environment' ) > -1 ) {
		appPath = moduleDir.split( 'packages/js/e2e-environment' )[ 0 ];
	}

	return appPath;
};

module.exports = getAppRoot;
