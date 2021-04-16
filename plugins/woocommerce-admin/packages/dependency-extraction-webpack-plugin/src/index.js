const WPDependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const packages = require( '../assets/packages' );

const WOOCOMMERCE_NAMESPACE = '@woocommerce/';

/**
 * Given a string, returns a new string with dash separators converted to
 * camelCase equivalent. This is not as aggressive as `_.camelCase` in
 * converting to uppercase, where Lodash will also capitalize letters
 * following numbers.
 *
 * @param {string} string Input dash-delimited string.
 *
 * @return {string} Camel-cased string.
 */
function camelCaseDash( string ) {
	return string.replace( /-([a-z])/g, ( _, letter ) => letter.toUpperCase() );
}

const wooRequestToExternal = ( request ) => {
	if ( packages.includes( request ) ) {
		const handle = request.substring( WOOCOMMERCE_NAMESPACE.length );
		const irregularExternalMap = {
			'blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
			settings: [ 'wc', 'wcSettings' ],
		};

		if ( irregularExternalMap[ handle ] ) {
			return irregularExternalMap[ handle ];
		}

		return [ 'wc', camelCaseDash( handle ) ];
	}
};

const wooRequestToHandle = ( request ) => {
	if ( packages.includes( request ) ) {
		const handle = request.substring( WOOCOMMERCE_NAMESPACE.length );
		const irregularHandleMap = {
			data: 'wc-store-data',
			'csv-export': 'wc-csv',
		};

		if ( irregularHandleMap[ handle ] ) {
			return irregularHandleMap[ handle ];
		}

		return 'wc-' + handle;
	}
};

class DependencyExtractionWebpackPlugin extends WPDependencyExtractionWebpackPlugin {
	externalizeWpDeps( _context, request, callback ) {
		let externalRequest;

		// Handle via options.requestToExternal first
		if ( typeof this.options.requestToExternal === 'function' ) {
			externalRequest = this.options.requestToExternal( request );
		}

		// Cascade to default if unhandled and enabled
		if (
			typeof externalRequest === 'undefined' &&
			this.options.useDefaults
		) {
			externalRequest = wooRequestToExternal( request );
		}

		if ( externalRequest ) {
			this.externalizedDeps.add( request );

			return callback( null, externalRequest );
		}

		// Fall back to the WP method
		return super.externalizeWpDeps( _context, request, callback );
	}

	mapRequestToDependency( request ) {
		// Handle via options.requestToHandle first
		if ( typeof this.options.requestToHandle === 'function' ) {
			const scriptDependency = this.options.requestToHandle( request );
			if ( scriptDependency ) {
				return scriptDependency;
			}
		}

		// Cascade to default if enabled
		if ( this.options.useDefaults ) {
			const scriptDependency = wooRequestToHandle( request );
			if ( scriptDependency ) {
				return scriptDependency;
			}
		}

		// Fall back to the WP method
		return super.mapRequestToDependency( request );
	}
}

module.exports = DependencyExtractionWebpackPlugin;
