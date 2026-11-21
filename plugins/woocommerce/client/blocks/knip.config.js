/**
 * Internal dependencies
 */
const { getEntryConfig } = require( './bin/entries.js' );
const {
	scriptModuleEntries,
	styleEntries,
	editorStyleEntries,
} = require( './bin/interactivity-entries.js' );
const { moduleEntries } = require( './bin/rolldown/modules.mjs' );

// Every file the build starts from, so knip does not report them as unused.
const buildEntries = [
	...[
		'core',
		'main',
		'frontend',
		'payments',
		'extensions',
		'editor',
		'styling',
		'cartAndCheckoutFrontend',
	].flatMap( ( type ) => Object.values( getEntryConfig( type ) ).flat() ),
	...Object.values( scriptModuleEntries ),
	...Object.values( styleEntries ).flat(),
	...Object.values( editorStyleEntries ).flat(),
	...Object.values( moduleEntries ),
	'./assets/js/dependency-detection/index.ts',
].map(
	( file ) =>
		`${ file.replace( `${ __dirname }/`, '' ).replace( /^\.\//, '' ) }!`
);

module.exports = {
	entry: [
		'assets/js/**/index.{ts,tsx,jsx,js}!',
		...new Set( buildEntries ),
	],
	project: [
		'assets/js/**/*.{ts,tsx,jsx,js}!',
		'!assets/js/**/test/**/*.{ts,tsx,jsx,js}',
	],
};
