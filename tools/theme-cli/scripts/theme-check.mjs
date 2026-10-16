import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { listThemeFiles, toolingRoot } from './theme-utils.mjs';

export async function runThemeCheck( directory ) {
	const slug = path.basename( directory );
	const config = JSON.parse(
		await fs.readFile( path.join( toolingRoot, '.wp-env.json' ), 'utf8' )
	);
	const configDirectory = path.join(
		toolingRoot,
		'.wp-env',
		'theme-check',
		slug
	);
	const stagedTheme = path.join( configDirectory, 'themes', slug );
	const files = await listThemeFiles( directory );
	await fs.rm( stagedTheme, { recursive: true, force: true } );
	for ( const file of files ) {
		const destination = path.join( stagedTheme, file );
		await fs.mkdir( path.dirname( destination ), { recursive: true } );
		await fs.copyFile( path.join( directory, file ), destination );
	}
	config.themes = [ stagedTheme ];
	await fs.mkdir( configDirectory, { recursive: true } );
	const configPath = path.join( configDirectory, '.wp-env.json' );
	await fs.writeFile(
		configPath,
		JSON.stringify( config, null, '\t' ) + '\n'
	);
	const run = ( args ) => {
		const result = spawnSync(
			'pnpm',
			[ 'exec', 'wp-env', '--config', configPath, ...args ],
			{
				cwd: toolingRoot,
				stdio: 'inherit',
				env: {
					...process.env,
					WP_ENV_HOME:
						process.env.WP_ENV_HOME ??
						path.join(
							toolingRoot,
							'.wp-env',
							'theme-check',
							'home'
						),
				},
			}
		);
		if ( result.error ) {
			throw result.error;
		}
		return result.status ?? 1;
	};
	try {
		const startStatus = run( [ 'start', '--runtime=docker' ] );
		if ( startStatus !== 0 ) {
			return startStatus;
		}
		const activateStatus = run( [
			'run',
			'cli',
			'wp',
			'plugin',
			'activate',
			'theme-check',
		] );
		if ( activateStatus !== 0 ) {
			return activateStatus;
		}
		return run( [ 'run', 'cli', 'wp', 'theme-check', 'run', slug ] );
	} finally {
		const stopStatus = run( [ 'stop' ] );
		if ( stopStatus !== 0 ) {
			throw new Error(
				`Could not stop the Theme Check environment for ${ slug }.`
			);
		}
	}
}
