module.exports = function ( api ) {
	api.cache( true );

	return {
		presets: [
			[
				'@babel/preset-env',
				{
					targets: {
						node: 'current',
					},
				},
			],
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
