/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( '@automattic/mini-css-extract-plugin-with-rtl' );
const { get } = require( 'lodash' );
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const FixStyleOnlyEntriesPlugin = require( 'webpack-fix-style-only-entries' );
const BundleAnalyzerPlugin = require( 'webpack-bundle-analyzer' )
	.BundleAnalyzerPlugin;
const MomentTimezoneDataPlugin = require( 'moment-timezone-data-webpack-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const UnminifyWebpackPlugin = require( './unminify' );
const AsyncChunkSrcVersionParameterPlugin = require( './chunk-src-version-param' );
const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );
const WooCommerceDependencyExtractionWebpackPlugin = require( './packages/dependency-extraction-webpack-plugin/src/index' );

/**
 * External dependencies
 */
const CustomTemplatedPathPlugin = require( '@wordpress/custom-templated-path-webpack-plugin' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const WC_ADMIN_PHASE = process.env.WC_ADMIN_PHASE || 'development';

const wcAdminPackages = [
	'components',
	'csv-export',
	'currency',
	'customer-effort-score',
	'date',
	'experimental',
	'explat',
	'navigation',
	'notices',
	'number',
	'data',
	'tracks',
];

const entryPoints = {};
wcAdminPackages.forEach( ( name ) => {
	entryPoints[ name ] = `./packages/${ name }`;
} );

const wpAdminScripts = [
	'marketing-coupons',
	'navigation-opt-out',
	'onboarding-homepage-notice',
	'onboarding-product-notice',
	'onboarding-product-import-notice',
	'onboarding-tax-notice',
	'print-shipping-label-banner',
	'beta-features-tracking-modal',
];
wpAdminScripts.forEach( ( name ) => {
	entryPoints[ name ] = `./client/wp-admin-scripts/${ name }`;
} );

const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );

const suffix = WC_ADMIN_PHASE === 'core' ? '' : '.min';

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		app: './client/index.js',
		ie: './client/ie.scss',
		...entryPoints,
	},
	output: {
		filename: ( data ) => {
			return wpAdminScripts.includes( data.chunk.name )
				? `wp-admin-scripts/[name]${ suffix }.js`
				: `[name]/index${ suffix }.js`;
		},
		chunkFilename: `chunks/[name]${ suffix }.js`,
		path: path.join( __dirname, 'dist' ),
		library: [ 'wc', '[modulename]' ],
		libraryTarget: 'this',
		jsonpFunction: '__wcAdmin_webpackJsonp',
	},
	module: {
		rules: [
			{
				parser: {
					amd: false,
				},
			},
			{
				test: /\.(t|j)sx?$/,
				exclude: [
					/node_modules(\/|\\)(?!(debug))/,
					/build/,
					/build-module/,
				],
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@wordpress/babel-preset-default',
							[
								'@babel/preset-env',
								{
									corejs: '3',
									useBuiltIns: 'usage',
								},
							],
							[ '@babel/preset-typescript' ],
						],
					},
				},
			},
			{ test: /\.md$/, use: 'raw-loader' },
			{
				test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/,
				loader: 'url-loader',
			},
			{
				test: /\.s?css$/,
				exclude: /storybook\/wordpress/,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					{
						loader: 'postcss-loader',
						options: {
							ident: 'postcss',
							plugins: postcssPlugins,
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sassOptions: {
								includePaths: [
									'client/stylesheets/abstracts',
								],
							},
							prependData:
								'@import "_colors"; ' +
								'@import "_variables"; ' +
								'@import "_breakpoints"; ' +
								'@import "_mixins"; ',
						},
					},
				],
			},
		],
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
		alias: {
			'~': path.resolve( __dirname + '/client' ),
			'gutenberg-components': path.resolve(
				__dirname,
				'node_modules/@wordpress/components/src'
			),
			'@woocommerce/wc-admin-settings': path.resolve(
				__dirname,
				'client/wc-admin-settings/index.js'
			),
		},
	},
	plugins: [
		new ForkTsCheckerWebpackPlugin(),
		new FixStyleOnlyEntriesPlugin(),
		new CustomTemplatedPathPlugin( {
			modulename( outputPath, data ) {
				const entryName = get( data, [ 'chunk', 'name' ] );
				if ( entryName ) {
					return entryName.replace( /-([a-z])/g, ( match, letter ) =>
						letter.toUpperCase()
					);
				}
				return outputPath;
			},
		} ),
		new WebpackRTLPlugin( {
			minify: {
				safe: true,
			},
		} ),
		new MiniCssExtractPlugin( {
			filename: './[name]/style.css',
			chunkFilename: './chunks/[id].style.css',
			rtlEnabled: true,
		} ),
		new CopyWebpackPlugin(
			wcAdminPackages.map( ( packageName ) => ( {
				from: `./packages/${ packageName }/build-style/*.css`,
				to: `./${ packageName }/`,
				flatten: true,
				transform: ( content ) => content,
			} ) )
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
		new MomentTimezoneDataPlugin( {
			startYear: 2000, // This strips out timezone data before the year 2000 to make a smaller file.
		} ),
		process.env.ANALYZE && new BundleAnalyzerPlugin(),
		// Partially replace with __webpack_get_script_filename__ in app once using Webpack 5.x.
		// The CSS chunk portion will need to remain, as it originates in MiniCssExtractPlugin.
		new AsyncChunkSrcVersionParameterPlugin(),
		WC_ADMIN_PHASE !== 'core' &&
			new UnminifyWebpackPlugin( {
				test: /\.js($|\?)/i,
				mainEntry: 'app/index.min.js',
			} ),
	].filter( Boolean ),
	optimization: {
		minimize: NODE_ENV !== 'development',
		minimizer: [ new TerserPlugin() ],
		splitChunks: {
			name: false,
		},
	},
	node: {
		crypto: 'empty',
	},
};

if ( webpackConfig.mode !== 'production' && WC_ADMIN_PHASE !== 'core' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
