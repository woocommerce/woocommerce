/**
 * Internal dependencies
 */
const { webpackConfig } = require( '@woocommerce/style-build' );

module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: {
		'build-style': __dirname + '/src/style.scss',
	},
	output: {
		path: __dirname,
	},
	module: {
		rules: webpackConfig.rules,
	},
	plugins: webpackConfig.plugins,
};
