const getAppRoot = () => {
	// Figure out where we're installed.
	// Typically will be in node_modules/, but WooCommerce
	// uses a local file path (packages/js/e2e-environment).
	let appPath = false;
	const dirPath = __dirname;

	if ( dirPath.indexOf( 'node_modules' ) > -1 ) {
		appPath = dirPath.split( 'node_modules' )[ 0 ];
	} else if ( dirPath.indexOf( 'packages/js/e2e-environment' ) > -1 ) {
		appPath = dirPath.split( 'packages/js/e2e-environment' )[ 0 ];
	}

	return appPath;
};

module.exports = getAppRoot;
