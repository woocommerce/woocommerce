const getCoreTestsRoot = () => {
	// Figure out where we're installed.
	// Typically will be in node_modules/, but WooCommerce
	// uses a local file path (packages/js/e2e-core-tests).
	let coreTestsPath = false;
	const dirPath = __dirname;

	if ( dirPath.indexOf( 'node_modules' ) > -1 ) {
		coreTestsPath = dirPath.split( 'node_modules' )[ 0 ];
	} else if ( dirPath.indexOf( 'packages/js/e2e-core-tests' ) > -1 ) {
		coreTestsPath = dirPath.split( 'packages/js/e2e-core-tests' )[ 0 ];
	}

	return {
		appRoot: coreTestsPath,
		coreTestsRoot: __dirname,
	};
};

module.exports = getCoreTestsRoot;
