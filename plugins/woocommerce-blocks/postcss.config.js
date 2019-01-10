module.exports = ( { env } ) => ( {
	plugins: {
		autoprefixer: { browsers: [ '>1%' ] },
		cssnano: env === 'production',
	},
} );
