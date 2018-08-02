module.exports = {
	plugins: [
		require( './node_modules/@wordpress/postcss-themes' )( {
			// @TODO: A default is required for now. Fix postcss-themes to allow no default
			defaults: {
				primary: '#0085ba',
				secondary: '#11a0d2',
				toggle: '#11a0d2',
				button: '#0085ba',
				outlines: '#007cba',
			},
			themes: {
				'woocommerce-page': {
					primary: '#95588a',
					secondary: '#95588a',
					toggle: '#95588a',
					button: '#95588a',
					outlines: '#95588a',
				},
			},
		} ),
		require( 'autoprefixer' ),
		require( 'postcss-color-function' ),
	],
};
