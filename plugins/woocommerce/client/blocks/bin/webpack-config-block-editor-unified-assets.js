/**
 * External dependencies
 */
const path = require( 'path' );
const { omit } = require( 'lodash' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );

/**
 * Internal dependencies
 */
const { getEntryConfig } = require( './webpack-entries' );
const {
	editorStyleEntries,
	styleEntries,
} = require( './webpack-interactivity-entries' );
const {
	NODE_ENV,
	getProgressBarPluginConfig,
	getResolve,
	requestToExternal,
	requestToHandle,
} = require( './webpack-helpers' );
const { getSharedPlugins, getStylingConfig } = require( './webpack-configs' );
const { sharedOptimizationConfig } = require( './webpack-shared-config' );

const ROOT_DIR = path.resolve( __dirname, '../../../../../' );
// Blocks' webpack writes directly to the WooCommerce plugin's
// `assets/client/blocks/` so PHP can enqueue files from their final location
// without an intermediate rsync step.
const BUILD_DIR = path.resolve( __dirname, '../../../assets/client/blocks' );
const BABEL_CACHE_DIR = path.join(
	ROOT_DIR,
	'node_modules/.cache/babel-loader'
);
const isProduction = NODE_ENV === 'production';
const UNIFIED_EDITOR_STYLE_HANDLE = 'wc-block-library-style';

const editorExternalPackages = [
	'@woocommerce/block-data',
	'@woocommerce/blocks-checkout',
	'@woocommerce/blocks-checkout-events',
	'@woocommerce/blocks-components',
	'@woocommerce/blocks-registry',
	'@woocommerce/data',
	'@woocommerce/entities',
	'@woocommerce/price-format',
	'@woocommerce/shared-context',
	'@woocommerce/shared-hocs',
];

const shouldBundleWooPackage = ( request ) =>
	request.startsWith( '@woocommerce/' ) &&
	! editorExternalPackages.includes( request );

const requestToUnifiedEditorExternal = ( request ) => {
	if ( shouldBundleWooPackage( request ) ) {
		return false;
	}

	return requestToExternal( request );
};

const requestToUnifiedEditorHandle = ( request ) => {
	if ( shouldBundleWooPackage( request ) ) {
		return false;
	}

	return requestToHandle( request );
};

const getUnifiedEditorPackageAliases = () => ( {
	'@woocommerce/block-data': path.resolve( __dirname, `../assets/js/data` ),
	'@woocommerce/blocks-checkout': path.resolve(
		__dirname,
		`../packages/checkout`
	),
	'@woocommerce/blocks-checkout-events': path.resolve(
		__dirname,
		`../assets/js/events`
	),
	'@woocommerce/blocks-components': path.resolve(
		__dirname,
		`../packages/components`
	),
	'@woocommerce/blocks-registry': path.resolve(
		__dirname,
		`../assets/js/blocks-registry`
	),
	'@woocommerce/data': path.resolve(
		__dirname,
		`../../../../../packages/js/data/src/index.ts`
	),
	'@woocommerce/price-format': path.resolve(
		__dirname,
		`../packages/prices`
	),
	'@woocommerce/sanitize': path.resolve(
		__dirname,
		`../../../../../packages/js/sanitize/src/index.ts`
	),
	'@woocommerce/settings': path.resolve(
		__dirname,
		`../assets/js/settings/shared`
	),
	'@woocommerce/shared-context': path.resolve(
		__dirname,
		`../assets/js/shared/context/`
	),
	'@woocommerce/shared-hocs': path.resolve(
		__dirname,
		`../assets/js/shared/hocs/`
	),
} );

/**
 * Reuse the established styling entry graph so the unified bundle includes
 * transitive and nonstandard SCSS imports as the legacy per-block build does.
 *
 * @param {string[]} exclude Entry names to exclude.
 * @return {Object} Unified styling entries.
 */
const getUnifiedEditorStyleEntries = ( exclude = [] ) =>
	omit(
		{
			'wc-block-library-style-source': [
				...Object.values( getEntryConfig( 'styling', exclude ) ).flat(),
				// Interactivity styles are emitted by a separate frontend build,
				// so they are excluded from the standard styling entry graph.
				...Object.values( styleEntries ).flat(),
				...Object.values( editorStyleEntries ).flat(),
			],
		},
		exclude
	);

/**
 * Build config for unified Blocks editor scripts.
 *
 * @param {Object} options Build options.
 */
const getUnifiedMainConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( {
		alias: {
			...getUnifiedEditorPackageAliases(),
			...alias,
		},
		resolvePlugins,
	} );

	return {
		entry: omit(
			{
				'wc-block-library': Object.values(
					getEntryConfig( 'main' )
				).flat(),
			},
			options.exclude || []
		),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			// Keep the filename stable for WordPress translations while using
			// the query string to invalidate cached chunks after a build.
			chunkFilename: `wc-block-library-[name].js?ver=[contenthash]`,
			filename: `[name].js`,
			uniqueName: 'webpackWcBlocksUnifiedMainJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
							plugins: [
								isProduction
									? require.resolve(
											'babel-plugin-transform-react-remove-prop-types'
									  )
									: false,
							].filter( Boolean ),
							cacheDirectory: BABEL_CACHE_DIR,
							cacheCompression: false,
						},
					},
				},
				{
					test: /\.s[c|a]ss$/,
					use: {
						loader: 'ignore-loader',
					},
				},
			],
		},
		optimization: {
			...sharedOptimizationConfig,
			splitChunks: false,
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Unified editor',
				dependencyRequestToExternal: requestToUnifiedEditorExternal,
				dependencyRequestToHandle: requestToUnifiedEditorHandle,
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Unified editor' )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for unified Blocks editor styles.
 *
 * @param {Object} options Build options.
 */
const getUnifiedStylingConfig = ( options = {} ) => {
	const stylingConfig = getStylingConfig( options );

	return {
		...stylingConfig,
		entry: getUnifiedEditorStyleEntries( options.exclude || [] ),
		output: {
			...stylingConfig.output,
			uniqueName: 'webpackWcBlocksUnifiedStylingJsonp',
		},
		optimization: {
			...stylingConfig.optimization,
			splitChunks: {
				...stylingConfig.optimization.splitChunks,
				cacheGroups: {
					// JavaScript is traversed only to discover stylesheet imports.
					// Do not emit the inherited JavaScript split chunks.
					default: false,
					defaultVendors: false,
					editorStyle: {
						test: ( module = {} ) => {
							return module.type.includes( 'css' );
						},
						name: UNIFIED_EDITOR_STYLE_HANDLE,
						chunks: 'all',
						enforce: true,
						priority: 10,
					},
				},
			},
		},
	};
};

module.exports = {
	getUnifiedMainConfig,
	getUnifiedStylingConfig,
};
