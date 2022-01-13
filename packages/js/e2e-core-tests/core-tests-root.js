/**
 * External dependencies
 */
const path = require( 'path' );

const getCoreTestsRoot = () => {
	// Figure out where we're installed.
	// Typically will be in node_modules/, but WooCommerce
	// uses a local file path (packages/js/e2e-core-tests).
	let coreTestsPath = false;
	const moduleDir = path.dirname(
		require.resolve( '@woocommerce/e2e-core-tests' )
	);

	if ( moduleDir.indexOf( 'node_modules' ) > -1 ) {
		coreTestsPath = moduleDir.split( 'node_modules' )[ 0 ];
	} else if ( moduleDir.indexOf( 'packages/js/e2e-core-tests' ) > -1 ) {
		coreTestsPath = moduleDir.split( 'packages/js/e2e-core-tests' )[ 0 ];
	}

	return {
		appRoot: coreTestsPath,
		packageRoot: moduleDir,
		coreTestsRoot: __dirname
	};
};

module.exports = getCoreTestsRoot;
