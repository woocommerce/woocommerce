/**
 * External dependencies
 */
const TerserPlugin = require( 'terser-webpack-plugin' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const isProduction = NODE_ENV === 'production';

const sharedOptimizationConfig = {
	concatenateModules: isProduction && ! process.env.WP_BUNDLE_ANALYZER,
	minimizer: [
		new TerserPlugin( {
			parallel: true,
			terserOptions: {
				output: {
					comments: /translators:/i,
				},
				compress: {
					passes: 2,
				},
				mangle: {
					reserved: [ '__', '_n', '_nx', '_x' ],
				},
			},
			extractComments: false,
		} ),
	],
};

module.exports = {
	sharedOptimizationConfig,
};
