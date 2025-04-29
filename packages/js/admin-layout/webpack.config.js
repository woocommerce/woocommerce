/**
 * Internal dependencies
 */
const path = require('path');
const { webpackConfig } = require('@woocommerce/internal-style-build');

module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: {
		'build-style': path.resolve(__dirname, 'src/style.scss'),
	},
	output: {
		path: __dirname,
	},
	module: {
		rules: webpackConfig.rules,
		parser: webpackConfig.parser,
	},
	plugins: webpackConfig.plugins,
};
