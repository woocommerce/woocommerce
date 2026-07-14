/**
 * External dependencies
 */
const path = require( 'path' );
const fs = require( 'fs' );
const { paramCase } = require( 'change-case' );
const webpack = require( 'webpack' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const CircularDependencyPlugin = require( 'circular-dependency-plugin' );
const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

/**
 * Internal dependencies
 */
const DependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const {
	WebpackRTLPlugin,
} = require( '@woocommerce/internal-build/style-build' );
const FilesystemCacheWarningsPlugin = require( './filesystem-cache-warnings-webpack-plugin.js' );
const RemoveFilesPlugin = require( './remove-files-webpack-plugin' );
const { getEntryConfig, genericBlocks } = require( './webpack-entries' );
const {
	ASSET_CHECK,
	NODE_ENV,
	CHECK_CIRCULAR_DEPS,
	CONSOLIDATED_EDITOR_STYLE_HANDLE,
	requestToExternal,
	requestToEditorExternal,
	requestToHandle,
	requestToEditorHandle,
	getProgressBarPluginConfig,
	getCacheGroups,
	getEditorPackageAliases,
	getResolve,
} = require( './webpack-helpers' );
const AddSplitChunkDependencies = require( './add-split-chunk-dependencies' );
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

/**
 * Shared config for all script builds.
 */
let initialBundleAnalyzerPort = 8888;
const getSharedPlugins = ( {
	bundleAnalyzerReportTitle,
	checkCircularDeps = true,
	dependencyRequestToExternal = requestToExternal,
	dependencyRequestToHandle = requestToHandle,
} ) =>
	[
		CHECK_CIRCULAR_DEPS === 'true' && checkCircularDeps !== false
			? new CircularDependencyPlugin( {
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					cwd: process.cwd(),
					failOnError: 'warn',
			  } )
			: false,
		// The WP_BUNDLE_ANALYZER global variable enables a utility that represents bundle
		// content as a convenient interactive zoomable treemap.
		process.env.WP_BUNDLE_ANALYZER &&
			new BundleAnalyzerPlugin( {
				analyzerPort: initialBundleAnalyzerPort++,
				reportTitle: bundleAnalyzerReportTitle,
			} ),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			combineAssets: ASSET_CHECK,
			outputFormat: ASSET_CHECK ? 'json' : 'php',
			requestToExternal: dependencyRequestToExternal,
			requestToHandle: dependencyRequestToHandle,
		} ),
		// Substitute the `__i18n_text_domain__` identifier used by the
		// @woocommerce/email-editor package with the WooCommerce text
		// domain so strings extract and translate under `woocommerce`.
		new webpack.DefinePlugin( {
			__i18n_text_domain__: JSON.stringify( 'woocommerce' ),
		} ),
		// Suppress file system cache warnings (unsupported serialization related).
		new FilesystemCacheWarningsPlugin(),
	].filter( Boolean );

/**
 * Build config for core packages.
 *
 * @param {Object} options Build options.
 */
const getCoreConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( { alias, resolvePlugins } );
	return {
		entry: getEntryConfig( 'core', options.exclude || [] ),
		output: {
			filename: ( chunkData ) => {
				return `${ paramCase( chunkData.chunk.name ) }.js`;
			},
			path: BUILD_DIR,
			library: [ 'wc', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksCoreJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(t|j)sx?$/,
					exclude: [
						/[\/\\](node_modules|build|docs|bin|storybook|tests|test)[\/\\]/,
					],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
							plugins: [],
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
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Core',
			} ),
			new ProgressBarPlugin( getProgressBarPluginConfig( 'Core' ) ),
		],
		optimization: {
			...sharedOptimizationConfig,
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
		},
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for Blocks in the editor context.
 *
 * @param {Object} options Build options.
 */
const getMainConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( { alias, resolvePlugins } );

	return {
		entry: getEntryConfig( 'main', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			chunkFilename: `[name].js?ver=[contenthash]`,
			filename: `[name].js`,
			library: [ 'wc', 'blocks', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksMainJsonp',
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
			splitChunks: {
				minSize: 200000,
				automaticNameDelimiter: '--',
				cacheGroups: {
					commons: {
						test: /[\/\\]node_modules[\/\\]/,
						name: 'wc-blocks-vendors',
						chunks: 'all',
						enforce: true,
					},
					...getCacheGroups(),
				},
			},
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Main',
			} ),
			new ProgressBarPlugin( getProgressBarPluginConfig( 'Main' ) ),
			new CopyWebpackPlugin( {
				patterns: [
					{
						from: './assets/js/**/block.json',
						to( { absoluteFilename } ) {
							const JSONFile = fs.readFileSync(
								path.resolve( __dirname, absoluteFilename )
							);
							const metadata = JSON.parse( JSONFile.toString() );
							const blockName = metadata.name
								.split( '/' )
								.at( 1 );

							if (
								metadata.parent &&
								! genericBlocks[ blockName ]
							)
								return `./inner-blocks/${ blockName }/block.json`;
							return `./${ blockName }/block.json`;
						},
					},
				],
			} ),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for consolidated Blocks editor assets.
 *
 * @param {Object} options Build options.
 */
const getConsolidatedMainConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;

	const resolve = getResolve( {
		alias: {
			...getEditorPackageAliases(),
			...alias,
		},
		resolvePlugins,
	} );
	return {
		entry: getEntryConfig( 'consolidatedMain', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			// This is a cache busting mechanism which ensures that the script is loaded via the browser with a ?ver=hash
			// string. The hash is based on the built file contents.
			// @see https://github.com/webpack/webpack/issues/2329
			// Using the ?ver string is needed here so the filename does not change between builds. The WordPress
			// i18n system relies on the hash of the filename, so changing that frequently would result in broken
			// translations which we must avoid.
			// @see https://github.com/Automattic/jetpack/pull/20926
			chunkFilename: `wc-block-library-[name].js?ver=[contenthash]`,
			filename: `[name].js`,
			library: [ 'wc', 'blocks', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksConsolidatedMainJsonp',
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
				bundleAnalyzerReportTitle: 'Consolidated editor',
				dependencyRequestToExternal: requestToEditorExternal,
				dependencyRequestToHandle: requestToEditorHandle,
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Consolidated editor' )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for Blocks in the frontend context.
 *
 * @param {Object} options Build options.
 */
const getFrontConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( { alias, resolvePlugins } );
	return {
		entry: getEntryConfig( 'frontend', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			// This is a cache busting mechanism which ensures that the script is loaded via the browser with a ?ver=hash
			// string. The hash is based on the built file contents.
			// @see https://github.com/webpack/webpack/issues/2329
			// Using the ?ver string is needed here so the filename does not change between builds. The WordPress
			// i18n system relies on the hash of the filename, so changing that frequently would result in broken
			// translations which we must avoid.
			// @see https://github.com/Automattic/jetpack/pull/20926
			chunkFilename: `[name]-frontend.js?ver=[contenthash]`,
			filename: () => {
				return '[name]-frontend.js';
			},
			uniqueName: 'webpackWcBlocksFrontendJsonp',
			library: [ 'wc', '[name]' ],
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[
									'@wordpress/babel-preset-default',
									{
										modules: false,
										targets: {
											browsers: [
												'extends @wordpress/browserslist-config',
											],
										},
									},
								],
							],
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
			splitChunks: {
				minSize: 200000,
				automaticNameDelimiter: '--',
				cacheGroups: {
					vendor: {
						test: /[\\/]node_modules[\\/]/,
						// Note that filenames are suffixed with `frontend` so the generated file is `wc-blocks-frontend-vendors-frontend`.
						name: 'wc-blocks-frontend-vendors',
						chunks: ( chunk ) => {
							return (
								chunk.name !== 'product-button-interactivity'
							);
						},
						enforce: true,
					},
					...getCacheGroups(),
				},
			},
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Frontend',
			} ),
			new ProgressBarPlugin( getProgressBarPluginConfig( 'Frontend' ) ),
			new AddSplitChunkDependencies(),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for built-in payment gateway integrations.
 *
 * @param {Object} options Build options.
 */
const getPaymentsConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( { alias, resolvePlugins } );
	return {
		entry: getEntryConfig( 'payments', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			filename: `[name].js`,
			uniqueName: 'webpackWcBlocksPaymentMethodExtensionJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[
									'@wordpress/babel-preset-default',
									{
										modules: false,
										targets: {
											browsers: [
												'extends @wordpress/browserslist-config',
											],
										},
									},
								],
							],
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
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Payment Method Extensions',
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Payment Method Extensions' )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for extension integrations.
 *
 * @param {Object} options Build options.
 */
const getExtensionsConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( { alias, resolvePlugins } );
	return {
		entry: getEntryConfig( 'extensions', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			filename: '[name].js',
			uniqueName: 'webpackWcBlocksExtensionsMethodExtensionJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[
									'@wordpress/babel-preset-default',
									{
										modules: false,
										targets: {
											browsers: [
												'extends @wordpress/browserslist-config',
											],
										},
									},
								],
							],
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
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Experimental Extensions',
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Experimental Extensions' )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for scripts used exclusively in the Site Editor.
 *
 * @param {Object} options Build options.
 */
const getSiteEditorConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( { alias, resolvePlugins } );

	return {
		entry: getEntryConfig( 'editor', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			filename: `[name].js`,
			chunkLoadingGlobal: 'webpackWcBlocksExtensionsMethodExtensionJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[
									'@wordpress/babel-preset-default',
									{
										modules: false,
										targets: {
											browsers: [
												'extends @wordpress/browserslist-config',
											],
										},
									},
								],
							],
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
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Site Editor',
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Site Editor' )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for CSS Styles.
 *
 * @param {Object} options Build options.
 */
const getStyleConfig = ( options = {}, consolidated = false ) => {
	const { alias, resolvePlugins = [] } = options;
	const entryType = consolidated ? 'consolidatedStyling' : 'styling';
	const configName = consolidated ? 'Consolidated styles' : 'Styles';

	const resolve = getResolve( { alias, resolvePlugins } );
	return {
		entry: getEntryConfig( entryType, options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			filename: '[name]-style.js',
			library: [ 'wc', 'blocks', '[name]' ],
			libraryTarget: 'this',
			uniqueName: consolidated
				? 'webpackWcBlocksConsolidatedStylingJsonp'
				: 'webpackWcBlocksStylingJsonp',
		},
		optimization: {
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					editorStyle: {
						test: ( module = {}, { moduleGraph } ) => {
							if ( ! module.type.includes( 'css' ) ) {
								return false;
							}

							const moduleIdentifier =
								typeof module.identifier === 'function'
									? module.identifier()
									: '';
							if ( consolidated ) {
								return moduleIdentifier.includes(
									'?editor-bundle'
								);
							}

							const moduleIssuer =
								moduleGraph.getIssuer( module );
							if ( ! moduleIssuer ) {
								return module.resource?.endsWith(
									'editor.scss'
								);
							}

							return (
								moduleIssuer.resource.endsWith(
									'editor.scss'
								) ||
								moduleIssuer.resource.includes(
									`${ path.sep }assets${ path.sep }js${ path.sep }editor-components${ path.sep }`
								)
							);
						},
						name: consolidated
							? CONSOLIDATED_EDITOR_STYLE_HANDLE
							: 'wc-blocks-editor-style',
						chunks: 'all',
						enforce: consolidated,
						priority: 10,
					},
					...getCacheGroups(),
					'base-components': {
						test: /\/assets\/js\/base\/components\//,
						name( module, chunks, cacheGroupKey ) {
							const moduleFileName = module
								.identifier()
								.split( '/' )
								.reduceRight( ( item ) => item )
								.split( '|' )
								.reduce( ( item ) => item );
							const allChunksNames = chunks
								.map( ( item ) => item.name )
								.join( '~' );
							return `${ cacheGroupKey }-${ allChunksNames }-${ moduleFileName }`;
						},
					},
				},
			},
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
					test: /\.s?css$/,
					use: [
						MiniCssExtractPlugin.loader,
						'css-loader',
						'postcss-loader',
						{
							loader: 'sass-loader',
							options: {
								sassOptions: {
									includePaths: [ 'assets/css/abstracts' ],
								},
								additionalData: ( content, loaderContext ) => {
									const { resourcePath, rootContext } =
										loaderContext;
									const relativePath = path.relative(
										rootContext,
										resourcePath
									);

									if (
										relativePath.startsWith(
											'assets/css/abstracts/'
										) ||
										relativePath.startsWith(
											'assets\\css\\abstracts\\'
										)
									) {
										return content;
									}

									return (
										'@use "sass:math";' +
										'@use "sass:string";' +
										'@use "sass:color";' +
										'@use "sass:map";' +
										'@import "_colors"; ' +
										'@import "_variables"; ' +
										'@import "_breakpoints"; ' +
										'@import "_mixins"; ' +
										content
									);
								},
							},
						},
					],
				},
			],
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: configName,
			} ),
			new ProgressBarPlugin( getProgressBarPluginConfig( configName ) ),
			new MiniCssExtractPlugin( {
				filename: '[name].css',
			} ),
			new WebpackRTLPlugin(),
			// Remove JS files generated by MiniCssExtractPlugin.
			new RemoveFilesPlugin( path.join( BUILD_DIR, '*style.js' ) ),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

