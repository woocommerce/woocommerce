/**
 * Internal dependencies
 */
const { webpackConfig } = require( '@woocommerce/internal-style-build' );

/**
 * External dependencies
 */
const path = require( 'path' );

//const packagesPathForRegex = path.resolve( __dirname, '..' ).replaceAll( /\W/g, '.' );
// const watchIgnoreRegex = `^(?!(${ packagesPathForRegex }.((packages.js.components.src)|(packages.js.internal-style-build.abstracts)|(node_modules..pnpm.(@automattic|@wordpress))))).*`;

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	mode: NODE_ENV,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../../node_modules/.cache/webpack-components'
		),
	},
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
	// watchOptions: { ignored: new RegExp( `^(?!(${ packagesPathForRegex }.((components.src)|(internal-style-build.abstracts)))).*` ), },
};
