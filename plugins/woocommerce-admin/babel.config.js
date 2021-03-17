const {
	babelConfig: e2eBabelConfig,
} = require( '@woocommerce/e2e-environment' );

module.exports = function ( api ) {
	api.cache( true );

	return {
		...e2eBabelConfig,
		presets: [
			...e2eBabelConfig.presets,
			'@babel/preset-typescript',
			'@wordpress/babel-preset-default',
		],
		sourceType: 'unambiguous',
		plugins: [
			/**
			 * This allows arrow functions as class methods so that binding
			 * methods to `this` in the constructor isn't required.
			 */
			'@babel/plugin-proposal-class-properties',
		],
		env: {
			production: {
				plugins: [
					[
						'@wordpress/babel-plugin-makepot',
						{
							output: 'languages/woocommerce-admin.po',
						},
					],
				],
			},
		},
	};
};
