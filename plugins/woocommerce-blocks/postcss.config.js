module.exports = ( { env } ) => ( {
	plugins: {
		autoprefixer: { grid: true },
		cssnano: env === 'production',
	},
} );
