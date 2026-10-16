// Only Jest uses this. Webpack passes its own babel-loader options, so the build gets an empty config here.
module.exports = api =>
	api.env( 'test' )
		? {
				presets: [
					[ '@babel/preset-env', { targets: { node: 'current' } } ],
					'@babel/preset-typescript',
				],
			}
		: {};
