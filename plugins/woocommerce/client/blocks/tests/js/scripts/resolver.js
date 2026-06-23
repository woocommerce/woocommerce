const path = require( 'path' );

const packagesToAugment = [ 'parsel-js' ];

module.exports = ( modulePath, options ) => {
	if ( modulePath === 'uuid' ) {
		const packageJsonPath = options.defaultResolver(
			'uuid/package.json',
			options
		);

		// Resolve to the package selected from the importer, but avoid
		// Jest/jsdom picking ESM browser exports for CommonJS tests.
		return path.join( path.dirname( packageJsonPath ), 'dist/index.js' );
	}

	// Call the defaultResolver, so we leverage its cache, error handling, etc.
	return options.defaultResolver( modulePath, {
		...options,
		// Use packageFilter to process parsed `package.json` before the resolution (see https://www.npmjs.com/package/resolve#resolveid-opts-cb)
		packageFilter: ( pkg ) => {
			// This is a workaround for https://github.com/LeaVerou/parsel/issues/79

			// jest-environment-jsdom 28+ tries to use browser exports instead of default exports,
			// but parsel incorrectly prioritizes the browser export over the node export, causing
			// a Jest error related to trying to parse "export" syntax.
			if ( packagesToAugment.includes( pkg.name ) ) {
				delete pkg.exports;
				delete pkg.module;
			}
			return pkg;
		},
	} );
};
