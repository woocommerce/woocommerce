/* eslint-disable @typescript-eslint/no-require-imports -- postcss-loader loads this config as CommonJS. */
const autoprefixer = require( 'autoprefixer' );
const cssnano = require( 'cssnano' );

module.exports = ( { env } ) => ( {
	plugins: [
		'@wordpress/theme/postcss-plugins/postcss-ds-token-fallbacks',
		autoprefixer( { grid: true } ),
		...( env === 'production' ? [ cssnano() ] : [] ),
	],
} );
