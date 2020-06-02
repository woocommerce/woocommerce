/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );

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
];

module.exports = ( { config: storybookConfig } ) => {
	storybookConfig.module.rules.push(
		{
			test: /\/stories\/.+\.js$/,
			loaders: [ require.resolve( '@storybook/source-loader' ) ],
			enforce: 'pre',
		},
		...wcAdminWebpackConfig.module.rules
	);

	storybookConfig.resolve.alias = wcAdminWebpackConfig.resolve.alias;

	wcAdminPackages.forEach( ( name ) => {
		storybookConfig.resolve.alias[
			`@woocommerce/${ name }`
		] = path.resolve( __dirname, `../packages/${ name }/src` );
	} );

	storybookConfig.plugins.push(
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} )
	);

	return storybookConfig;
};
