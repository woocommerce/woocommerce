const presetDir = './node_modules/@woocommerce/internal-js-tests';
// eslint-disable-next-line @typescript-eslint/no-require-imports -- Jest loads this config as CommonJS.
const preset = require( `${ presetDir }/jest-preset.js` );

// wp-build keeps route code in `routes/`, which the shared preset doesn't transform.
const [ , sourceTransform ] = Object.entries( preset.transform ).find(
	( [ pattern ] ) => pattern.startsWith( '(?:src' )
);

module.exports = {
	rootDir: __dirname,
	roots: [ '<rootDir>' ],
	preset: `<rootDir>/${ presetDir }`,
	transform: {
		...preset.transform,
		'routes/.*\\.[jt]sx?$': sourceTransform,
	},
};
