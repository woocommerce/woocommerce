const presetDir = './node_modules/@woocommerce/internal-js-tests';
// eslint-disable-next-line @typescript-eslint/no-require-imports -- Jest loads this config as CommonJS.
const preset = require( `${ presetDir }/jest-preset.js` );

module.exports = {
	rootDir: __dirname,
	roots: [ '<rootDir>' ],
	preset: `<rootDir>/${ presetDir }`,
	transform: {
		...preset.transform,
		// The shared preset does not transform wp-build routes.
		'routes/.*\\.[jt]sx?$': [
			'ts-jest',
			{
				tsconfig: `<rootDir>/${ presetDir }/tsconfig-jest.json`,
				diagnostics: false,
			},
		],
	},
};
