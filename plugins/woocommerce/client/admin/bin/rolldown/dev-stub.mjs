// Points built admin scripts at the Vite dev server, for hot reload.
/* eslint-disable no-console -- command line script */
// Usage: node dev/stub.mjs [outDir] <entry> [entry...], for example
//   node dev/stub.mjs app product-tour settings-embed
// Rebuild admin to undo.
import fs from 'node:fs';
import path from 'node:path';
import { ADMIN } from '@woocommerce/rolldown-build/shared';
import { entries, OUT } from './config.mjs';

const args = process.argv.slice( 2 );
const outDir = args[ 0 ]?.includes( '/' ) ? args.shift() : OUT;
const byName = new Map( entries().map( ( entry ) => [ entry.name, entry ] ) );

for ( const name of args ) {
	const entry = byName.get( name );
	if ( ! entry ) {
		throw new Error( `No admin entry named ${ name }` );
	}
	// Vite serves ES modules, which run after classic scripts. Package
	// entries set `wc.*` globals that other scripts read when they load, so
	// only entries nothing else reads can come from Vite.
	if ( ! (
		name === 'app' ||
		name === 'embed' ||
		entry.out.startsWith( 'wp-admin-scripts/' )
	) ) {
		throw new Error(
			`${ name } sets a wc.* global other scripts need at load; it cannot be served by Vite`
		);
	}
	const file = path.resolve( ADMIN, entry.files.at( -1 ) );
	const source = fs.statSync( file ).isDirectory()
		? [ 'index.tsx', 'index.ts', 'index.js' ]
				.map( ( f ) => path.join( file, f ) )
				.find( fs.existsSync )
		: file;
	const url =
		'/' + path.relative( ADMIN, source ).split( path.sep ).join( '/' );
	const stub = fs
		.readFileSync( path.join( import.meta.dirname, 'dev-stub.js' ), 'utf8' )
		.replace( '/client/index.tsx', url );
	fs.writeFileSync( path.join( outDir, entry.out ), stub );
	console.log( `${ entry.out } -> http://localhost:5173${ url }` );
}
