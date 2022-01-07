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
	storybookConfig.module.rules.push( ...wcAdminWebpackConfig.module.rules );

	storybookConfig.resolve.alias = wcAdminWebpackConfig.resolve.alias;

	wcAdminPackages.forEach( ( name ) => {
		storybookConfig.resolve.alias[
			`@woocommerce/${ name }`
		] = path.resolve( __dirname, `../packages/${ name }/src` );
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
		new CopyWebpackPlugin( [
			{
				from: path.resolve( __dirname, 'wordpress/css' ),
				to: 'wordpress/css',
			},
			{
				from: path.resolve(
					__dirname,
					`../packages/components/build-style/*.css`
				),
				to: `./component-css`,
				flatten: true,
			},
			{
				from: path.resolve(
					__dirname,
					`../packages/experimental/build-style/*.css`
				),
				to: `./experimental-css`,
				flatten: true,
			},
		] )
	);

	return storybookConfig;
};
