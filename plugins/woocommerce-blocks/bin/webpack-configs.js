/**
 * External dependencies
 */
const path = require( 'path' );
const { kebabCase } = require( 'lodash' );
const RemoveFilesPlugin = require( './remove-files-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
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
	NODE_ENV,
	CHECK_CIRCULAR_DEPS,
	requestToExternal,
	requestToHandle,
	findModuleMatch,
	getProgressBarPluginConfig,
} = require( './webpack-helpers' );

const isProduction = NODE_ENV === 'production';

/**
 * Shared config for all script builds.
 */
const sharedPlugins = [
	CHECK_CIRCULAR_DEPS === 'true'
		? new CircularDependencyPlugin( {
				exclude: /node_modules/,
				cwd: process.cwd(),
				failOnError: 'warn',
		  } )
		: false,
	// The WP_BUNDLE_ANALYZER global variable enables a utility that represents bundle
	// content as a convenient interactive zoomable treemap.
	process.env.WP_BUNDLE_ANALYZER && new BundleAnalyzerPlugin(),
	new DependencyExtractionWebpackPlugin( {
		injectPolyfill: true,
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
				return `${ kebabCase( chunkData.chunk.name ) }.js`;
			},
			path: path.resolve( __dirname, '../build/' ),
			library: [ 'wc', '[name]' ],
			libraryTarget: 'this',
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			jsonpFunction: 'webpackWcBlocksJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(t|j)sx?$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader?cacheDirectory',
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
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
			...sharedPlugins,
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Core', options.fileSuffix )
			),
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
			},
			minimizer: [
				new TerserPlugin( {
					cache: true,
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
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			// This can be removed when moving to webpack 5:
			// https://webpack.js.org/blog/2020-10-10-webpack-5-release/#automatic-unique-naming
			jsonpFunction: 'webpackWcBlocksJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: /node_modules/,
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
				minSize: 0,
				automaticNameDelimiter: '--',
				cacheGroups: {
					commons: {
						test: /[\\/]node_modules[\\/]/,
						name: 'wc-blocks-vendors',
						chunks: 'all',
						enforce: true,
					},
				},
			},
			minimizer: [
				new TerserPlugin( {
					cache: true,
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
			...sharedPlugins,
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Main', options.fileSuffix )
			),
			new CopyWebpackPlugin( {
				patterns: [
					{
						from: './assets/js/blocks/checkout/block.json',
						to: './checkout/block.json',
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
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			// This can be removed when moving to webpack 5:
			// https://webpack.js.org/blog/2020-10-10-webpack-5-release/#automatic-unique-naming
			jsonpFunction: 'webpackWcBlocksJsonp',
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
			},
			minimizer: [
				new TerserPlugin( {
					cache: true,
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
			...sharedPlugins,
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Frontend', options.fileSuffix )
			),
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
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			jsonpFunction: 'webpackWcBlocksPaymentMethodExtensionJsonp',
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
			},
			minimizer: [
				new TerserPlugin( {
					cache: true,
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
			...sharedPlugins,
			new ProgressBarPlugin(
				getProgressBarPluginConfig(
					'Payment Method Extensions',
					options.fileSuffix
				)
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
			jsonpFunction: 'webpackWcBlocksExtensionsMethodExtensionJsonp',
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
							].filter( Boolean ),
						},
					},
				},
			],
		},
		optimization: {
			concatenateModules:
				isProduction && ! process.env.WP_BUNDLE_ANALYZER,
			splitChunks: {
				automaticNameDelimiter: '--',
			},
			minimizer: [
				new TerserPlugin( {
					cache: true,
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
			...sharedPlugins,
			new ProgressBarPlugin(
				getProgressBarPluginConfig(
					'Experimental Extensions',
					options.fileSuffix
				)
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
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			jsonpFunction: 'webpackWcBlocksJsonp',
		},
		optimization: {
			splitChunks: {
				minSize: 0,
				automaticNameDelimiter: '--',
				cacheGroups: {
					editorStyle: {
						// Capture all `editor` stylesheets and editor-components stylesheets.
						test: ( module = {} ) =>
							module.constructor.name === 'CssModule' &&
							( findModuleMatch( module, /editor\.scss$/ ) ||
								findModuleMatch(
									module,
									/[\\/]assets[\\/]js[\\/]editor-components[\\/]/
								) ),
						name: 'wc-blocks-editor-style',
						chunks: 'all',
						priority: 10,
					},
					vendorsStyle: {
						test: /\/node_modules\/.*?style\.s?css$/,
						name: 'wc-blocks-vendors-style',
						chunks: 'all',
						priority: 7,
					},
					blocksStyle: {
						// Capture all stylesheets with name `style` or name that starts with underscore (abstracts).
						test: /(style|_.*)\.scss$/,
						name: 'wc-blocks-style',
						chunks: 'all',
						priority: 5,
					},
				},
			},
		},
		module: {
			rules: [
				{
					test: /\/node_modules\/.*?style\.s?css$/,
					use: [
						MiniCssExtractPlugin.loader,
						{ loader: 'css-loader', options: { importLoaders: 1 } },
						'postcss-loader',
						{
							loader: 'sass-loader',
							options: {
								sassOptions: {
									includePaths: [ 'node_modules' ],
								},
								additionalData: ( content ) => {
									const styleImports = [
										'colors',
										'breakpoints',
										'variables',
										'mixins',
										'animations',
										'z-index',
									]
										.map(
											( imported ) =>
												`@import "~@wordpress/base-styles/${ imported }";`
										)
										.join( ' ' );
									return styleImports + content;
								},
							},
						},
					],
				},
				{
					test: /\.s?css$/,
					exclude: /node_modules/,
					use: [
						MiniCssExtractPlugin.loader,
						{ loader: 'css-loader', options: { importLoaders: 1 } },
						'postcss-loader',
						{
							loader: 'sass-loader',
							options: {
								sassOptions: {
									includePaths: [ 'assets/css/abstracts' ],
								},
								additionalData: ( content, loaderContext ) => {
									const {
										resourcePath,
										rootContext,
									} = loaderContext;
									const relativePath = path.relative(
										rootContext,
										resourcePath
									);

									if (
										relativePath.startsWith(
											'assets/css/abstracts/'
										)
									) {
										return content;
									}

									return (
										'@use "sass:math";' +
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
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Styles', options.fileSuffix )
			),
			new WebpackRTLPlugin( {
				filename: `[name]${ fileSuffix }-rtl.css`,
				minify: {
					safe: true,
				},
			} ),
			new MiniCssExtractPlugin( {
				filename: `[name]${ fileSuffix }.css`,
			} ),
			// Remove JS files generated by MiniCssExtractPlugin.
			new RemoveFilesPlugin( `./build/*style${ fileSuffix }.js` ),
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
	getPaymentsConfig,
	getExtensionsConfig,
	getStylingConfig,
};
