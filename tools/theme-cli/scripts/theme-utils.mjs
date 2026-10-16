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

export function assertThemeSlug( slug ) {
	if ( ! /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test( slug ?? '' ) ) {
		throw new Error(
			'Supply a theme slug containing lowercase letters, numbers, and hyphens.'
		);
	}
}

export async function getThemeDirectory( slug, root = themesRoot ) {
	assertThemeSlug( slug );
	const directory = path.join( root, slug );
	if ( ! ( await fs.lstat( directory ) ).isDirectory() ) {
		throw new Error(
			`Theme ${ slug } must be a directory, not a symbolic link.`
		);
	}
	return directory;
}

export function readThemeHeader( contents, header ) {
	return (
		contents
			.match( new RegExp( `^${ header }:\\s*(.+)$`, 'mi' ) )?.[ 1 ]
			.trim() ?? null
	);
}

export async function discoverThemes( root = themesRoot ) {
	const themes = [];
	for ( const entry of await fs.readdir( root, { withFileTypes: true } ) ) {
		if ( ! entry.isDirectory() ) {
			continue;
		}
		try {
			await fs.access( path.join( root, entry.name, 'style.css' ) );
			await fs.access( path.join( root, entry.name, 'theme.json' ) );
		} catch {
			continue;
		}
		assertThemeSlug( entry.name );
		themes.push( { slug: entry.name } );
	}
	return themes.sort( ( left, right ) =>
		left.slug.localeCompare( right.slug )
	);
}

const developmentPaths = new Set( [
	'node_modules',
	'vendor',
	'tests',
	'scripts',
	'changelog',
	'artifacts',
	'package.json',
	'package-lock.json',
	'pnpm-lock.yaml',
	'composer.json',
	'composer.lock',
	'phpcs.xml',
	'phpcs.xml.dist',
	'phpunit.xml',
	'phpunit.xml.dist',
	'phpstan.neon',
	'phpstan.neon.dist',
	'README.md',
	'CHANGELOG.md',
	'AGENTS.md',
	'CLAUDE.md',
] );

export function isThemeFile( relativePath ) {
	return relativePath
		.split( '/' )
		.every(
			( part ) =>
				! part.startsWith( '.' ) && ! developmentPaths.has( part )
		);
}

export async function listThemeFiles( directory, prefix = '' ) {
	const files = [];
	for ( const entry of await fs.readdir( path.join( directory, prefix ), {
		withFileTypes: true,
	} ) ) {
		const relativePath = prefix + entry.name;
		if ( ! isThemeFile( relativePath ) ) {
			continue;
		}
		if ( entry.isSymbolicLink() ) {
			throw new Error(
				`Theme files must not be symbolic links: ${ relativePath }`
			);
		}
		if ( entry.isDirectory() ) {
			files.push(
				...( await listThemeFiles( directory, relativePath + '/' ) )
			);
		} else {
			files.push( relativePath );
		}
	}
	return files.sort();
}
