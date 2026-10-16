import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { getThemeDirectory, isMain, toolingRoot } from './theme-utils.mjs';

export function environmentArgs( args ) {
	if (
		args[ 0 ] === 'start' &&
		! args.some(
			( arg ) => arg === '--runtime' || arg.startsWith( '--runtime=' )
		)
	) {
		return [ ...args, '--runtime=playground' ];
	}
	return args;
}

export async function prepareEnvironment( slug, environment = '' ) {
	const directory = await getThemeDirectory( slug );
	await fs.access( path.join( directory, 'style.css' ) );
	await fs.access( path.join( directory, 'theme.json' ) );
	const config = JSON.parse(
		await fs.readFile( path.join( toolingRoot, '.wp-env.json' ), 'utf8' )
	);
	config.themes = [ directory ];
	const configDirectory = path.join(
		toolingRoot,
		'.wp-env',
		environment,
		slug
	);
	await fs.mkdir( configDirectory, { recursive: true } );
	const configPath = path.join( configDirectory, '.wp-env.json' );
	await fs.writeFile(
		configPath,
		JSON.stringify( config, null, '\t' ) + '\n'
	);
	return configPath;
}

export async function runEnvironment( slug, args, environment = '' ) {
	const configPath = await prepareEnvironment( slug, environment );
	const result = spawnSync(
		'pnpm',
		[
			'exec',
			'wp-env',
			...environmentArgs( args ),
			'--config',
			configPath,
		],
		{
			cwd: toolingRoot,
			stdio: 'inherit',
			env: environment
				? {
						...process.env,
						WP_ENV_HOME:
							process.env.WP_ENV_HOME ??
							path.join(
								toolingRoot,
								'.wp-env',
								environment,
								'home'
							),
					}
				: process.env,
		}
	);
	if ( result.error ) {
		throw result.error;
	}
	return result.status ?? 1;
}

if ( isMain( import.meta.url ) ) {
	try {
		const [ command, slug, ...args ] = process.argv.slice( 2 );
		process.exitCode = await runEnvironment( slug, [ command, ...args ] );
	} catch ( error ) {
		process.stderr.write( `${ error.message }\n` );
		process.exitCode = 1;
	}
}
