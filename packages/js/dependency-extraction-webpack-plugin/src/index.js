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

const wooRequestToExternal = ( request, excludedExternals ) => {
	if ( packages.includes( request ) ) {
		if ( ( excludedExternals || [] ).includes( request ) ) {
			return;
		}

		const handle = request.substring( WOOCOMMERCE_NAMESPACE.length );
		const irregularExternalMap = {
			'block-data': [ 'wc', 'wcBlocksData' ],
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
			'block-data': 'wc-blocks-data-store',
			'csv-export': 'wc-csv',
		};

		if ( irregularHandleMap[ handle ] ) {
			return irregularHandleMap[ handle ];
		}

		return 'wc-' + handle;
	}
};

class DependencyExtractionWebpackPlugin extends WPDependencyExtractionWebpackPlugin {
	constructor( options ) {
		const bundledPackages = options?.bundledPackages || [];
		const userRequestToExternal = options?.requestToExternal;
		const userRequestToHandle = options?.requestToHandle;
		// Mirror the upstream default so WooCommerce defaults respect useDefaults: false.
		const useDefaults = options?.useDefaults !== false;

		super( {
			...options,
			// Inject WooCommerce defaults ahead of WP defaults, without overriding externalizeWpDeps/mapRequestToDependency.
			requestToExternal: ( request ) => {
				if ( userRequestToExternal ) {
					const result = userRequestToExternal( request );
					if ( result !== undefined ) {
						return result;
					}
				}
				if ( useDefaults ) {
					return wooRequestToExternal( request, bundledPackages );
				}
			},
			requestToHandle: ( request ) => {
				if ( userRequestToHandle ) {
					const result = userRequestToHandle( request );
					if ( result !== undefined ) {
						return result;
					}
				}
				if ( useDefaults ) {
					return wooRequestToHandle( request );
				}
			},
		} );
	}

	/**
	 * Patched copy of WPDependencyExtractionWebpackPlugin.apply() from @wordpress/dependency-extraction-webpack-plugin/lib/index.js.
	 * Patches wrong webpack instance usage. Keep in sync with the parent when upgrading @wordpress/dependency-extraction-webpack-plugin.
	 *
	 * @param {import('webpack').Compiler} compiler
	 */
	apply( compiler ) {
		this.useModules = Boolean( compiler.options.output?.module );

		// This is THE patch, originally: 'this.externalsPlugin = new webpack.ExternalsPlugin(.' The root cause is pnpm
		// peer dependencies causing different webpack instance usage, causing the external modules being uncacheable.
		// Please remove the apply override once https://github.com/WordPress/gutenberg/pull/77284 is merged and released.
		this.externalsPlugin = new compiler.webpack.ExternalsPlugin(
			this.useModules ? 'import' : 'window',
			this.externalizeWpDeps.bind( this )
		);

		this.externalsPlugin.apply( compiler );

		compiler.hooks.thisCompilation.tap(
			this.constructor.name,
			( compilation ) => {
				compilation.hooks.processAssets.tap(
					{
						name: this.constructor.name,
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_OPTIMIZE_COMPATIBILITY,
					},
					() => this.checkForMagicComments( compilation )
				);
				compilation.hooks.processAssets.tap(
					{
						name: this.constructor.name,
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_ANALYSE,
					},
					() => this.addAssets( compilation )
				);
			}
		);
	}
}

module.exports = DependencyExtractionWebpackPlugin;
