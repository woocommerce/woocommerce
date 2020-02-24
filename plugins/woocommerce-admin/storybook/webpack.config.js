/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

/**
 * External dependencies
 */
const wcAdminWebpackConfig = require( '../webpack.config.js' );

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

	storybookConfig.plugins.push(
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} )
	);

	return storybookConfig;
};
