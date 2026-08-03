const internalPreset = require( './node_modules/@woocommerce/internal-js-tests/jest-preset' );

const dataviewsWpEntry =
	'node_modules/@wordpress/dataviews/build-wp/index\\.js$';

module.exports = {
	...internalPreset,
	rootDir: './',
	roots: [ '<rootDir>/src' ],
	moduleNameMapper: {
		...internalPreset.moduleNameMapper,
		'^react$': require.resolve( 'react' ),
		'^react-dom$': require.resolve( 'react-dom' ),
		'^react-dom/client$': require.resolve( 'react-dom/client' ),
		'^react/jsx-runtime$': require.resolve( 'react/jsx-runtime' ),
	},
	transform: {
		...internalPreset.transform,
		[ dataviewsWpEntry ]: [
			'babel-jest',
			{
				babelrc: false,
				configFile: false,
				presets: [
					[
						require.resolve( '@babel/preset-env' ),
						{
							modules: 'commonjs',
							targets: { node: 'current' },
						},
					],
				],
			},
		],
	},
	transformIgnorePatterns: internalPreset.transformIgnorePatterns.map(
		( pattern ) =>
			pattern.replace(
				'(?:\\.pnpm|',
				'(?:\\.pnpm|@wordpress/dataviews|'
			)
	),
};
