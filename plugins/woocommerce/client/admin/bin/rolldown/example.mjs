// Builds an extension example from docs/examples/extensions into
/* eslint-disable no-console -- command line script */
// plugins/woocommerce/client/<name>, with dist/index.js and dist/index.asset.php.
// Usage: WC_EXT=simple-inbox-note node bin/rolldown/example.mjs
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { rolldown } from 'rolldown';
import { assetPhp } from '@woocommerce/rolldown-build/classic';
import {
	ADMIN,
	TARGET,
	externalsFor,
} from '@woocommerce/rolldown-build/shared';

const extension = process.env.WC_EXT;
if ( ! extension ) {
	throw new Error(
		'Please provide an extension: WC_EXT=<example directory>.'
	);
}
const source = path.join( ADMIN, 'docs/examples/extensions', extension );
if ( ! fs.existsSync( path.join( source, 'js/index.js' ) ) ) {
	throw new Error( `Extension example ${ extension } does not exist.` );
}
const target = path.resolve( ADMIN, '..', extension );
fs.cpSync( source, target, { recursive: true } );

const externals = externalsFor();
const bundle = await rolldown( {
	input: path.join( source, 'js/index.js' ),
	platform: 'browser',
	tsconfig: false,
	moduleTypes: { '.js': 'jsx' },
	transform: { target: TARGET, jsx: { runtime: 'automatic' } },
	external: ( id ) => Boolean( externals.toGlobal( id ) ),
} );
const { output } = await bundle.write( {
	dir: path.join( target, 'dist' ),
	format: 'iife',
	entryFileNames: 'index.js',
	sourcemap: true,
	globals: ( id ) =>
		[ 'window', ...[].concat( externals.toGlobal( id ) ) ].join( '.' ),
} );
await bundle.close();
const [ chunk ] = output;
fs.writeFileSync(
	path.join( target, 'dist/index.asset.php' ),
	assetPhp( {
		dependencies: chunk.imports
			.map( ( id ) => externals.toHandle( id ) )
			.sort(),
		version: createHash( 'sha1' )
			.update( chunk.code )
			.digest( 'hex' )
			.slice( 0, 20 ),
	} )
);
console.log( `Built ${ path.relative( process.cwd(), target ) }` );
