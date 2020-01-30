/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

/**
 * Internal dependencies
 */
const { getAlias, getMainConfig } = require( '../bin/webpack-helpers.js' );

module.exports = ( { config: storybookConfig } ) => {
	const wooBlocksConfig = getMainConfig( { alias: getAlias() } );

	storybookConfig.resolve.alias = {
		...storybookConfig.resolve.alias,
		...wooBlocksConfig.resolve.alias,
	};
	storybookConfig.module.rules.push(
		{
			test: /\/stories\/.+\.js$/,
			loaders: [ require.resolve( '@storybook/source-loader' ) ],
			enforce: 'pre',
		},
		...wooBlocksConfig.module.rules
	);

	storybookConfig.plugins.push(
		new MiniCssExtractPlugin( {
			filename: `[name].css`,
		} )
	);

	return storybookConfig;
};
