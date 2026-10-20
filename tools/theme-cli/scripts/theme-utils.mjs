import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const toolingRoot = fileURLToPath( new URL( '../', import.meta.url ) );
export const themesRoot = path.resolve( toolingRoot, '../../themes' );

export function isMain( url ) {
	return (
		process.argv[ 1 ] &&
		path.resolve( process.argv[ 1 ] ) === fileURLToPath( url )
	);
}

export function runCommand( command, args, options ) {
	const result = spawnSync( command, args, {
		stdio: 'inherit',
		...options,
	} );
	if ( result.error ) {
		throw result.error;
	}
	return result.status ?? 1;
}

export async function discoverThemes() {
	const entries = await fs.readdir( themesRoot, { withFileTypes: true } );
	return entries
		.filter( ( entry ) => entry.isDirectory() )
		.map( ( entry ) => entry.name )
		.sort( ( left, right ) => left.localeCompare( right ) );
}

export async function getThemeDirectory( slug ) {
	if ( ! ( await discoverThemes() ).includes( slug ) ) {
		throw new Error( `Unknown theme: ${ slug }` );
	}
	return path.join( themesRoot, slug );
}
