module.exports = {
	plugins: [
		require( '@wordpress/postcss-themes' )( {
			// @todo A default is required for now. Fix postcss-themes to allow no default
			defaults: {
				primary: '#0085ba',
				secondary: '#11a0d2',
				toggle: '#11a0d2',
				button: '#0085ba',
				outlines: '#007cba',
			},
			themes: {
				'woocommerce-onboarding': {
					primary: '#d52c82',
					secondary: '#d52c82',
					toggle: '#d52c82',
					button: '#d52c82',
					outlines: '#d52c82',
				},
				'woocommerce-page': {
					primary: '#95588a',
					secondary: '#95588a',
					toggle: '#95588a',
					button: '#95588a',
					outlines: '#95588a',
				},
			},
		} ),
		require( 'autoprefixer' )( { grid: true } ),
		require( 'postcss-color-function' ),
	],
};
