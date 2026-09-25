module.exports = ( { env } ) => ( {
	plugins: {
		'@wordpress/theme/postcss-plugins/postcss-ds-token-fallbacks': {},
		autoprefixer: { grid: true },
		cssnano: env === 'production',
	},
} );
