module.exports = {
	presets: [
		[
			'@wordpress/babel-preset-default',
			{
				targets: {
					node: 'current',
				},
			},
		],
		[ '@babel/preset-typescript' ],
	],
};
