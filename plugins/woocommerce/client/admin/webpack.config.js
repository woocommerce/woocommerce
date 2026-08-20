/**
 * External dependencies
 */
const { get } = require( 'lodash' );
const path = require( 'path' );
const fs = require( 'fs' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );
const ReactRefreshWebpackPlugin = require( '@pmmmwh/react-refresh-webpack-plugin' );
const webpack = require( 'webpack' );

/**
 * Internal dependencies
 */
const {
	webpackConfig: styleConfig,
} = require( '@woocommerce/internal-build/style-build' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin/src/index' );
const CustomTemplatedPathPlugin = require( './bin/custom-templated-path-webpack-plugin' );
const UnminifyWebpackPlugin = require( './bin/unminify-webpack-plugin.js' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const WC_ADMIN_PHASE = process.env.WC_ADMIN_PHASE || 'development';
const isHot = Boolean( process.env.HOT );
const isProduction = NODE_ENV === 'production';
const isWatch = ! isProduction && process.argv.includes( '--watch' );

const getSubdirectoriesAt = ( searchPath ) => {
	const dir = path.resolve( __dirname, searchPath );
	return fs
		.readdirSync( dir, { withFileTypes: true } )
		.filter( ( entry ) => entry.isDirectory() )
		.map( ( entry ) => entry.name );
};

const WC_ADMIN_PACKAGES_DIR = '../../../../packages/js';
const WP_ADMIN_SCRIPTS_DIR = './client/wp-admin-scripts';
const SETTINGS_UI_PACKAGE_DIR = path.resolve(
	__dirname,
	`${ WC_ADMIN_PACKAGES_DIR }/settings-ui`
);

const resolvePackageDirectory = ( packageName, fromDirectory ) =>
	path.dirname(
		require.resolve( `${ packageName }/package.json`, {
			paths: [ fromDirectory ],
		} )
	);

const dataViewsPackageDirectory = resolvePackageDirectory(
	'@wordpress/dataviews',
	SETTINGS_UI_PACKAGE_DIR
);
const dataViewsUiPackageDirectory = resolvePackageDirectory(
	'@wordpress/ui',
	dataViewsPackageDirectory
);
const dataViewsBundledPackageDirectories = [
	dataViewsPackageDirectory,
	resolvePackageDirectory(
		'@wordpress/components',
		dataViewsPackageDirectory
	),
	resolvePackageDirectory( '@wordpress/compose', dataViewsPackageDirectory ),
	resolvePackageDirectory( '@wordpress/data', dataViewsPackageDirectory ),
	dataViewsUiPackageDirectory,
	resolvePackageDirectory( '@wordpress/theme', dataViewsUiPackageDirectory ),
];
const dataViewsBundledDependencyRoots = [
	...new Set(
		dataViewsBundledPackageDirectories.map( ( packageDirectory ) =>
			path.resolve( packageDirectory, '../..' )
		)
	),
];
const dataViewsWpEntry = require.resolve( '@wordpress/dataviews/wp', {
	paths: [ SETTINGS_UI_PACKAGE_DIR ],
} );
const settingsUIDataFormRuntimeEntry = path.resolve(
	SETTINGS_UI_PACKAGE_DIR,
	'src/dataform-runtime.ts'
);

// Admin writes directly to the plugin's `assets/client/admin/` so PHP can
// enqueue without an intermediate copy step. The JS config and every composed
// package CSS config use this constant for `output.path`.
const BUILD_DIR = path.resolve( __dirname, '../../assets/client/admin' );

// wpAdminScripts are loaded on wp-admin pages outside the context of WooCommerce Admin
// See ./client/wp-admin-scripts/README.md for more details
const wpAdminScripts = getSubdirectoriesAt( WP_ADMIN_SCRIPTS_DIR ); // automatically include all subdirs
const wcAdminPackages = [
	// we use a whitelist for this instead of dynamically generating it because not all folders are packages meant for consumption
	'admin-layout',
	'components',
	'csv-export',
	'currency',
	'customer-effort-score',
	'date',
	'experimental-products-app',
	'experimental',
	'explat',
	'navigation',
	'notices',
	'number',
	'data',
	'tracks',
	'onboarding',
	'sanitize',
	'settings-ui',
	'remote-logging',
	'email-editor',
];

// Resolve each entry to the package's `wc-source` export as an absolute path.
// Using the package name (`@woocommerce/<name>`) as the entry request would
// match WooCommerceDependencyExtractionWebpackPlugin's externals list and
// collapse the entry into a `window.wc.<name>` shim that re-exports itself.
// Pointing entries at the filesystem dodges that match while still letting
// transitive `@woocommerce/*` imports inside the bundle externalize normally.
const resolvePackageSourceEntry = ( name ) => {
	const pkgJsonPath = path.resolve(
		__dirname,
		`${ WC_ADMIN_PACKAGES_DIR }/${ name }/package.json`
	);
	const pkgJson = require( pkgJsonPath );
	const source = pkgJson.exports?.[ '.' ]?.[ 'wc-source' ];
	if ( ! source ) {
		throw new Error(
			`Package @woocommerce/${ name } has no exports["."]["wc-source"] entry in ${ pkgJsonPath }`
		);
	}
	return path.resolve( path.dirname( pkgJsonPath ), source );
};

// Packages opt into having admin bundle their stylesheet by exporting a
// `src/style.scss` next to `src/index.ts`. The file isn't imported from
// `src/index.ts` (consumers historically relied on a separate `style.css`
// asset), so we add it as a second entry tuple. Webpack bundles both into
// the same chunk and MiniCssExtractPlugin emits `<pkg>/style.css`.
const resolvePackageStyleEntry = ( name ) => {
	const styleScss = path.resolve(
		__dirname,
		`${ WC_ADMIN_PACKAGES_DIR }/${ name }/src/style.scss`
	);
	return fs.existsSync( styleScss ) ? styleScss : null;
};

const getEntryPoints = () => {
	const entryPoints = {
		app: './client/index.tsx',
		embed: './client/embed.tsx',
	};
	wcAdminPackages.forEach( ( name ) => {
		const source = resolvePackageSourceEntry( name );
		const style = resolvePackageStyleEntry( name );
		const privateRuntime =
			name === 'settings-ui' ? settingsUIDataFormRuntimeEntry : null;
		// Order matters: webpack uses the last item in an array entry as the
		// chunk's export source. Private runtime and stylesheet entries come
		// first so only `src/index.ts` exports land on `window.wc.<name>`.
		const entries = [ style, privateRuntime, source ].filter( Boolean );
		entryPoints[ name ] = entries.length === 1 ? source : entries;
	} );
	wpAdminScripts.forEach( ( name ) => {
		entryPoints[ name ] = `${ WP_ADMIN_SCRIPTS_DIR }/${ name }`;
	} );
	return entryPoints;
};

// WordPress.org’s translation infrastructure ignores files named “.min.js” so we need to name our JS files without min when releasing the plugin.
const outputSuffix = WC_ADMIN_PHASE === 'core' ? '' : '.min';

const jsConfig = {
	name: 'admin-js',
	mode: NODE_ENV,
	performance: {
		hints: false,
	},
	cache:
		isWatch || process.env.CI || process.env.HOT || process.env.STORYBOOK
			? { type: 'memory' }
			: {
					type: 'filesystem',
					cacheDirectory: path.resolve(
						__dirname,
						`node_modules/.cache/webpack-${ WC_ADMIN_PHASE }-source`
					),
					buildDependencies: {
						config: [
							__filename,
							path.resolve(
								__dirname,
								'../../../../pnpm-lock.yaml'
							),
							require.resolve(
								'@woocommerce/dependency-extraction-webpack-plugin'
							),
							require.resolve(
								'@woocommerce/internal-build/style-build'
							),
						],
					},
			  },
	entry: getEntryPoints(),
	output: {
		filename: ( data ) => {
			// Output wpAdminScripts to wp-admin-scripts folder
			// See https://github.com/woocommerce/woocommerce-admin/pull/3061
			return wpAdminScripts.includes( data.chunk.name )
				? `wp-admin-scripts/[name]${ outputSuffix }.js`
				: `[name]/index${ outputSuffix }.js`;
		},
		chunkFilename: `chunks/[name]${ outputSuffix }.js?ver=[contenthash]`,
		path: BUILD_DIR,
		library: {
			// Expose the exports of entry points so we can consume the libraries in window.wc.[modulename] with WooCommerceDependencyExtractionWebpackPlugin.
			name: [ 'wc', '[modulename]' ],
			type: 'window',
		},
		// A unique name of the webpack build to avoid multiple webpack runtimes to conflict when using globals.
		uniqueName: '__wcAdmin_webpackJsonp',
	},
	module: {
		parser: styleConfig.parser,
		rules: [
			{
				// The DataForm runtime is a private entry until WOOPRD-3596 imports
				// it from the renderer. Keep it in production builds without
				// re-exporting it through window.wc.settingsUi.
				include: settingsUIDataFormRuntimeEntry,
				sideEffects: true,
			},
			{
				// DataViews' /wp build inlines WordPress packages but leaves their
				// third-party imports external. Resolve those imports from the exact
				// package graph bundled into this entry under pnpm's strict layout.
				// Keep this private runtime even while no public package export uses it.
				include: dataViewsWpEntry,
				sideEffects: true,
				resolve: {
					modules: [
						...dataViewsBundledDependencyRoots,
						'node_modules',
					],
				},
			},
			{
				test: /\.(t|j)sx?$/,
				parser: {
					// Disable AMD to fix an issue where underscore and lodash where clashing
					// See https://github.com/woocommerce/woocommerce-admin/pull/1004 and https://github.com/Automattic/woocommerce-services/pull/1522
					amd: false,
				},
				exclude: [
					/[\/\\]node_modules[\/\\]\.pnpm[\/\\]/,
					/[\/\\](changelog|bin|build|docs|test)[\/\\]/,
				],
				use: {
					loader: 'babel-loader',
					options: {
						// Prevent babel.config.js (Jest/Node context) from merging into this browser build and duplicating presets.
						configFile: false,
						sourceType: 'unambiguous',
						presets: [ '@wordpress/babel-preset-default' ],
						plugins: [
							! isProduction &&
								isHot &&
								require.resolve( 'react-refresh/babel' ),
							isProduction &&
								require.resolve(
									'babel-plugin-transform-react-remove-prop-types'
								),
						].filter( Boolean ),
						cacheDirectory: path.resolve(
							__dirname,
							'../../../../node_modules/.cache/babel-loader'
						),
						cacheCompression: false,
					},
				},
			},
			{ test: /\.md$/, use: 'raw-loader' },
			{
				// @wordpress/theme declares sideEffects: false, which would
				// tree-shake bare imports of its design tokens stylesheet.
				// TODO: remove this rule when bumping @wordpress/theme past
				// 0.17.0; newer releases fix the tree-shaking upstream.
				test: /@wordpress[\/\\]theme[\/\\].*\.css$/,
				sideEffects: true,
			},
			{
				test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/,
				type: 'asset',
			},
			...styleConfig.rules,
		],
	},
	resolve: {
		fallback: {
			// Reduce bundle size by omitting Node crypto library.
			// See https://github.com/woocommerce/woocommerce-admin/pull/5768
			crypto: 'empty',
			// Ignore fs, path to skip resolve errors for @automattic/calypso-config
			fs: false,
			path: false,
		},
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
		// Activate the `"wc-source"` conditional export declared in each
		// `packages/js/*` package.json. Webpack walks the package's exports map
		// and picks `./src/index.ts` directly — no per-package alias is
		// required, and transitive `@woocommerce/*` imports resolve to source
		// through the same mechanism. The condition is namespaced (`wc-` prefix)
		// so it never collides with third-party packages that publish their own
		// `"source"` conditional export. `'...'` extends the default list.
		conditionNames: [ 'wc-source', '...' ],
		alias: {
			'~': path.resolve( __dirname + '/client' ),
			'react/jsx-dev-runtime': require.resolve( 'react/jsx-dev-runtime' ),
			'react/jsx-runtime': require.resolve( 'react/jsx-runtime' ),
		},
	},
	plugins: [
		...styleConfig.plugins,
		// Substitute the `__i18n_text_domain__` identifier used by the
		// @woocommerce/email-editor package with the WooCommerce text
		// domain so strings extract and translate under `woocommerce`.
		new webpack.DefinePlugin( {
			__i18n_text_domain__: JSON.stringify( 'woocommerce' ),
		} ),
		new CustomTemplatedPathPlugin( {
			modulename( outputPath, data ) {
				const entryName = get( data, [ 'chunk', 'name' ] );
				if ( entryName ) {
					// Convert the dash-case name to a camel case module name.
					// For example, 'csv-export' -> 'csvExport'
					return entryName.replace( /-([a-z])/g, ( match, letter ) =>
						letter.toUpperCase()
					);
				}
				return outputPath;
			},
		} ),

		// React Fast Refresh.
		! isProduction && isHot && new ReactRefreshWebpackPlugin(),

		// We reuse this Webpack setup for Storybook, where we need to disable dependency extraction.
		! process.env.STORYBOOK &&
			new WooCommerceDependencyExtractionWebpackPlugin( {
				requestToExternal( request ) {
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
							// React 18 split createRoot/hydrateRoot into
							// react-dom/client. WordPress's wp-react-dom UMD
							// aggregates both entrypoints onto the same
							// window.ReactDOM global. DEWP's default mapper
							// doesn't know about the subpath yet
							// (https://github.com/WordPress/gutenberg/pull/77326),
							// so map it here.
							return 'ReactDOM';
						case '@wordpress/global-styles-engine':
							// @wordpress/global-styles-engine is not a standard WordPress package available globally,
							// so we need to bundle it instead of treating it as an external.
							return null;
					}

					if ( request.startsWith( '@wordpress/dataviews' ) ) {
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
					if (
						request.match( /^@wordpress\/.*\/build(?:-module)?/ )
					) {
						return null;
					}

					// Skip requesting to external if the import path is from the build or build-module directory for WooCommerce packages.
					// This can reduce the bundle size when we don't need to load the entire WooCommerce package.
					if (
						request.match( /^@woocommerce\/.*\/build(?:-module)?/ )
					) {
						return null;
					}
				},
				requestToHandle( request ) {
					if ( request === 'moment-timezone' ) {
						return 'moment';
					}
					if ( request === 'react-dom/client' ) {
						return 'react-dom';
					}
				},
			} ),
		process.env.ANALYZE && new BundleAnalyzerPlugin(),
		// We only want to generate unminified files in the development phase.
		WC_ADMIN_PHASE === 'development' &&
			// Generate unminified files to load the unminified version when `define( 'SCRIPT_DEBUG', true );` is set in wp-config.
			new UnminifyWebpackPlugin( {
				test: /\.js($|\?)/i,
				mainEntry: 'app/index.min.js',
			} ),
	].filter( Boolean ),
	optimization: {
		minimize: NODE_ENV !== 'development',
		splitChunks: {
			// Not to generate chunk names because it caused a stressful workflow when deploying the plugin to WP.org
			// See https://github.com/woocommerce/woocommerce-admin/pull/5229
			name: false,
		},
	},
};
if ( ! isProduction || WC_ADMIN_PHASE === 'development' ) {
	// Set default sourcemap mode if it wasn't set by WP_DEVTOOL.
	jsConfig.devtool = jsConfig.devtool || 'source-map';

	if ( isHot ) {
		// Add dev server config
		// Copied from https://github.com/WordPress/gutenberg/blob/05bea6dd5c6198b0287c41a401d36a06b48831eb/packages/scripts/config/webpack.config.js#L312-L326
		jsConfig.devServer = {
			devMiddleware: {
				writeToDisk: true,
			},
			allowedHosts: 'auto',
			host: 'localhost',
			port: 8887,
			proxy: {
				'/assets/client/admin': {
					pathRewrite: {
						'^/assets/client/admin': '',
					},
				},
			},
		};
	}
}

module.exports = jsConfig;
