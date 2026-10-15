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

export async function prepareEnvironment( slug ) {
	const directory = await getThemeDirectory( slug );
	await fs.access( path.join( directory, 'style.css' ) );
	await fs.access( path.join( directory, 'theme.json' ) );
	const config = JSON.parse(
		await fs.readFile( path.join( toolingRoot, '.wp-env.json' ), 'utf8' )
	);
	config.themes = [ directory ];
	const configDirectory = path.join( toolingRoot, '.wp-env', slug );
	await fs.mkdir( configDirectory, { recursive: true } );
	const configPath = path.join( configDirectory, '.wp-env.json' );
	await fs.writeFile(
		configPath,
		JSON.stringify( config, null, '\t' ) + '\n'
	);
	return configPath;
}

export async function runEnvironment( slug, args ) {
	const configPath = await prepareEnvironment( slug );
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
		console.error( error.message );
		process.exitCode = 1;
	}
}
