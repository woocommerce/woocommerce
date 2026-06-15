const requestToExternal = ( request ) => {
	switch ( request ) {
		case 'moment-timezone':
			// Use WordPress core's window.moment (which includes moment-timezone)
			// instead of bundling a stripped copy.
			return 'moment';
		case 'react/jsx-runtime':
		case 'react/jsx-dev-runtime':
			// @wordpress/dependency-extraction-webpack-plugin version bump related, which added 'react-jsx-runtime' dependency.
			// See https://github.com/WordPress/gutenberg/pull/61692 for more details about the dependency in general.
			// For backward compatibility reasons we need to skip requesting to external here.
			return null;
		case 'react-dom/client':
			// React 18 split createRoot/hydrateRoot into react-dom/client.
			// WordPress's wp-react-dom UMD aggregates both entrypoints onto the
			// same window.ReactDOM global.
			return 'ReactDOM';
		case '@wordpress/dataviews':
		case '@wordpress/dataviews/wp':
			return [ 'wp', 'dataviews' ];
		case '@wordpress/global-styles-engine':
			// @wordpress/global-styles-engine is not a standard WordPress package available globally,
			// so we need to bundle it instead of treating it as an external.
			return null;
	}

	if ( request.startsWith( '@wordpress/theme' ) ) {
		return null;
	}

	if ( request.startsWith( '@wordpress/ui' ) ) {
		return null;
	}

	// Skip requesting to external if the import path is from the build or build-module directory for WordPress packages.
	// This is required for @wordpress/edit-site to work and also can reduce the bundle size when we don't need to load the entire WordPress package.
	if ( request.match( /^@wordpress\/.*\/build(?:-module)?/ ) ) {
		return null;
	}

	// Skip requesting to external if the import path is from the build or build-module directory for WooCommerce packages.
	// This can reduce the bundle size when we don't need to load the entire WooCommerce package.
	if ( request.match( /^@woocommerce\/.*\/build(?:-module)?/ ) ) {
		return null;
	}
};

const requestToHandle = ( request ) => {
	if ( request === 'moment-timezone' ) {
		return 'moment';
	}

	if ( request === 'react-dom/client' ) {
		return 'react-dom';
	}

	if (
		request === '@wordpress/dataviews' ||
		request === '@wordpress/dataviews/wp'
	) {
		return 'wp-dataviews';
	}
};

module.exports = {
	requestToExternal,
	requestToHandle,
};
