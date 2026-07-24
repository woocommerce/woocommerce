/**
 * External dependencies
 */
const path = require( 'path' );
const { omit } = require( 'lodash' );
const cssnano = require( 'cssnano' );
const postcss = require( 'postcss' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const webpack = require( 'webpack' );

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
const UNIFIED_EDITOR_STYLE_PATTERN = /^wc-block-library-style(?:-rtl)?\.css$/;
const OPTIMIZE_UNIFIED_EDITOR_STYLES_PLUGIN =
	'OptimizeUnifiedEditorStylesPlugin';

// Supported extension contracts. Changes to stable root exports require
// backwards-compatibility handling and a deprecation path.
const publicApiPackages = [
	'@woocommerce/block-data',
	'@woocommerce/blocks-checkout',
	'@woocommerce/blocks-checkout-events',
	'@woocommerce/blocks-components',
	'@woocommerce/blocks-registry',
	'@woocommerce/data',
	'@woocommerce/price-format',
	'@woocommerce/sanitize',
	'@woocommerce/settings',
	'@woocommerce/shared-context',
	'@woocommerce/shared-hocs',
	'@woocommerce/types',
];

// Externalized to preserve a shared runtime instance, not to expose a
// supported extension API.
const internalRuntimePackages = [ '@woocommerce/entities' ];

const editorExternalPackages = [
	...publicApiPackages,
	...internalRuntimePackages,
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
	'@woocommerce/block-data': path.resolve(
		__dirname,
		`../packages/public-api/block-data`
	),
	'@woocommerce/blocks-checkout': path.resolve(
		__dirname,
		`../packages/public-api/blocks-checkout`
	),
	'@woocommerce/blocks-checkout-events': path.resolve(
		__dirname,
		`../packages/public-api/blocks-checkout-events`
	),
	'@woocommerce/blocks-components': path.resolve(
		__dirname,
		`../packages/public-api/blocks-components`
	),
	'@woocommerce/blocks-registry': path.resolve(
		__dirname,
		`../packages/public-api/blocks-registry`
	),
	'@woocommerce/data': path.resolve(
		__dirname,
		`../../../../../packages/js/data/src/index.ts`
	),
	'@woocommerce/price-format': path.resolve(
		__dirname,
		`../packages/public-api/price-format`
	),
	'@woocommerce/sanitize': path.resolve(
		__dirname,
		`../../../../../packages/js/sanitize/src/index.ts`
	),
	'@woocommerce/settings': path.resolve(
		__dirname,
		`../packages/public-api/settings`
	),
	'@woocommerce/shared-context': path.resolve(
		__dirname,
		`../packages/public-api/shared-context/`
	),
	'@woocommerce/shared-hocs': path.resolve(
		__dirname,
		`../packages/public-api/shared-hocs/`
	),
} );

/**
 * Optimize the combined editor styles after CSS extraction and RTL generation.
 *
 * The inherited styling configuration runs PostCSS for every Sass entry before
 * MiniCssExtractPlugin combines those entries. When multiple entries import the
 * same shared styles, each entry is minified independently and the duplicate
 * rules remain in the final combined stylesheet.
 *
 * This plugin runs cssnano once more at Webpack's optimize-size stage, after
 * WebpackRTLPlugin has emitted its derived RTL asset. Processing at that point
 * allows cssnano to remove duplicates across the complete LTR and RTL bundles
 * rather than only within individual Sass entries.
 *
 * Only the unified editor stylesheet filenames are processed so legacy block
 * styles and other build outputs remain unchanged. The plugin is added to the
 * production configuration only; development builds avoid the additional work
 * to preserve fast rebuilds and their existing source output.
 */
class OptimizeUnifiedEditorStylesPlugin {
	/**
	 * Apply the plugin.
	 *
	 * @param {webpack.Compiler} compiler Webpack compiler.
	 */
	apply( compiler ) {
		compiler.hooks.thisCompilation.tap(
			OPTIMIZE_UNIFIED_EDITOR_STYLES_PLUGIN,
			( compilation ) => {
				compilation.hooks.processAssets.tapPromise(
					{
						name: OPTIMIZE_UNIFIED_EDITOR_STYLES_PLUGIN,
						stage: webpack.Compilation
							.PROCESS_ASSETS_STAGE_OPTIMIZE_SIZE,
					},
					async ( assets ) => {
						await Promise.all(
							Object.entries( assets )
								.filter( ( [ assetName ] ) =>
									UNIFIED_EDITOR_STYLE_PATTERN.test(
										assetName
									)
								)
								.map( async ( [ assetName, asset ] ) => {
									const result = await postcss( [
										cssnano,
									] ).process( asset.source().toString(), {
										from: undefined,
										map: false,
									} );

									compilation.updateAsset(
										assetName,
										new webpack.sources.RawSource(
											result.css
										)
									);
								} )
						);
					}
				);
			}
		);
	}
}

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
			new webpack.optimize.LimitChunkCountPlugin( {
				maxChunks: 1,
			} ),
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
		plugins: [
			...stylingConfig.plugins,
			isProduction && new OptimizeUnifiedEditorStylesPlugin(),
		].filter( Boolean ),
	};
};

module.exports = {
	getUnifiedMainConfig,
	getUnifiedStylingConfig,
};
