import { execFileSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import {
	assertThemeSlug,
	isMain,
	isThemeFile,
	readThemeHeader,
	themesRoot,
} from './theme-utils.mjs';

export async function buildZip( slug ) {
	assertThemeSlug( slug );
	const git = ( ...args ) =>
		execFileSync( 'git', args, {
			cwd: path.dirname( themesRoot ),
			encoding: 'utf8',
		} ).trim();
	const commit = git( 'rev-parse', 'HEAD' );
	const tree = `${ commit }:themes/${ slug }`;
	const stylesheet = git( 'show', `${ tree }/style.css` );
	const version = readThemeHeader( stylesheet, 'Version' );
	const files = git( 'ls-tree', '-r', '--name-only', tree )
		.split( '\n' )
		.filter( isThemeFile );
	const outputDirectory = path.join( themesRoot, slug, 'artifacts' );
	await fs.mkdir( outputDirectory, { recursive: true } );
	const archive = path.resolve(
		outputDirectory,
		`${ slug }-${ version }.zip`
	);
	git(
		'archive',
		'--format=zip',
		`--prefix=${ slug }/`,
		`--output=${ archive }`,
		tree,
		'--',
		...files
	);
	return archive;
}

if ( isMain( import.meta.url ) ) {
	try {
		console.log( await buildZip( process.argv[ 2 ] ) );
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}
