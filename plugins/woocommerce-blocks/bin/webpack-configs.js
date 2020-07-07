/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const MergeExtractFilesPlugin = require( './merge-extract-files-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const { NormalModuleReplacementPlugin } = require( 'webpack' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const chalk = require( 'chalk' );
const { kebabCase } = require( 'lodash' );
const CreateFileWebpack = require( 'create-file-webpack' );

/**
 * Internal dependencies
 */
const { getEntryConfig } = require( './webpack-entries' );
const {
	NODE_ENV,
	requestToExternal,
	requestToHandle,
	findModuleMatch,
} = require( './webpack-helpers' );

const dashIconReplacementModule = path.resolve(
	__dirname,
	'../assets/js/module_replacements/dashicon.js'
);

const getCoreConfig = ( options = {} ) => {
	const isLegacy = options.fileSuffix && options.fileSuffix === 'legacy';
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
					test: /\.jsx?$/,
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
			],
		},
		plugins: [
			new ProgressBarPlugin( {
				format:
					chalk.blue( 'Building Core' ) +
					' [:bar] ' +
					chalk.green( ':percent' ) +
					' :msg (:elapsed seconds)',
				summary: false,
				customSummary: ( time ) => {
					const prefix = isLegacy ? 'Legacy' : '';
					console.log(
						chalk.green.bold(
							`${ prefix } Core assets build completed (${ time })`
						)
					);
				},
			} ),
			new DependencyExtractionWebpackPlugin( { injectPolyfill: true } ),
			new CreateFileWebpack( {
				path: './',
				// file name
				fileName: 'blocks.ini',
				// content of the file
				content: `woocommerce_blocks_phase = ${ process.env
					.WOOCOMMERCE_BLOCKS_PHASE || 3 }`,
			} ),
		],
	};
};

const getMainConfig = ( options = {} ) => {
	let { fileSuffix } = options;
	const { alias, resolvePlugins = [] } = options;
	const isLegacy = fileSuffix === 'legacy';
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
				cacheGroups: {
					commons: {
						test: /[\\/]node_modules[\\/]/,
						name: 'vendors',
						chunks: 'all',
						enforce: true,
					},
					editor: {
						// Capture all `editor` stylesheets and the components stylesheets.
						test: ( module = {} ) =>
							module.constructor.name === 'CssModule' &&
							( findModuleMatch( module, /editor\.scss$/ ) ||
								findModuleMatch(
									module,
									/[\\/]assets[\\/]components[\\/]/
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
						test: /style\.scss$/,
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
					test: /\.jsx?$/,
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
					test: /\/node_modules\/.*?style\.s?css$/,
					use: [
						MiniCssExtractPlugin.loader,
						{ loader: 'css-loader', options: { importLoaders: 1 } },
						'postcss-loader',
						{
							loader: 'sass-loader',
							query: {
								includePaths: [ 'node_modules' ],
								data: [
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
									.join( ' ' ),
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
							query: {
								includePaths: [
									'assets/css/abstracts',
									'node_modules',
								],
								data: [
									'_colors',
									'_variables',
									'_breakpoints',
									'_mixins',
								]
									.map(
										( imported ) =>
											`@import "${ imported }";`
									)
									.join( ' ' ),
							},
						},
					],
				},
			],
		},
		plugins: [
			new WebpackRTLPlugin( {
				filename: `[name]${ fileSuffix }-rtl.css`,
				minify: {
					safe: true,
				},
			} ),
			new MiniCssExtractPlugin( {
				filename: `[name]${ fileSuffix }.css`,
			} ),
			new MergeExtractFilesPlugin(
				[
					`build/editor${ fileSuffix }.js`,
					`build/style${ fileSuffix }.js`,
				],
				`build/vendors${ fileSuffix }.js`
			),
			new ProgressBarPlugin( {
				format:
					chalk.blue( 'Building Main' ) +
					' [:bar] ' +
					chalk.green( ':percent' ) +
					' :msg (:elapsed seconds)',
				summary: false,
				customSummary: ( time ) => {
					const prefix = isLegacy ? 'Legacy' : '';
					console.log(
						chalk.green.bold(
							`${ prefix } Main assets build completed (${ time })`
						)
					);
				},
			} ),
			new DependencyExtractionWebpackPlugin( {
				injectPolyfill: true,
				requestToExternal,
				requestToHandle,
			} ),
			new NormalModuleReplacementPlugin(
				/dashicon/,
				( result ) => ( result.resource = dashIconReplacementModule )
			),
		],
		resolve,
	};
};

const getFrontConfig = ( options = {} ) => {
	let { fileSuffix } = options;
	const { alias, resolvePlugins = [] } = options;
	const isLegacy = fileSuffix === 'legacy';
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
		module: {
			rules: [
				{
					test: /\.jsx?$/,
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
			new ProgressBarPlugin( {
				format:
					chalk.blue( 'Building Frontend' ) +
					' [:bar] ' +
					chalk.green( ':percent' ) +
					' :msg (:elapsed seconds)',
				summary: false,
				customSummary: ( time ) => {
					const prefix = isLegacy ? 'Legacy' : '';
					console.log(
						chalk.green.bold(
							`${ prefix } Frontend assets build completed (${ time })`
						)
					);
				},
			} ),
			new DependencyExtractionWebpackPlugin( {
				injectPolyfill: true,
				requestToExternal,
				requestToHandle,
			} ),
			new NormalModuleReplacementPlugin(
				/dashicon/,
				( result ) => ( result.resource = dashIconReplacementModule )
			),
		],
		resolve,
	};
};

const getPaymentsConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const isLegacy = options.fileSuffix && options.fileSuffix === 'legacy';
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
					test: /\.jsx?$/,
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
					test: /\.s?css$/,
					exclude: /node_modules/,
					use: [
						MiniCssExtractPlugin.loader,
						{ loader: 'css-loader', options: { importLoaders: 1 } },
						'postcss-loader',
						{
							loader: 'sass-loader',
							query: {
								includePaths: [
									'assets/css/abstracts',
									'node_modules',
								],
								data: [
									'_colors',
									'_variables',
									'_breakpoints',
									'_mixins',
								]
									.map(
										( imported ) =>
											`@import "${ imported }";`
									)
									.join( ' ' ),
							},
						},
					],
				},
			],
		},
		plugins: [
			new WebpackRTLPlugin( {
				filename: `[name]-rtl.css`,
				minify: {
					safe: true,
				},
			} ),
			new MiniCssExtractPlugin( {
				filename: `[name].css`,
			} ),
			new ProgressBarPlugin( {
				format:
					chalk.blue( 'Building Payment Method Extensions' ) +
					' [:bar] ' +
					chalk.green( ':percent' ) +
					' :msg (:elapsed seconds)',
				summary: false,
				customSummary: ( time ) => {
					const prefix = isLegacy ? 'Legacy' : '';
					console.log(
						chalk.green.bold(
							`${ prefix } Payment Method Extension assets build completed (${ time })`
						)
					);
				},
			} ),
			new DependencyExtractionWebpackPlugin( {
				injectPolyfill: true,
				requestToExternal,
				requestToHandle,
			} ),
			new NormalModuleReplacementPlugin(
				/dashicon/,
				( result ) => ( result.resource = dashIconReplacementModule )
			),
		],
		resolve,
	};
};

module.exports = {
	getCoreConfig,
	getFrontConfig,
	getMainConfig,
	getPaymentsConfig,
};
