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
			'@wordpress/babel-preset-default',
		],
		sourceType: 'unambiguous',
		plugins: [],
		ignore: [ 'packages/**/node_modules' ],
		env: {
			production: {},
		},
	};
};
