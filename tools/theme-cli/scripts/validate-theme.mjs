import fs from 'node:fs/promises';
import path from 'node:path';
import { runThemeCheck } from './theme-check.mjs';
import {
	discoverThemes,
	getThemeDirectory,
	isMain,
	runCommand,
} from './theme-utils.mjs';

export async function checkThemeVersion( directory ) {
	const [ stylesheet, manifest, readme ] = await Promise.all(
		[ 'style.css', 'package.json', 'readme.txt' ].map( ( file ) =>
			fs.readFile( path.join( directory, file ), 'utf8' )
		)
	);
	const versions = {
		'style.css': stylesheet
			.match( /^[ \t/*#@]*Version:[ \t]*([^\r\n]*)/im )?.[ 1 ]
			.trim(),
		'package.json': JSON.parse( manifest ).version,
		'readme.txt': readme
			.match( /^[ \t]*Stable tag:[ \t]*([^\r\n]*)/im )?.[ 1 ]
			.trim(),
	};
	const values = Object.values( versions );
	if (
		values.some(
			( version ) => typeof version !== 'string' || ! version
		) ||
		new Set( values ).size !== 1
	) {
		const details = Object.entries( versions )
			.map(
				( [ file, version ] ) =>
					`${ file }: ${ version || '(missing)' }`
			)
			.join( ', ' );
		throw new Error(
			`Theme versions must be present and match. ${ details }.`
		);
	}
}

/**
 * Lint a theme's JavaScript, CSS, Markdown, and PHP, then run WordPress Theme Check
 * in a separate Docker environment.
 *
 */
export async function validateTheme( slug, fix = false ) {
	process.stdout.write( `Validating ${ slug }\n` );
	const directory = await getThemeDirectory( slug );
	await checkThemeVersion( directory );
	const checks = [
		[ 'lint-js', '.', '--no-error-on-unmatched-pattern' ],
		[ 'lint-style' ],
		[ 'lint-md-docs' ],
	];
	let status = 0;
	for ( const check of checks ) {
		const checkStatus = runCommand(
			'pnpm',
			[ 'exec', 'wp-scripts', ...check, ...( fix ? [ '--fix' ] : [] ) ],
			{ cwd: directory }
		);
		if ( checkStatus !== 0 ) {
			status = 1;
		}
	}
	const runPHP = ( script ) =>
		runCommand( 'composer', [ 'run-script', script ], {
			cwd: directory,
		} );
	const fixStatus = fix ? runPHP( 'phpcbf' ) : 0;
	if ( fixStatus > 1 || runPHP( 'phpcs' ) !== 0 ) {
		status = 1;
	}
	const themeCheckStatus = await runThemeCheck( slug );
	return status === 0 && themeCheckStatus === 0 ? 0 : 1;
}

if ( isMain( import.meta.url ) ) {
	try {
		const args = process.argv.slice( 2 );
		const fix = args.includes( '--fix' );
		const targets = args.filter( ( arg ) => arg !== '--fix' );
		if ( targets.length !== 1 ) {
			throw new Error( 'Usage: validate-theme.mjs <slug|--all> [--fix]' );
		}
		const slugs =
			targets[ 0 ] === '--all' ? await discoverThemes() : targets;
		for ( const slug of slugs ) {
			try {
				if ( ( await validateTheme( slug, fix ) ) !== 0 ) {
					process.exitCode = 1;
				}
			} catch ( error ) {
				process.stderr.write( `${ slug }: ${ error.message }\n` );
				process.exitCode = 1;
			}
		}
	} catch ( error ) {
		process.stderr.write( `${ error.message }\n` );
		process.exitCode = 1;
	}
}
