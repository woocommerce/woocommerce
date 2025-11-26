module.exports = ( { env } ) => ( {
	plugins: {
		// Note: autoprefixer removed - modern browsers (per @wordpress/browserslist-config)
		// no longer need CSS vendor prefixes.
		cssnano: env === 'production',
	},
} );
