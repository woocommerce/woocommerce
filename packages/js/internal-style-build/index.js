/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );
const StyleAssetPlugin = require( './style-asset-plugin' );

/**
 * @deprecated intended for React version migration and can be dropped from exports any time without further notice.
 */
const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );

/**
 * https://github.com/woocommerce/woocommerce/pull/47486: do not break build if TS emits errors - React 18 migration related.
 * The solution is based on https://github.com/TypeStrong/fork-ts-checker-webpack-plugin/issues/232#issuecomment-1322651312.
 *
 * @deprecated intended for React version migration and can be dropped from exports any time without further notice.
 */
class TypeScriptWarnOnlyWebpackPlugin {
	constructor( error_codes ) {
		this.error_codes = error_codes;
	}

	apply( compiler ) {
		const codes = this.error_codes;
		const hooks = ForkTsCheckerWebpackPlugin.getCompilerHooks( compiler );
		hooks.issues.tap( 'TypeScriptWarnOnlyWebpackPlugin', ( issues ) =>
			issues.map( function ( issue ) {
				const mutate =
					issue.severity === 'error' && codes.includes( issue.code );
				return {
					...issue,
					severity: mutate ? 'warning' : issue.severity,
				};
			} )
		);
	}
}

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	plugin: MiniCssExtractPlugin,
	webpackConfig: {
		parser: {
			javascript: {
				exportsPresence: 'error',
			},
		},
		rules: [
			{
				test: /\.s?css$/,
				exclude: [
					/storybook\/wordpress/,
					/build-style\/*\/*.css/,
					/[\/\\](changelog|bin|docs|build|build-module|build-types|build-style|vendor|tests|test)[\/\\]/,
				],
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					{
						loader: 'postcss-loader',
						options: {
							postcssOptions: {
								plugins: postcssPlugins,
							},
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sassOptions: {
								includePaths: [
									path.resolve( __dirname, 'abstracts' ),
								],
							},
							webpackImporter: true,
							additionalData: ( content, loaderContext ) => {
								const { resourcePath } = loaderContext;
								if ( resourcePath.includes( '@automattic+' ) ) {
									/*
									 * Skip adding additional data for @automattic/* packages to
									 * fix "SassError: @use rules must be written before any other rules."
									 * @automattic/* packages have included '@use "sass:math" and other necessary imports.
									 */
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
		plugins: [
			new RemoveEmptyScriptsPlugin(),
			new MiniCssExtractPlugin( {
				filename: '[name]/style.css',
				chunkFilename: 'chunks/[id].style.css?ver=[contenthash]',
			} ),
			new WebpackRTLPlugin( {
				filename: '[name]/style-rtl.css',
				minify: NODE_ENV === 'development' ? false : { safe: true },
			} ),
			new StyleAssetPlugin(),
		],
	},
	StyleAssetPlugin,
	ForkTsCheckerWebpackPlugin,
	TypeScriptWarnOnlyWebpackPlugin,
};
