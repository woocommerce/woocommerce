/**
 * External dependencies
 */
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );

/**
 * Internal dependencies
 */
const {
	webpackConfig,
	plugin,
} = require( '@woocommerce/internal-style-build' );

const NODE_ENV = process.env.NODE_ENV || 'development';

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
	plugins: [
		new plugin( {
			filename: ( data ) => {
				return data.chunk.name.startsWith( '/build/blocks' )
					? `[name].css`
					: `[name]/style.css`;
			},
			chunkFilename: 'chunks/[id].style.css',
		} ),
		new WebpackRTLPlugin( {
			test: /(?<!style)\.css$/,
			filename: '[name]-rtl.css',
			minify: NODE_ENV === 'development' ? false : { safe: true },
		} ),
		new WebpackRTLPlugin( {
			test: /style\.css$/,
			filename: '[name]/style-rtl.css',
			minify: NODE_ENV === 'development' ? false : { safe: true },
		} ),
	],
};
