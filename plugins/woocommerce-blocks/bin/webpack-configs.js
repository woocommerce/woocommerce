/**
 * External dependencies
 */
const path = require( 'path' );
const fs = require( 'fs' );
const { paramCase } = require( 'change-case' );
const RemoveFilesPlugin = require( './remove-files-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const WebpackRTLPlugin = require( './webpack-rtl-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const CreateFileWebpack = require( 'create-file-webpack' );
const CircularDependencyPlugin = require( 'circular-dependency-plugin' );
const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

/**
 * Internal dependencies
 */
const { getEntryConfig } = require( './webpack-entries' );
const {
	ASSET_CHECK,
	NODE_ENV,
	CHECK_CIRCULAR_DEPS,
	requestToExternal,
	requestToHandle,
	getProgressBarPluginConfig,
	getCacheGroups,
} = require( './webpack-helpers' );

const isProduction = NODE_ENV === 'production';

/**
 * Shared config for all script builds.
 */
let initialBundleAnalyzerPort = 8888;
const getSharedPlugins = ( {
	bundleAnalyzerReportTitle,
	checkCircularDeps = true,
} ) =>
	[
		CHECK_CIRCULAR_DEPS === 'true' && checkCircularDeps !== false
			? new CircularDependencyPlugin( {
					exclude: /node_modules/,
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
			requestToExternal,
			requestToHandle,
		} ),
	].filter( Boolean );

/**
 * Build config for core packages.
 *
 * @param {Object} options Build options.
 */
const getCoreConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'core', options.exclude || [] ),
		output: {
			filename: ( chunkData ) => {
				return `${ paramCase( chunkData.chunk.name ) }.js`;
			},
			path: path.resolve( __dirname, '../build/' ),
			library: [ 'wc', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksCoreJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(t|j)sx?$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader',
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
							plugins: [
								'@babel/plugin-proposal-optional-chaining',
								'@babel/plugin-proposal-class-properties',
							],
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
			new CreateFileWebpack( {
				path: './',
				// file name
				fileName: 'blocks.ini',
				// content of the file
				content: `
woocommerce_blocks_phase = ${ process.env.WOOCOMMERCE_BLOCKS_PHASE || 3 }
woocommerce_blocks_env = ${ NODE_ENV }
`.trim(),
			} ),
		],
		optimization: {
			// Only concatenate modules in production, when not analyzing bundles.
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
					extractComments: false,
				} ),
			],
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
	let { fileSuffix } = options;
	const { alias, resolvePlugins = [] } = options;
	fileSuffix = fileSuffix ? `-${ fileSuffix }` : '';
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'main', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: path.resolve( __dirname, '../build/' ),
			// This is a cache busting mechanism which ensures that the script is loaded via the browser with a ?ver=hash
			// string. The hash is based on the built file contents.
			// @see https://github.com/webpack/webpack/issues/2329
			// Using the ?ver string is needed here so the filename does not change between builds. The WordPress
			// i18n system relies on the hash of the filename, so changing that frequently would result in broken
			// translations which we must avoid.
			// @see https://github.com/Automattic/jetpack/pull/20926
			chunkFilename: `[name]${ fileSuffix }.js?ver=[contenthash]`,
			filename: `[name]${ fileSuffix }.js`,
			library: [ 'wc', 'blocks', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksMainJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
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
								'@babel/plugin-proposal-optional-chaining',
								'@babel/plugin-proposal-class-properties',
							].filter( Boolean ),
							cacheDirectory: true,
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
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
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
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
					extractComments: false,
				} ),
			],
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
							/**
							 * Getting the block name from the JSON metadata is less error prone
							 * than extracting it from the file path.
							 */
							const JSONFile = fs.readFileSync(
								path.resolve( __dirname, absoluteFilename )
							);
							const metadata = JSON.parse( JSONFile.toString() );
							const blockName = metadata.name
								.split( '/' )
								.at( 1 );

							if ( metadata.parent )
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
 * Build config for Blocks in the frontend context.
 *
 * @param {Object} options Build options.
 */
