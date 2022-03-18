/**
 * External dependencies
 */
const { get } = require( 'lodash' );
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const CustomTemplatedPathPlugin = require( '@wordpress/custom-templated-path-webpack-plugin' );
const BundleAnalyzerPlugin = require( 'webpack-bundle-analyzer' )
	.BundleAnalyzerPlugin;
const MomentTimezoneDataPlugin = require( 'moment-timezone-data-webpack-plugin' );
const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );

/**
 * Internal dependencies
 */
const AsyncChunkSrcVersionParameterPlugin = require( './chunk-src-version-param' );
const UnminifyWebpackPlugin = require( './unminify' );
const { webpackConfig: styleConfig } = require( '@woocommerce/style-build' );
const WooCommerceDependencyExtractionWebpackPlugin = require( './packages/dependency-extraction-webpack-plugin/src/index' );

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
	'onboarding',
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
	'payment-method-promotions',
];
wpAdminScripts.forEach( ( name ) => {
	entryPoints[ name ] = `./client/wp-admin-scripts/${ name }`;
} );

const suffix = WC_ADMIN_PHASE === 'core' ? '' : '.min';

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		app: './client/index.js',
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
		libraryTarget: 'window',
		uniqueName: '__wcAdmin_webpackJsonp',
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				parser: {
					amd: false,
				},
			},
			{
				test: /\.(t|j)sx?$/,
				exclude: [
					// Exclude node_modules/ but not node_modules/debug* and node_modules/explat-client-react-helpers
					// explat-client-react-helpers module contains optional chaining operators which need to be processed via babel loader for webpack 4.
					// see webpack issue for details: https://github.com/webpack/webpack/issues/10227#issue-547480527
					/node_modules(\/|\\)\.pnpm(\/|\\)(?!(debug|\@automattic\+explat-client-react-helpers))/,
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
			...styleConfig.rules,
		],
	},
	resolve: {
		fallback:{
			'crypto': 'empty'
		},
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
		alias: {
			'~': path.resolve( __dirname + '/client' ),
			'gutenberg-components': path.resolve(
				__dirname,
				'node_modules/@wordpress/components/src'
			),
		},
	},
	plugins: [
		...styleConfig.plugins,
		new ForkTsCheckerWebpackPlugin(),
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
		new CopyWebpackPlugin({

			patterns: wcAdminPackages.map( ( packageName ) => ( {
				from: `./packages/${ packageName }/build-style/*.css`,
				to: `./${ packageName }/[name][ext]`,
				noErrorOnMissing: true
			} ) )
		}
		),

		// We reuse this Webpack setup for Storybook, where we need to disable dependency extraction.
		! process.env.STORYBOOK &&
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
		splitChunks: {
			name: false
		}
	},
};

if ( webpackConfig.mode !== 'production' && WC_ADMIN_PHASE !== 'core' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
