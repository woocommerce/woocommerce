import { runThemeCheck } from './theme-check.mjs';
import {
	discoverThemes,
	getThemeDirectory,
	isMain,
	runCommand,
} from './theme-utils.mjs';

/**
 * Lint a theme's JavaScript, CSS, Markdown, and PHP, then run WordPress Theme Check
 * in a separate Docker environment.
 *
 */
export async function validateTheme( slug, fix = false ) {
	process.stdout.write( `Validating ${ slug }\n` );
	const directory = await getThemeDirectory( slug );
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