const getFrontConfig = ( options = {} ) => {
	let { fileSuffix } = options;
	const { alias, resolvePlugins = [] } = options;
	fileSuffix = fileSuffix ? `-${ fileSuffix }` : '';
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'frontend', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: path.resolve( __dirname, '../build/' ),
			// This is a cache busting mechanism which ensures that the script is loaded via the browser with a ?ver=hash
			// string. The hash is based on the built file contents.
			// @see https://github.com/webpack/webpack/issues/2329
			// Using the ?ver string is needed here so the filename does not change between builds. The WordPress
			// i18n system relies on the hash of the filename, so changing that frequently would result in broken
			// translations which we must avoid.
			// @see https://github.com/Automattic/jetpack/pull/20926
			chunkFilename: `[name]-frontend${ fileSuffix }.js?ver=[contenthash]`,
			filename: `[name]-frontend${ fileSuffix }.js`,
			uniqueName: 'webpackWcBlocksFrontendJsonp',
			library: [ 'wc', '[name]' ],
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
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
								'@babel/plugin-proposal-optional-chaining',
								'@babel/plugin-proposal-class-properties',
							].filter( Boolean ),
							cacheDirectory: true,
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
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
			splitChunks: {
				minSize: 200000,
				automaticNameDelimiter: '--',
				cacheGroups: {
					commons: {
						test: /[\\/]node_modules[\\/]/,
						name: 'wc-blocks-vendors',
						chunks: 'all',
						enforce: true,
					},
					...getCacheGroups(),
				},
			},
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
					extractComments: false,
				} ),
			],
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Frontend',
			} ),
			new ProgressBarPlugin( getProgressBarPluginConfig( 'Frontend' ) ),
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
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'payments', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: path.resolve( __dirname, '../build/' ),
			filename: `[name].js`,
			uniqueName: 'webpackWcBlocksPaymentMethodExtensionJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
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
								'@babel/plugin-proposal-optional-chaining',
								'@babel/plugin-proposal-class-properties',
							].filter( Boolean ),
							cacheDirectory: true,
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
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
					extractComments: false,
				} ),
			],
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
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'extensions', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: path.resolve( __dirname, '../build/' ),
			filename: `[name].js`,
			uniqueName: 'webpackWcBlocksExtensionsMethodExtensionJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
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
								'@babel/plugin-proposal-optional-chaining',
								'@babel/plugin-proposal-class-properties',
							].filter( Boolean ),
							cacheDirectory: true,
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
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
					extractComments: false,
				} ),
			],
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
 * Build config for scripts to be used exclusively within the Site Editor context.
 *
 * @param {Object} options Build options.
 */
const getSiteEditorConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'editor', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: path.resolve( __dirname, '../build/' ),
			filename: `[name].js`,
			chunkLoadingGlobal: 'webpackWcBlocksExtensionsMethodExtensionJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader?cacheDirectory',
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
								'@babel/plugin-proposal-optional-chaining',
							].filter( Boolean ),
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
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					...getCacheGroups(),
				},
			},
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
					extractComments: false,
				} ),
			],
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
const getStylingConfig = ( options = {} ) => {
	let { fileSuffix } = options;
	const { alias, resolvePlugins = [] } = options;
	fileSuffix = fileSuffix ? `-${ fileSuffix }` : '';
	const resolve = alias
		? {
				alias,
				plugins: resolvePlugins,
		  }
		: {
				plugins: resolvePlugins,
		  };
	return {
		entry: getEntryConfig( 'styling', options.exclude || [] ),
		output: {
			devtoolNamespace: 'wc',
			path: path.resolve( __dirname, '../build/' ),
			filename: `[name]-style${ fileSuffix }.js`,
			library: [ 'wc', 'blocks', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksStylingJsonp',
		},
		optimization: {
			splitChunks: {
				automaticNameDelimiter: '--',
				cacheGroups: {
					editorStyle: {
						// Capture all `editor` stylesheets and editor-components stylesheets.
						test: ( module = {}, { moduleGraph } ) => {
							if ( ! module.type.includes( 'css' ) ) {
								return false;
							}

							const moduleIssuer =
								moduleGraph.getIssuer( module );
							if ( ! moduleIssuer ) {
								return false;
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
						name: 'wc-blocks-editor-style',
						chunks: 'all',
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
					use: {
						loader: 'babel-loader?cacheDirectory',
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
							plugins: [
								isProduction
									? require.resolve(
											'babel-plugin-transform-react-remove-prop-types'
									  )
									: false,
								'@babel/plugin-proposal-optional-chaining',
								'@babel/plugin-proposal-class-properties',
							].filter( Boolean ),
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
			...getSharedPlugins( { bundleAnalyzerReportTitle: 'Styles' } ),
			new ProgressBarPlugin( getProgressBarPluginConfig( 'Styles' ) ),
			new MiniCssExtractPlugin( {
				filename: `[name]${ fileSuffix }.css`,
			} ),
			new WebpackRTLPlugin( {
				filenameSuffix: '-rtl.css',
			} ),
			// Remove JS files generated by MiniCssExtractPlugin.
			new RemoveFilesPlugin( `./build/*style${ fileSuffix }.js` ),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

const getInteractivityAPIConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	return {
		entry: {
			'wc-interactivity': './assets/js/interactivity',
		},
		output: {
			filename: '[name].js',
			path: path.resolve( __dirname, '../build/' ),
			library: [ 'wc', '__experimentalInteractivity' ],
			libraryTarget: 'this',
			chunkLoadingGlobal: 'webpackWcBlocksJsonp',
		},
		resolve: {
			alias,
			plugins: resolvePlugins,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'WP directives',
				checkCircularDeps: false,
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'WP directives' )
			),
		],
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
					use: [
						{
							loader: require.resolve( 'babel-loader' ),
							options: {
								cacheDirectory:
									process.env.BABEL_CACHE_DIRECTORY || true,
								babelrc: false,
								configFile: false,
								presets: [
									'@babel/preset-typescript',
									[
										'@babel/preset-react',
										{
											runtime: 'automatic',
											importSource: 'preact',
										},
									],
								],
								// Required until Webpack is updated to ^5.0.0
								plugins: [
									'@babel/plugin-proposal-optional-chaining',
									'@babel/plugin-proposal-class-properties',
								],
							},
						},
					],
				},
			],
		},
	};
};

module.exports = {
	getCoreConfig,
	getFrontConfig,
	getMainConfig,
	getPaymentsConfig,
	getExtensionsConfig,
	getSiteEditorConfig,
	getStylingConfig,
	getInteractivityAPIConfig,
};
