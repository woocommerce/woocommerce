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

export async function discoverThemes() {
	const entries = await fs.readdir( themesRoot, { withFileTypes: true } );
	return entries
		.filter( ( entry ) => entry.isDirectory() )
		.map( ( entry ) => ( { slug: entry.name } ) )
		.sort( ( left, right ) => left.slug.localeCompare( right.slug ) );
}

export async function getThemeDirectory( slug ) {
	if (
		! ( await discoverThemes() ).some( ( theme ) => theme.slug === slug )
	) {
		throw new Error( `Unknown theme: ${ slug }` );
	}
	return path.join( themesRoot, slug );
}
