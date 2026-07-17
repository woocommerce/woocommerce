const preset = require( './node_modules/@woocommerce/internal-js-tests/jest-preset.js' );

// dataviews 17.2 pulls in packages that ship only ES modules (no CJS
// build), which jest must transform like the preset's other ESM deps.
const esmOnlyModules = [ '@wordpress/theme' ];

module.exports = {
	...preset,
	rootDir: './',
	roots: [ '<rootDir>/src' ],
	transformIgnorePatterns: [
		preset.transformIgnorePatterns[ 0 ].replace(
			')/)',
			'|' + esmOnlyModules.join( '|' ) + ')/)'
		),
	],
	transform: {
		...preset.transform,
		// No babel config reaches node_modules here, so convert the ESM
		// with an explicit inline preset.
		'node_modules/@wordpress/theme/.*\\.mjs$': [
			'babel-jest',
			{
				configFile: false,
				babelrc: false,
				presets: [
					[
						'@babel/preset-env',
						{ targets: { node: 'current' } },
					],
				],
			},
		],
	},
};
