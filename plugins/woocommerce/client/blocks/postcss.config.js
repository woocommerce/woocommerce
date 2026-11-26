/* eslint-disable @typescript-eslint/no-require-imports -- postcss-loader loads this config as CommonJS. */
const {
	default: tokenFallbacks,
} = require( '@wordpress/theme/postcss-plugins/postcss-ds-token-fallbacks' );
const autoprefixer = require( 'autoprefixer' );
const cssnano = require( 'cssnano' );

module.exports = ( { env } ) => ( {
	plugins: [
		tokenFallbacks(),
		autoprefixer( { grid: true } ),
		...( env === 'production' ? [ cssnano() ] : [] ),
	],
} );
