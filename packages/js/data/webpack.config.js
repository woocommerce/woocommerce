/* eslint-disable @typescript-eslint/no-var-requires */
const path = require('path');
const ForkTsCheckerWebpackPlugin = require('fork-ts-checker-webpack-plugin');

module.exports = {
	context: __dirname, // to automatically find tsconfig.json
	output: {
		path: path.resolve(__dirname, 'build'),
	},
	resolve: {
		extensions: ['.tsx', '.ts', '.js'],
		extensionAlias: {
			'.ts': ['.js', '.ts'],
			'.cts': ['.cjs', '.cts'],
			'.mts': ['.mjs', '.mts'],
		},
	},
	module: {
		rules: [
			{
				test: /.([cm]?ts|tsx)$/,
				loader: 'ts-loader',
				options: {
					transpileOnly: true
				}
			},
		],
	},
	plugins: [
		new ForkTsCheckerWebpackPlugin(),
	],
};
