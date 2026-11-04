const presetDir = './node_modules/@woocommerce/internal-js-tests';
// eslint-disable-next-line @typescript-eslint/no-require-imports -- Jest loads this config as CommonJS.
const preset = require( `${ presetDir }/jest-preset.js` );

// ESM-only packages that @wordpress/editor imports, on top of those the preset transforms.
const esmModules = [ 'client-zip' ];

module.exports = {
	rootDir: __dirname,
	roots: [ '<rootDir>' ],
	preset: `<rootDir>/${ presetDir }`,
	transformIgnorePatterns: preset.transformIgnorePatterns.map( ( pattern ) =>
		pattern.replace(
			'(?:\\.pnpm|',
			`(?:\\.pnpm|${ esmModules.join( '|' ) }|`
		)
	),
	transform: {
		...preset.transform,
		...Object.fromEntries(
			esmModules.map( ( name ) => [
				`node_modules/${ name }/.*\\.js$`,
				'babel-jest',
			] )
		),
		// The shared preset does not transform wp-build routes or entity fields.
		'(?:routes|entity-fields)/.*\\.[jt]sx?$': [
			'ts-jest',
			{
				tsconfig: `<rootDir>/${ presetDir }/tsconfig-jest.json`,
				diagnostics: false,
			},
		],
	},
};
