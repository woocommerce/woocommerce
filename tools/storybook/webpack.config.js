/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

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

	storybookConfig.resolve.modules = [
		path.join( __dirname, '../../plugins/woocommerce-admin/client' ),
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
						`../../packages/js/experimental/build-style/*.css`
					),
					to: `./experimental-css/[name][ext]`,
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
