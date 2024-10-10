/**
 * Internal dependencies
 */
const { webpackConfig, ForkTsCheckerWebpackPlugin, TypeScriptWarnOnlyWebpackPlugin } = require( '@woocommerce/internal-style-build' );
const path = require("path");

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
		rules: [
			...webpackConfig.rules,
			{
				test: /\.tsx?$/,
				loader: 'ts-loader',
				options: {
					transpileOnly: true
				},
				include: [ path.resolve( __dirname, './src/' ) ],
			},
		],
	},
	plugins: [
		...webpackConfig.plugins,
		new ForkTsCheckerWebpackPlugin(),
		new TypeScriptWarnOnlyWebpackPlugin(),
	],
};
