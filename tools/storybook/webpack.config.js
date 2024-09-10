/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const webpack = require( 'webpack' );

/**
 * External dependencies
 */
const wcAdminWebpackConfig = require( '../../plugins/woocommerce-admin/webpack.config.js' );

const wcAdminPackages = [
	'components',
	'csv-export',
	'currency',
	'date',
	'navigation',
	'number',
	'data',
	'tracks',
	'experimental',
];

module.exports = ( storybookConfig ) => {
	storybookConfig.module.rules = [
		...storybookConfig.module.rules,
		...wcAdminWebpackConfig.module.rules,
	];

	storybookConfig.resolve.alias = wcAdminWebpackConfig.resolve.alias;

	wcAdminPackages.forEach( ( name ) => {
		storybookConfig.resolve.alias[ `@woocommerce/${ name }` ] =
			path.resolve( __dirname, `../../packages/js/${ name }/src` );
	} );

	storybookConfig.resolve.alias[ '@woocommerce/settings' ] = path.resolve(
		__dirname,
		'./setting.mock.js'
	);

	// We need to use react 18 for the storybook since some dependencies are not compatible with react 17
	// Once we upgrade react to 18 in repo, we can remove this alias
	storybookConfig.resolve.alias.react = path.resolve(
		__dirname,
		'./node_modules/react'
	);
	storybookConfig.resolve.alias[ 'react-dom' ] = path.resolve(
		__dirname,
		'./node_modules/react-dom'
	);
	storybookConfig.resolve.alias[ '@storybook/react-dom-shim' ] =
		'@storybook/react-dom-shim/dist/react-18';

	storybookConfig.resolve.modules = [
		path.join( __dirname, '../../plugins/woocommerce-admin/client' ),
		path.join( __dirname, '../../packages/js/product-editor/src' ),
		'node_modules',
	];

	storybookConfig.plugins.push(
		...wcAdminWebpackConfig.plugins,
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: path.resolve( __dirname, 'wordpress/css' ),
					to: 'wordpress/css/[name][ext]',
				},
				{
					from: require.resolve(
						'@wordpress/components/build-style/style.css'
					),
					to: 'wordpress/css/components.css',
				},
				{
					from: path.resolve(
						__dirname,
						`../../packages/js/components/build-style/*.css`
					),
					to: `./component-css/[name][ext]`,
				},
				{
					from: path.resolve(
						__dirname,
						`../../packages/js/onboarding/build-style/*.css`
					),
					to: `./onboarding-css/[name][ext]`,
				},
				{
					from: path.resolve(
						__dirname,
						`../../packages/js/product-editor/build-style/*.css`
					),
					to: `./product-editor-css/[name][ext]`,
				},
				{
					from: path.resolve(
						__dirname,
						`../../packages/js/experimental/build-style/*.css`
					),
					to: `./experimental-css/[name][ext]`,
				},
				{
					from: path.resolve(
						__dirname,
						`../../plugins/woocommerce/assets/client/admin/app/*.css`
					),
					to: `./app-css/[name][ext]`,
				},
			],
		} )
	);

	storybookConfig.resolve.fallback = {
		...storybookConfig.resolve.fallback,
		// Ignore fs to fix resolve 'fs' error for @automattic/calypso-config
		fs: false,
	};

	return storybookConfig;
};
