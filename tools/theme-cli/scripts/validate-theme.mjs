import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { runThemeCheck } from './theme-check.mjs';
import {
	discoverThemes,
	getThemeDirectory,
	isMain,
	toolingRoot,
} from './theme-utils.mjs';

const repositoryRoot = path.resolve( toolingRoot, '../..' );

export async function validateTheme( slug, fix = false ) {
	console.log( `Validating ${ slug }` );
	const directory = await getThemeDirectory( slug );
	const checks = [
		[
			'lint-js',
			'.',
			'--config',
			path.join( repositoryRoot, 'eslint.config.mjs' ),
			'--no-error-on-unmatched-pattern',
			'--rule',
			JSON.stringify( {
				'@wordpress/i18n-text-domain': [
					'error',
					{ allowedTextDomain: slug },
				],
			} ),
		],
		[
			'lint-style',
			'--config',
			path.join( repositoryRoot, 'stylelint.config.cjs' ),
		],
		[
			'lint-md-docs',
			'--config',
			path.join( repositoryRoot, '.markdownlint.json' ),
		],
	];
	let status = 0;
	for ( const check of checks ) {
		const result = spawnSync(
			'pnpm',
			[ 'exec', 'wp-scripts', ...check, ...( fix ? [ '--fix' ] : [] ) ],
			{ cwd: directory, stdio: 'inherit' }
		);
		if ( result.error ) {
			throw result.error;
		}
		if ( result.status !== 0 ) {
			status = 1;
		}
	}
	const themeCheckStatus = await runThemeCheck( directory );
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
			targets[ 0 ] === '--all'
				? ( await discoverThemes() ).map( ( theme ) => theme.slug )
				: targets;
		for ( const slug of slugs ) {
			try {
				if ( ( await validateTheme( slug, fix ) ) !== 0 ) {
					process.exitCode = 1;
				}
			} catch ( error ) {
				console.error( `${ slug }: ${ error.message }` );
				process.exitCode = 1;
			}
		}
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}
