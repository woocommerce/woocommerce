/**
 * Internal dependencies
 */
const { webpackConfig } = require( '@woocommerce/internal-style-build' );

module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: {
		'build-style': __dirname + '/src/style.scss',
	},
	output: {
		path: __dirname,
	},
	module: {
		parser: webpackConfig.parser,
		rules: webpackConfig.rules,
	},
	plugins: webpackConfig.plugins,
};
