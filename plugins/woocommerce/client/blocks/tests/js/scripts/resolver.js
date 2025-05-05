const packagesToAugment = [ 'uuid', 'parsel-js' ];

module.exports = ( path, options ) => {
	// Call the defaultResolver, so we leverage its cache, error handling, etc.
	return options.defaultResolver( path, {
		...options,
		// Use packageFilter to process parsed `package.json` before the resolution (see https://www.npmjs.com/package/resolve#resolveid-opts-cb)
		packageFilter: ( pkg ) => {
			// This is a workaround for https://github.com/uuidjs/uuid/pull/616 and https://github.com/LeaVerou/parsel/issues/79

			// jest-environment-jsdom 28+ tries to use browser exports instead of default exports,
			// but uuid only offers an ESM browser export and not a CommonJS one. Parsel incorrectly
			// prioritizes the browser export over the node export, causing a Jest error related to
			// trying to parse "export" syntax.
			if ( packagesToAugment.includes( pkg.name ) ) {
				delete pkg.exports;
				delete pkg.module;
			}
			return pkg;
		},
	} );
};
