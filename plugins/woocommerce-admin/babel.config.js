const babelConfig = {
	presets: [
		[
			'@babel/preset-env',
			{
				targets: {
					node: 'current',
				},
			},
		],
	],
};

module.exports = function ( api ) {
	api.cache( true );

	return {
		...babelConfig,
		presets: [
			...babelConfig.presets,
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
		},
	};
};