const getStylingConfig = ( options = {} ) => getStyleConfig( options, false );

const getConsolidatedStylingConfig = ( options = {} ) =>
	getStyleConfig( options, true );

const getCartAndCheckoutFrontendConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;

	const resolve = getResolve( { alias, resolvePlugins } );
	return {
		entry: getEntryConfig(
			'cartAndCheckoutFrontend',
			options.exclude || []
		),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			// This is a cache busting mechanism which ensures that the script is loaded via the browser with a ?ver=hash
			// string. The hash is based on the built file contents.
			// @see https://github.com/webpack/webpack/issues/2329
			// Using the ?ver string is needed here so the filename does not change between builds. The WordPress
			// i18n system relies on the hash of the filename, so changing that frequently would result in broken
			// translations which we must avoid.
			// @see https://github.com/Automattic/jetpack/pull/20926
			chunkFilename: '[name]-frontend.js?ver=[contenthash]',
			filename: '[name]-frontend.js',
			uniqueName: 'webpackWcBlocksCartCheckoutFrontendJsonp',
			library: [ 'wc', '[name]' ],
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[
									'@wordpress/babel-preset-default',
									{
										modules: false,
										targets: {
											browsers: [
												'extends @wordpress/browserslist-config',
											],
										},
									},
								],
							],
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
			splitChunks: {
				minSize: 200000,
				automaticNameDelimiter: '--',
				cacheGroups: {
					commons: {
						test: /[\\/]node_modules[\\/]/,
						name: 'wc-cart-checkout-vendors',
						chunks: 'all',
						enforce: true,
					},
					base: {
						// A refined include blocks and settings that are shared between cart and checkout that produces the smallest possible bundle.
						test: /assets[\\/]js[\\/](settings|previews|base|data|utils|blocks[\\/]cart-checkout-shared|icons)|packages[\\/](checkout|components)|atomic[\\/]utils/,
						name: 'wc-cart-checkout-base',
						chunks: 'all',
						enforce: true,
					},
					...getCacheGroups(),
				},
			},
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Cart & Checkout Frontend',
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Cart & Checkout Frontend' )
			),
			new AddSplitChunkDependencies(),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};

module.exports = {
	getCoreConfig,
	getFrontConfig,
	getMainConfig,
	getConsolidatedMainConfig,
	getPaymentsConfig,
	getExtensionsConfig,
	getSiteEditorConfig,
	getStylingConfig,
	getConsolidatedStylingConfig,
	getCartAndCheckoutFrontendConfig,
};
