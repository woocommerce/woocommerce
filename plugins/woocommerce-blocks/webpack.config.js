/**
 * Config for compiling Gutenberg blocks JS.
 */
var GutenbergBlocksConfig = {
	entry: {
		'products-block': './assets/js/products-block.jsx',
		// 'next-block-name': './assets/js/gutenberg/some-other-block.jsx', <-- How to add more gutenblocks to this.
	},
	output: {
		path: __dirname + '/assets/js/',
		filename: '[name].js',
	},
	module: {
		loaders: [
			{
				test: /.jsx$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
		],
	},
};

module.exports = [ GutenbergBlocksConfig ];