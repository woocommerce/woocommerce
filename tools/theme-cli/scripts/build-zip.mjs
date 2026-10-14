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

export async function buildZip(
	slug,
	commit,
	outputDirectory = path.join( themesRoot, slug, 'artifacts' ),
	repository = path.dirname( themesRoot )
) {
	assertThemeSlug( slug );
	if ( ! /^[a-f0-9]{40}$/.test( commit ?? '' ) ) {
		throw new Error(
			'Supply a full commit SHA. Only committed theme files are packaged.'
		);
	}
	const git = ( ...args ) =>
		execFileSync( 'git', args, {
			cwd: repository,
			encoding: 'utf8',
			maxBuffer: 10 * 1024 * 1024,
		} ).trim();
	if ( git( 'cat-file', '-t', commit ) !== 'commit' ) {
		throw new Error(
			'Supply the SHA of a commit, not a tag or tree object.'
		);
	}
	const tree = `${ commit }:themes/${ slug }`;
	const stylesheet = git( 'show', `${ tree }/style.css` );
	const version = readThemeHeader( stylesheet, 'Version' );
	if ( ! /^\d+\.\d+\.\d+$/.test( version ?? '' ) ) {
		throw new Error(
			'The committed style.css must declare a version in X.Y.Z format.'
		);
	}
	const files = git( 'ls-tree', '-r', '--name-only', tree )
		.split( '\n' )
		.filter( isThemeFile );
	if (
		! files.includes( 'style.css' ) ||
		! files.includes( 'templates/index.html' ) ||
		! files.includes( 'theme.json' )
	) {
		throw new Error(
			'The committed block theme must include style.css, theme.json, and templates/index.html.'
		);
	}
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
		const [ slug, commit, outputDirectory ] = process.argv.slice( 2 );
		console.log( await buildZip( slug, commit, outputDirectory ) );
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}
