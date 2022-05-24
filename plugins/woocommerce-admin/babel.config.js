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
		ignore: [ 'packages/**/node_modules' ],
		env: {
			production: {},

			storybook: {
				plugins: [
					/**
					 * We need to set loose mode here because the storybook's default babel config enables the loose mode.
					 * The 'loose' mode configuration must be the same for those babel plugins.
					 *
					 */
					[
						'@babel/plugin-proposal-class-properties',
						{ loose: true },
					],
					[
						'@babel/plugin-proposal-private-methods',
						{ loose: true },
					],
					[
						'@babel/plugin-proposal-private-property-in-object',
						{ loose: true },
					],
				],
			},
		},
	};
};
