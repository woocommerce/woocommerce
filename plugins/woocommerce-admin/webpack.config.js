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

/**
 * WordPress dependencies
 */
const CustomTemplatedPathPlugin = require( '@wordpress/custom-templated-path-webpack-plugin' );

const NODE_ENV = process.env.NODE_ENV || 'development';

const externals = {
	'@wordpress/api-fetch': { this: [ 'wp', 'apiFetch' ] },
	'@wordpress/blocks': { this: [ 'wp', 'blocks' ] },
	'@wordpress/data': { this: [ 'wp', 'data' ] },
	'@wordpress/editor': { this: [ 'wp', 'editor' ] },
	'@wordpress/element': { this: [ 'wp', 'element' ] },
	'@wordpress/hooks': { this: [ 'wp', 'hooks' ] },
	'@wordpress/url': { this: [ 'wp', 'url' ] },
	'@wordpress/html-entities': { this: [ 'wp', 'htmlEntities' ] },
	'@wordpress/i18n': { this: [ 'wp', 'i18n' ] },
	'@wordpress/data-controls': { this: [ 'wp', 'dataControls' ] },
	tinymce: 'tinymce',
	moment: 'moment',
	react: 'React',
	lodash: 'lodash',
	'react-dom': 'ReactDOM',
};

const wcAdminPackages = [
	'components',
	'csv-export',
	'currency',
	'date',
	'navigation',
	'number',
	'data',
];

const entryPoints = {};
wcAdminPackages.forEach( ( name ) => {
	externals[ `@woocommerce/${ name }` ] = {
		this: [
			'wc',
			name.replace( /-([a-z])/g, ( match, letter ) =>
				letter.toUpperCase()
			),
		],
	};
	entryPoints[ name ] = `./packages/${ name }`;
} );

const wpAdminScripts = [
	'marketing-coupons',
	'onboarding-homepage-notice',
	'onboarding-product-notice',
	'onboarding-product-import-notice',
	'onboarding-tax-notice',
	'print-shipping-label-banner',
];
wpAdminScripts.forEach( ( name ) => {
	entryPoints[ name ] = `./client/wp-admin-scripts/${ name }`;
} );

const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );

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
				? `wp-admin-scripts/[name].min.js`
				: `[name]/index.min.js`;
		},
		chunkFilename: `chunks/[id].[chunkhash].min.js`,
		path: path.join( __dirname, 'dist' ),
		library: [ 'wc', '[modulename]' ],
		libraryTarget: 'this',
	},
	externals,
	module: {
		rules: [
			{
				parser: {
					amd: false,
				},
			},
			{
				test: /\.jsx?$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.js?$/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							[
								'@babel/preset-env',
								{ loose: true, modules: 'commonjs' },
							],
						],
						plugins: [ 'transform-es2015-template-literals' ],
					},
				},
				include: new RegExp(
					'/node_modules/(' +
						'|acorn-jsx' +
						'|d3-array' +
						'|debug' +
						'|marked' +
						'|regexpu-core' +
						'|unicode-match-property-ecmascript' +
						'|unicode-match-property-value-ecmascript)/'
				),
			},
			{ test: /\.md$/, use: 'raw-loader' },
			{
				test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/,
				loader: 'url-loader',
			},
			{
				test: /\.s?css$/,
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
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [ path.join( __dirname, 'client' ), 'node_modules' ],
		alias: {
			'gutenberg-components': path.resolve(
				__dirname,
				'node_modules/@wordpress/components/src'
			),
			// @todo - remove once https://github.com/WordPress/gutenberg/pull/16196 is released.
			'react-spring': 'react-spring/web.cjs',
			'@woocommerce/wc-admin-settings': path.resolve(
				__dirname,
				'client/settings/index.js'
			),
		},
	},
	plugins: [
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
		new MomentTimezoneDataPlugin( {
			startYear: 2000, // This strips out timezone data before the year 2000 to make a smaller file.
		} ),
		process.env.ANALYZE && new BundleAnalyzerPlugin(),
		new UnminifyWebpackPlugin( {
			test: /\.js($|\?)/i,
			mainEntry: 'app/index.min.js',
		} ),
	].filter( Boolean ),
	optimization: {
		minimize: NODE_ENV !== 'development',
		minimizer: [ new TerserPlugin() ],
	},
};

if ( webpackConfig.mode !== 'production' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
