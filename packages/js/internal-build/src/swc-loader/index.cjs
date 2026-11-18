/**
 * webpack `use` entry that compiles JS and TS with swc instead of Babel.
 *
 * It matches what `@wordpress/babel-preset-default` does for the browser
 * build: the WordPress browser targets, the automatic JSX runtime and ES module
 * output. swc is several times faster than Babel, and Babel ran on webpack's main
 * thread, which limits the admin and blocks builds.
 *
 * Unlike the Babel setup, propTypes are not removed in production builds.
 */
const browserslist = require( '@wordpress/browserslist-config' );

/**
 * Returns the swc parser options for a file, by extension.
 *
 * @param {string} resource Absolute file path.
 */
const getParser = ( resource ) =>
	/\.tsx?$/.test( resource )
		? { syntax: 'typescript', tsx: resource.endsWith( 'x' ) }
		: { syntax: 'ecmascript', jsx: true };

const swcLoader = ( { resource } ) => [
	{
		loader: require.resolve( 'swc-loader' ),
		options: {
			swcrc: false,
			// Like Babel's `sourceType: 'unambiguous'`.
			isModule: 'unknown',
			env: { targets: browserslist },
			jsc: {
				parser: getParser( resource ),
				transform: {
					react: { runtime: 'automatic' },
				},
			},
		},
	},
];

module.exports = { swcLoader };
