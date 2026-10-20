import fs from 'node:fs/promises';
import path from 'node:path';
import { buildZip } from './build-zip.mjs';
import { runEnvironment } from './env.mjs';
import { runCommand, toolingRoot } from './theme-utils.mjs';

export async function runThemeCheck( slug ) {
	const archive = await buildZip( slug );
	const stagingDirectory = path.join(
		toolingRoot,
		'.wp-env',
		'theme-check',
		slug,
		'theme'
	);
	await fs.rm( stagingDirectory, { recursive: true, force: true } );
	await fs.mkdir( stagingDirectory, { recursive: true } );
	const extractStatus = runCommand( 'unzip', [
		'-q',
		archive,
		'-d',
		stagingDirectory,
	] );
	if ( extractStatus !== 0 ) {
		return extractStatus;
	}
	const run = ( args ) =>
		runEnvironment(
			slug,
			args,
			'theme-check',
			path.join( stagingDirectory, slug )
		);
	try {
		const status = await run( [ 'start', '--runtime=docker' ] );
		if ( status !== 0 ) {
			return status;
		}
		return await run( [ 'run', 'cli', 'wp', 'theme-check', 'run', slug ] );
	} finally {
		if ( ( await run( [ 'stop' ] ) ) !== 0 ) {
			throw new Error(
				`Could not stop the Theme Check environment for ${ slug }.`
			);
		}
	}
}
