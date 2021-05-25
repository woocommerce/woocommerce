/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const RemoveFilesPlugin = require( './remove-files-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const chalk = require( 'chalk' );
const { kebabCase } = require( 'lodash' );
const CreateFileWebpack = require( 'create-file-webpack' );
const CircularDependencyPlugin = require( 'circular-dependency-plugin' );

/**
 * Internal dependencies
 */
const { getEntryConfig } = require( './webpack-entries' );
const {
	NODE_ENV,
	CHECK_CIRCULAR_DEPS,
	requestToExternal,
	requestToHandle,
	requestToExternalInsideGB,
	requestToHandleInsideGB,
	findModuleMatch,
} = require( './webpack-helpers' );

const sharedPlugins = [
	CHECK_CIRCULAR_DEPS === 'true'
		? new CircularDependencyPlugin( {
				exclude: /node_modules/,
				cwd: process.cwd(),
				failOnError: 'warn',
		  } )
		: false,
	new DependencyExtractionWebpackPlugin( {
		injectPolyfill: true,
		requestToExternal,
		requestToHandle,
	} ),
].filter( Boolean );

const mainBlocksPlugins = [
	CHECK_CIRCULAR_DEPS === 'true'
		? new CircularDependencyPlugin( {
				exclude: /node_modules/,
				cwd: process.cwd(),
				failOnError: 'warn',
		  } )
		: false,
	new DependencyExtractionWebpackPlugin( {
		injectPolyfill: true,
		requestToExternal: requestToExternalInsideGB,
		requestToHandle: requestToHandleInsideGB,
	} ),
].filter( Boolean );
const getProgressBarPluginConfig = ( name, fileSuffix ) => {
	const isLegacy = fileSuffix && fileSuffix === 'legacy';
	const progressBarPrefix = isLegacy ? 'Legacy ' : '';
	return {
		format:
			chalk.blue( `Building ${ progressBarPrefix }${ name }` ) +
			' [:bar] ' +
			chalk.green( ':percent' ) +
			' :msg (:elapsed seconds)',
		summary: false,
		customSummary: ( time ) => {
			console.log(
				chalk.green.bold(
					`${ progressBarPrefix }${ name } assets build completed (${ time })`
				)
			);
		},
	};
};

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
		optimization: {
			splitChunks: {
				automaticNameDelimiter: '--',
			},
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
							plugins: [
								require.resolve(
									'@babel/plugin-proposal-class-properties'
								),
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
		resolve: {
			...resolve,
			extensions: [ '.js', '.ts', '.tsx' ],
		},
	};
};
// @todo delete getCoreEditorConfig when wordpress/gutenberg#27462 or rquivalent is merged.
// This is meant to fix issue #3839 in which we have two instances of SlotFillProvider context. Should be deleted once wordpress/gutenberg#27462.
const getCoreEditorConfig = ( options = {} ) => {
	return {
		...getCoreConfig( options ),
		entry: {
			blocksCheckout: './packages/checkout/index.js',
		},
		output: {
			filename: ( chunkData ) => {
				return `${ kebabCase( chunkData.chunk.name ) }-editor.js`;
			},
			path: path.resolve( __dirname, '../build/' ),
			library: [ 'wc', '[name]' ],
			libraryTarget: 'this',
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			jsonpFunction: 'webpackWcBlocksJsonp',
		},
		plugins: [
			...mainBlocksPlugins,
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
	};
};
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
			filename: `[name]${ fileSuffix }.js`,
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
					commons: {
						test: /[\\/]node_modules[\\/]/,
						name: 'vendors',
						chunks: 'all',
						enforce: true,
					},
				},
			},
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
								NODE_ENV === 'production'
									? require.resolve(
											'babel-plugin-transform-react-remove-prop-types'
									  )
									: false,
								require.resolve(
									'@babel/plugin-proposal-class-properties'
								),
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
		plugins: [
			...sharedPlugins,
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Main', options.fileSuffix )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

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
			filename: `[name]-frontend${ fileSuffix }.js`,
			// This fixes an issue with multiple webpack projects using chunking
			// overwriting each other's chunk loader function.
			// See https://webpack.js.org/configuration/output/#outputjsonpfunction
			jsonpFunction: 'webpackWcBlocksJsonp',
		},
		optimization: {
			splitChunks: {
				automaticNameDelimiter: '--',
			},
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
									'@babel/preset-env',
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
								require.resolve(
									'@babel/plugin-proposal-object-rest-spread'
								),
								require.resolve(
									'@babel/plugin-transform-react-jsx'
								),
								require.resolve(
									'@babel/plugin-proposal-async-generator-functions'
								),
								require.resolve(
									'@babel/plugin-transform-runtime'
								),
								require.resolve(
									'@babel/plugin-proposal-class-properties'
								),
								NODE_ENV === 'production'
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
		optimization: {
			splitChunks: {
				automaticNameDelimiter: '--',
			},
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
									'@babel/preset-env',
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
								require.resolve(
									'@babel/plugin-proposal-object-rest-spread'
								),
								require.resolve(
									'@babel/plugin-transform-react-jsx'
								),
								require.resolve(
									'@babel/plugin-proposal-async-generator-functions'
								),
								require.resolve(
									'@babel/plugin-transform-runtime'
								),
								require.resolve(
									'@babel/plugin-proposal-class-properties'
								),
								NODE_ENV === 'production'
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
									'@babel/preset-env',
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
								require.resolve(
									'@babel/plugin-proposal-object-rest-spread'
								),
								require.resolve(
									'@babel/plugin-transform-react-jsx'
								),
								require.resolve(
									'@babel/plugin-proposal-async-generator-functions'
								),
								require.resolve(
									'@babel/plugin-transform-runtime'
								),
								require.resolve(
									'@babel/plugin-proposal-class-properties'
								),
								NODE_ENV === 'production'
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
			extensions: [ '.js', '.ts' ],
		},
	};
};

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
					editor: {
						// Capture all `editor` stylesheets and editor-components stylesheets.
						test: ( module = {} ) =>
							module.constructor.name === 'CssModule' &&
							( findModuleMatch( module, /editor\.scss$/ ) ||
								findModuleMatch(
									module,
									/[\\/]assets[\\/]js[\\/]editor-components[\\/]/
								) ),
						name: 'editor',
						chunks: 'all',
						priority: 10,
					},
					'vendors-style': {
						test: /\/node_modules\/.*?style\.s?css$/,
						name: 'vendors-style',
						chunks: 'all',
						priority: 7,
					},
					style: {
						// Capture all stylesheets with name `style` or
						// name that starts with underscore (abstracts).
						test: /(style|_.*)\.scss$/,
						name: 'style',
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
	getCoreEditorConfig,
};
