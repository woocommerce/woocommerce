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
const WooCommerceDependencyExtractionWebpackPlugin = require( '../../packages/js/dependency-extraction-webpack-plugin/src/index' );

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
	entryPoints[ name ] = `../../packages/js/${ name }`;
} );
// wpAdminScripts are loaded on wp-admin pages outside the context of WooCommerce Admin
// See ./client/wp-admin-scripts/README.md for more details
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
			// Output wpAdminScripts to wp-admin-scripts folder
			// See https://github.com/woocommerce/woocommerce-admin/pull/3061
			return wpAdminScripts.includes( data.chunk.name )
				? `wp-admin-scripts/[name]${ suffix }.js`
				: `[name]/index${ suffix }.js`;
		},
		chunkFilename: `chunks/[name]${ suffix }.js`,
		path: path.join( __dirname, '/../woocommerce/assets/client/admin' ),
		// Expose the exports of entry points so we can consume the libraries in window.wc.[modulename] with WooCommerceDependencyExtractionWebpackPlugin.
		library: [ 'wc', '[modulename]' ],
		libraryTarget: 'window',
		// A unique name of the webpack build to avoid multiple webpack runtimes to conflict when using globals.
		uniqueName: '__wcAdmin_webpackJsonp',
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				parser: {
					// Disable AMD to fix an issue where underscore and lodash where clashing
					// See https://github.com/woocommerce/woocommerce-admin/pull/1004 and https://github.com/Automattic/woocommerce-services/pull/1522
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
									// Add polyfills such as Array.flat based on their usage in the code
									// See https://github.com/woocommerce/woocommerce-admin/pull/6411/
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
		fallback: {
			// Reduce bundle size by omitting Node crypto library.
			// See https://github.com/woocommerce/woocommerce-admin/pull/5768
			crypto: 'empty',
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
		// Runs TypeScript type checker on a separate process.
		new ForkTsCheckerWebpackPlugin(),
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
		// The package build process doesn't handle extracting CSS from JS files, so we copy them separately.
		new CopyWebpackPlugin( {
			patterns: wcAdminPackages.map( ( packageName ) => ( {
				from: `../../packages/js/${ packageName }/build-style/*.css`,
				to: `./${ packageName }/[name][ext]`,
				noErrorOnMissing: true,
			} ) ),
		} ),

		// We reuse this Webpack setup for Storybook, where we need to disable dependency extraction.
		! process.env.STORYBOOK &&
			new WooCommerceDependencyExtractionWebpackPlugin(),
		// Reduces data for moment-timezone.
		new MomentTimezoneDataPlugin( {
			// This strips out timezone data before the year 2000 to make a smaller file.
			startYear: 2000,
		} ),
		process.env.ANALYZE && new BundleAnalyzerPlugin(),
		// Adds the script version parameter to the chunk URLs for cache busting
		// TODO: Partially replace with __webpack_get_script_filename__ in app with Webpack 5.x.
		// The CSS chunk portion will need to remain, as it originates in MiniCssExtractPlugin.
		new AsyncChunkSrcVersionParameterPlugin(),
		// Generate unminified files to load the unminified version when `define( 'SCRIPT_DEBUG', true );` is set in wp-config.
		// This is also required to publish human readeable code in the deployed "plugin".
		// See https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable
		WC_ADMIN_PHASE !== 'core' &&
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

if ( webpackConfig.mode !== 'production' && WC_ADMIN_PHASE !== 'core' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
