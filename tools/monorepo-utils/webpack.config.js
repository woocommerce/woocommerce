const path = require( 'path' );
const CopyPlugin = require( 'copy-webpack-plugin' );

const buildMode = process.env.NODE_ENV || 'production';

module.exports = {
	entry: './src/index.ts',
	target: 'node',
	mode: buildMode,
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/,
			},
		],
	},
	resolve: {
		extensions: [ '.tsx', '.ts', '.js' ],
	},
	plugins: [
		new CopyPlugin( {
			patterns: [
				{ from: 'node_modules/figlet/fonts/Standard.flf', to: '../fonts/Standard.flf' },
			],
		} ),
	],
	output: {
		filename: 'index.js',
		path: path.resolve( __dirname, 'dist' ),
		clean: true,
	},
};