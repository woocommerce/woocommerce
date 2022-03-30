/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

/**
 * External dependencies
 */
const wcAdminWebpackConfig = require( '../webpack.config.js' );

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
		// We need to expose packages in "peerDependencies" to the global scope for @woocommerce/* to resolve packages.
		{
			test: require.resolve( 'moment' ),
			loader: 'expose-loader',
			options: {
				exposes: [ 'moment' ],
			},
		},
		{
			test: require.resolve( '@wordpress/data' ),
			loader: 'expose-loader',
			options: {
				exposes: [ '_wp_data' ],
			},
		},
	];

	storybookConfig.resolve.alias = wcAdminWebpackConfig.resolve.alias;

	wcAdminPackages.forEach( ( name ) => {
		storybookConfig.resolve.alias[
			`@woocommerce/${ name }`
		] = path.resolve( __dirname, `../../../packages/js/${ name }/src` );
	} );

	storybookConfig.resolve.alias[ '@woocommerce/settings' ] = path.resolve(
		__dirname,
		'./setting.mock.js'
	);

	storybookConfig.resolve.modules = [
		path.join( __dirname, '../client' ),
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
					from: path.resolve(
						__dirname,
						`../../../packages/js/components/build-style/*.css`
					),
					to: `./component-css/[name][ext]`,
				},
				{
					from: path.resolve(
						__dirname,
						`../../../packages/js/experimental/build-style/*.css`
					),
					to: `./experimental-css/[name][ext]`,
				},
			],
		} )
	);

	storybookConfig.externals = {
		'@wordpress/data': '_wp_data',
		moment: 'moment',
	};

	return storybookConfig;
};
